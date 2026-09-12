<?php

namespace App\Command\Motis;

use App\Entity\MotisInstances;
use App\Repository\MotisInstancesRepository;
use App\Service\Logger;
use App\Service\Motis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Contrôle périodique des instances MOTIS (à passer en cron).
 *
 * Remplace la surveillance à l'œil : la commande sonde chaque instance, tient
 * l'état à jour en base — ce dont l'API se sert pour choisir où router — et
 * répare les cas courants :
 *  - l'instance active ne répond plus mais l'autre oui : bascule ;
 *  - l'instance active est tombée et personne ne peut prendre le relais :
 *    redémarrage sur son dernier jeu de données ;
 *  - aucune instance n'est active : promotion de la première qui répond.
 */
class Health extends Command
{
    private EntityManagerInterface $entityManager;
    private MotisInstancesRepository $motisInstancesRepository;
    private Logger $logger;
    private Motis $motis;

    public function __construct(
        EntityManagerInterface $entityManager,
        MotisInstancesRepository $motisInstancesRepository,
        Logger $logger,
        Motis $motis
    ) {
        $this->entityManager = $entityManager;
        $this->motisInstancesRepository = $motisInstancesRepository;
        $this->logger = $logger;
        $this->motis = $motis;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:motis:health')
            ->setDescription('Sonde les instances motis, met l\'état à jour et répare')
            ->addOption('no-repair', null, InputOption::VALUE_NONE, 'Se contente de sonder, sans rien redémarrer ni basculer')
            ->addOption('restart-timeout', null, InputOption::VALUE_REQUIRED, 'Délai d\'attente d\'un redémarrage, en secondes', '1800');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instances = $this->motisInstancesRepository->findAll();

        if ($instances === []) {
            $output->writeln('<comment>Aucune instance enregistrée (app:motis:add)</comment>');

            return Command::SUCCESS;
        }

        $repair = $input->getOption('no-repair') !== true;
        $healthy = [];

        foreach ($instances as $instance) {
            $id = (string) $instance->getId();
            $running = $this->motis->isProcessRunning($instance->getPid());
            $probe = $running ? $this->motis->probe($instance) : ['ok' => false, 'error' => 'processus absent'];

            if ($probe['ok']) {
                $this->motis->markHealthy($instance);
                $healthy[] = $instance;
                $output->writeln(sprintf('[motis][%s] <info>OK</info>%s', $id, $instance->isActive() ? ' (active)' : ''));
                continue;
            }

            $wasHealthy = $instance->isHealthy();
            $this->motis->markUnhealthy($instance, (string) $probe['error']);

            $output->writeln(sprintf('[motis][%s] <error>KO : %s</error>', $id, $probe['error']));

            // On ne journalise en alerte que le passage de sain à défaillant,
            // pour ne pas noyer les logs quand une instance est arrêtée depuis
            // longtemps (ce qui est le cas normal de l'instance de réserve).
            if ($wasHealthy) {
                $this->logger->log(['message' => "[motis][$id] devenue indisponible : " . $probe['error']], 'WARN');
            }
        }

        if (!$repair) {
            return $healthy === [] ? Command::FAILURE : Command::SUCCESS;
        }

        return $this->repair($instances, $healthy, (int) $input->getOption('restart-timeout'), $output);
    }

    /**
     * @param MotisInstances[] $instances
     * @param MotisInstances[] $healthy
     */
    private function repair(array $instances, array $healthy, int $timeout, OutputInterface $output): int
    {
        $active = null;
        foreach ($instances as $instance) {
            if ($instance->isActive()) {
                $active = $instance;
                break;
            }
        }

        // Cas nominal.
        if ($active !== null && $active->isHealthy()) {
            return Command::SUCCESS;
        }

        // Une autre instance répond : bascule immédiate, c'est le chemin le
        // plus rapide pour retrouver un service.
        foreach ($healthy as $candidate) {
            if ($active !== null && $candidate->getId() === $active->getId()) {
                continue;
            }

            $output->writeln(sprintf('> Bascule sur %s (instance saine)', $candidate->getId()));
            $this->motis->promote($candidate);
            $this->logger->log([
                'message' => sprintf(
                    '[motis][health] bascule automatique sur %s%s',
                    $candidate->getId(),
                    $active !== null ? ' (' . $active->getId() . ' indisponible)' : ''
                ),
            ], 'WARN');

            return Command::SUCCESS;
        }

        // Plus personne ne répond : on tente de relancer une instance sur son
        // dernier jeu de données connu.
        $candidate = $active;
        if ($candidate === null || $candidate->getDataPath() === null) {
            foreach ($instances as $instance) {
                if ($instance->getDataPath() !== null) {
                    $candidate = $instance;
                    break;
                }
            }
        }

        if ($candidate === null || $candidate->getDataPath() === null) {
            $output->writeln('<error>Aucune instance ne dispose d\'un jeu de données : lancer app:motis:deploy</error>');
            $this->logger->log(['message' => '[motis][health] aucune instance disponible et aucun jeu de données à servir'], 'ERROR');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('> Redémarrage de %s sur %s', $candidate->getId(), $candidate->getDataPath()));
        $this->logger->log(['message' => '[motis][health] redémarrage de ' . $candidate->getId()], 'WARN');

        $started = $this->getApplication()->doRun(new ArrayInput([
            'command'   => 'app:motis:start',
            'id'        => $candidate->getId(),
            '--timeout' => (string) $timeout,
            '--restart' => true,
        ]), $output);

        if ($started !== Command::SUCCESS) {
            $this->logger->log(['message' => '[motis][health] redémarrage de ' . $candidate->getId() . ' en échec'], 'ERROR');

            return Command::FAILURE;
        }

        if (!$candidate->isActive()) {
            $this->motis->promote($candidate);
        }

        return Command::SUCCESS;
    }
}
