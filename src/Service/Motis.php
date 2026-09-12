<?php

namespace App\Service;

use App\Entity\MotisInstances;
use App\Repository\MotisInstancesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;

/**
 * Aiguillage vers l'instance MOTIS en service.
 *
 * L'API ne connaît pas de port en dur : elle demande ici quelle instance
 * interroger. La table motis_instances porte l'état, un unique drapeau `active`
 * désigne l'instance de production, et le déploiement blue-green se contente de
 * déplacer ce drapeau une fois la nouvelle instance vérifiée.
 *
 * Si l'instance active ne répond pas, on bascule automatiquement sur l'autre
 * quand elle est saine, plutôt que de renvoyer une erreur.
 */
class Motis
{
    /** Sonde de vivacité : peu coûteuse, sert au contrôle périodique. */
    public const PROBE_LIVENESS = '/api/v1/map/initial';

    /** Sonde de calcul d'itinéraire : valide que l'horaire est bien chargé. */
    public const PLAN_PATH = '/api/v5/plan';

    private EntityManagerInterface $entityManager;
    private MotisInstancesRepository $motisInstancesRepository;
    private ParameterBagInterface $params;
    private Logger $logger;

    /** Mémoïsation par requête HTTP : inutile de re-sonder plusieurs fois. */
    private ?MotisInstances $resolved = null;
    private bool $resolvedComputed = false;

    public function __construct(
        EntityManagerInterface $entityManager,
        MotisInstancesRepository $motisInstancesRepository,
        ParameterBagInterface $params,
        Logger $logger
    ) {
        $this->entityManager = $entityManager;
        $this->motisInstancesRepository = $motisInstancesRepository;
        $this->params = $params;
        $this->logger = $logger;
    }

    public function getHost(): string
    {
        return (string) ($this->params->has('motis_host') ? $this->params->get('motis_host') : '127.0.0.1');
    }

    public function getBaseUrl(MotisInstances $instance): string
    {
        return sprintf('http://%s:%s', $this->getHost(), $instance->getPort());
    }

    /**
     * Instance à interroger, ou null si aucune n'est exploitable.
     *
     * On se fie d'abord à l'état enregistré en base — tenu à jour par
     * app:motis:health — pour ne pas payer une sonde HTTP à chaque requête
     * d'API. Si l'appel échoue quand même, markUnhealthy() bascule sur l'autre.
     */
    public function resolve(): ?MotisInstances
    {
        if ($this->resolvedComputed) {
            return $this->resolved;
        }

        $this->resolvedComputed = true;

        $servable = $this->motisInstancesRepository->findServable();
        $this->resolved = $servable[0] ?? null;

        if ($this->resolved === null) {
            // Aucune instance déclarée saine. Deux cas à distinguer :
            //  - l'état en base est frais, donc MOTIS est réellement indisponible :
            //    on répond tout de suite, sans payer une sonde par requête ;
            //  - l'état est périmé (health pas encore passé, redémarrage récent) :
            //    on sonde une fois avant d'abandonner.
            $instances = $this->motisInstancesRepository->findBy([], ['active' => 'DESC']);

            if ($this->isStateStale($instances)) {
                foreach ($instances as $instance) {
                    if ($this->probe($instance)['ok']) {
                        $this->resolved = $instance;
                        break;
                    }
                }
            }
        }

        return $this->resolved;
    }

    /**
     * L'état enregistré est-il trop vieux pour qu'on s'y fie ?
     *
     * app:motis:health rafraîchit checked_at à chaque passage. Au-delà de la
     * fenêtre de fraîcheur, on considère que la supervision ne tourne pas et on
     * sonde nous-mêmes.
     *
     * @param MotisInstances[] $instances
     */
    private function isStateStale(array $instances): bool
    {
        $freshness = (int) ($this->params->has('motis_state_freshness') ? $this->params->get('motis_state_freshness') : 180);
        $threshold = new \DateTime('-' . $freshness . ' seconds');

        foreach ($instances as $instance) {
            if ($instance->getCheckedAt() !== null && $instance->getCheckedAt() > $threshold) {
                return false;
            }
        }

        return true;
    }

    /**
     * Effectue une requête sur l'instance en service.
     *
     * @param array<string, mixed> $query
     *
     * @return array{ok: bool, status: int, content: string|null, error: string|null, instance: string|null}
     */
    public function request(string $path, array $query = [], float $timeout = 20.0): array
    {
        $instance = $this->resolve();

        if ($instance === null) {
            return [
                'ok'       => false,
                'status'   => 503,
                'content'  => null,
                'error'    => 'Aucune instance MOTIS disponible',
                'instance' => null,
            ];
        }

        $result = $this->requestOn($instance, $path, $query, $timeout);

        if ($result['ok']) {
            return $result;
        }

        // L'instance déclarée saine ne répond pas : on la marque et on tente
        // l'autre, pour qu'une panne ne se traduise pas par une coupure d'API.
        $this->markUnhealthy($instance, $result['error'] ?? 'requête en échec');

        foreach ($this->motisInstancesRepository->findAll() as $fallback) {
            if ($fallback->getId() === $instance->getId()) {
                continue;
            }

            if (!$this->probe($fallback)['ok']) {
                continue;
            }

            $this->resolved = $fallback;
            $retry = $this->requestOn($fallback, $path, $query, $timeout);

            if ($retry['ok']) {
                $this->logger->log([
                    'message' => sprintf(
                        '[motis] bascule de %s vers %s après échec de requête',
                        $instance->getId(),
                        $fallback->getId()
                    ),
                ], 'WARN');

                return $retry;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{ok: bool, status: int, content: string|null, error: string|null, instance: string|null}
     */
    public function requestOn(MotisInstances $instance, string $path, array $query = [], float $timeout = 20.0): array
    {
        $url = $this->getBaseUrl($instance) . '/' . ltrim($path, '/');

        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        try {
            $client = HttpClient::create(['timeout' => $timeout, 'max_duration' => $timeout]);
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();
            $content = $response->getContent(false);

            return [
                'ok'       => $status === 200,
                'status'   => $status,
                'content'  => $content,
                'error'    => $status === 200 ? null : "HTTP $status",
                'instance' => $instance->getId(),
            ];
        } catch (HttpExceptionInterface | \Throwable $e) {
            return [
                'ok'       => false,
                'status'   => 0,
                'content'  => null,
                'error'    => $e->getMessage(),
                'instance' => $instance->getId(),
            ];
        }
    }

    /**
     * Sonde de vivacité : le serveur répond-il ?
     *
     * @return array{ok: bool, error: string|null}
     */
    public function probe(MotisInstances $instance, float $timeout = 5.0): array
    {
        $result = $this->requestOn($instance, self::PROBE_LIVENESS, [], $timeout);

        return ['ok' => $result['ok'], 'error' => $result['error']];
    }

    /**
     * Sonde de bout en bout : l'instance sait-elle réellement calculer un
     * itinéraire ? C'est ce qu'on vérifie avant de basculer la production
     * dessus — un serveur qui répond mais dont l'horaire est vide passerait la
     * sonde de vivacité.
     *
     * @return array{ok: bool, error: string|null, itineraries: int}
     */
    public function probeRouting(MotisInstances $instance, float $timeout = 60.0): array
    {
        $from = (string) ($this->params->has('motis_probe_from') ? $this->params->get('motis_probe_from') : '48.8442,2.3220');
        $to = (string) ($this->params->has('motis_probe_to') ? $this->params->get('motis_probe_to') : '48.8800,2.3550');

        $result = $this->requestOn($instance, self::PLAN_PATH, [
            'fromPlace' => $from,
            'toPlace'   => $to,
            'time'      => (new \DateTime('+1 hour'))->format(DATE_ATOM),
            'arriveBy'  => 'false',
        ], $timeout);

        if (!$result['ok']) {
            return ['ok' => false, 'error' => $result['error'], 'itineraries' => 0];
        }

        $json = json_decode((string) $result['content'], true);
        $itineraries = $json['itineraries'] ?? $json['plan']['itineraries'] ?? null;

        if (!is_array($itineraries)) {
            return ['ok' => false, 'error' => 'réponse sans itinéraire exploitable', 'itineraries' => 0];
        }

        if ($itineraries === []) {
            return ['ok' => false, 'error' => 'aucun itinéraire trouvé sur le trajet témoin', 'itineraries' => 0];
        }

        return ['ok' => true, 'error' => null, 'itineraries' => count($itineraries)];
    }

    /**
     * Le pid enregistré correspond-il toujours à un processus motis ?
     *
     * On vérifie la ligne de commande et pas seulement l'existence du pid :
     * après un redémarrage de la machine, un pid enregistré peut très bien
     * désigner un processus sans rapport, et l'instance passerait alors pour
     * vivante alors que MOTIS est arrêté.
     */
    public function isProcessRunning(?string $pid): bool
    {
        if ($pid === null || $pid === '' || !ctype_digit($pid)) {
            return false;
        }

        $cmdline = @file_get_contents('/proc/' . $pid . '/cmdline');

        if ($cmdline === false) {
            return false;
        }

        return str_contains(str_replace("\0", ' ', $cmdline), 'motis');
    }

    public function markHealthy(MotisInstances $instance): void
    {
        $instance->setHealthy(true);
        $instance->setState(MotisInstances::STATE_RUNNING);
        $instance->setLastError(null);

        $this->entityManager->flush();
    }

    public function markUnhealthy(MotisInstances $instance, string $error): void
    {
        $instance->setHealthy(false);
        $instance->setLastError($error);

        if (!$this->isProcessRunning($instance->getPid())) {
            $instance->setState(MotisInstances::STATE_STOPPED);
            $instance->setPid(null);
        } else {
            $instance->setState(MotisInstances::STATE_FAILED);
        }

        $this->entityManager->flush();
    }

    /**
     * Bascule la production sur $instance.
     *
     * L'opération se fait en transaction : à aucun moment deux instances ne
     * portent le drapeau, et à aucun moment aucune ne le porte.
     */
    public function promote(MotisInstances $instance): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $connection->executeStatement('UPDATE motis_instances SET active = 0 WHERE id <> ?', [$instance->getId()]);
            $connection->executeStatement('UPDATE motis_instances SET active = 1 WHERE id = ?', [$instance->getId()]);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        // La bascule est écrite en SQL direct : les entités déjà chargées gardent
        // sinon l'ancien drapeau. Il faut rafraîchir *toutes* les instances, pas
        // seulement la promue — sans quoi l'ancienne active reste `active` en
        // mémoire et app:motis:stop refuse ensuite de l'arrêter.
        foreach ($this->motisInstancesRepository->findAll() as $other) {
            $this->entityManager->refresh($other);
        }

        $this->logger->log(['message' => '[motis] instance active : ' . $instance->getId()], 'INFO');
    }

    /**
     * Empreinte du jeu de données d'entrée (GTFS + OSM), pour savoir si une
     * instance sert encore les données courantes.
     */
    public function computeDataHash(): string
    {
        $parts = [];

        foreach ([$this->params->get('gtfs_path'), $this->params->get('osm_path')] as $directory) {
            $files = glob(rtrim((string) $directory, '/') . '/*');

            foreach ($files === false ? [] : $files as $file) {
                if (is_file($file)) {
                    $parts[] = basename($file) . ':' . filesize($file) . ':' . filemtime($file);
                }
            }
        }

        sort($parts);

        return substr(hash('sha256', implode('|', $parts)), 0, 32);
    }
}
