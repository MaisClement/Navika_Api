<?php

namespace App\Command\Motis;

use App\Entity\MotisInstances;
use App\Repository\MotisInstancesRepository;
use App\Repository\ProviderRepository;
use App\Service\Logger;
use App\Service\Motis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * Déploiement blue-green d'un nouveau jeu de données MOTIS.
 *
 * Le principe : ne jamais toucher à l'instance qui sert le trafic. On construit
 * les données à part, on démarre l'instance de réserve dessus, on vérifie
 * qu'elle calcule réellement des itinéraires, et seulement alors on bascule le
 * drapeau `active`. La bascule est une écriture en base, instantanée ; l'API
 * suit sans redémarrage.
 *
 * Si une étape échoue, on s'arrête : l'instance en service n'a pas bougé et
 * continue de répondre. C'est ce qui permet de lancer la commande sans
 * surveillance depuis app:gtfs:update.
 */
class Deploy extends Command
{
    private EntityManagerInterface $entityManager;
    private MotisInstancesRepository $motisInstancesRepository;
    private ProviderRepository $providerRepository;
    private ParameterBagInterface $params;
    private Logger $logger;
    private Motis $motis;

    public function __construct(
        EntityManagerInterface $entityManager,
        MotisInstancesRepository $motisInstancesRepository,
        ProviderRepository $providerRepository,
        ParameterBagInterface $params,
        Logger $logger,
        Motis $motis
    ) {
        $this->entityManager = $entityManager;
        $this->motisInstancesRepository = $motisInstancesRepository;
        $this->providerRepository = $providerRepository;
        $this->params = $params;
        $this->logger = $logger;
        $this->motis = $motis;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:motis:deploy')
            ->setDescription('Construit un nouveau jeu de données et bascule dessus (blue-green)')
            ->addOption('instance', 'i', InputOption::VALUE_REQUIRED, 'Instance cible (défaut : celle qui n\'est pas active)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Redéploie même si les GTFS n\'ont pas changé')
            ->addOption('skip-import', null, InputOption::VALUE_NONE, 'Réutilise le dernier jeu de données construit')
            ->addOption('keep', null, InputOption::VALUE_REQUIRED, 'Nombre de jeux de données à conserver', '2')
            ->addOption('start-timeout', null, InputOption::VALUE_REQUIRED, 'Délai d\'attente du démarrage, en secondes', '1800');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lock = $this->acquireLock();
        if ($lock === null) {
            $output->writeln('<comment>Un déploiement est déjà en cours, abandon.</comment>');

            return Command::SUCCESS;
        }

        try {
            return $this->deploy($input, $output);
        } finally {
            $this->releaseLock($lock);
        }
    }

    private function deploy(InputInterface $input, OutputInterface $output): int
    {
        $target = $this->pickTarget($input, $output);

        if ($target === null) {
            return Command::FAILURE;
        }

        $hash = $this->motis->computeDataHash();
        $active = $this->motisInstancesRepository->findActive();

        if ($input->getOption('force') !== true
            && $active !== null
            && $active->getDataHash() === $hash
            && $active->isHealthy()
        ) {
            $output->writeln('<info>Les données servies sont à jour, rien à déployer ✅</info>');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Instance cible : <comment>%s</comment> (port %s)', $target->getId(), $target->getPort()));

        // 1. Construction du jeu de données, à l'écart de l'instance en service.
        if ($input->getOption('skip-import') === true) {
            $dataPath = $this->getLatestDataset();

            if ($dataPath === null) {
                $output->writeln('<error>Aucun jeu de données existant à réutiliser</error>');

                return Command::FAILURE;
            }

            $output->writeln("Réutilisation du jeu de données $dataPath");
        } else {
            $dataPath = $this->import($target, $output);

            if ($dataPath === null) {
                return Command::FAILURE;
            }
        }

        $target->setDataPath($dataPath);
        $target->setDataHash($hash);
        $this->entityManager->flush();

        // 2. Démarrage de l'instance de réserve sur ces données.
        $output->writeln('> Démarrage de l\'instance de réserve...');
        $started = $this->getApplication()->doRun(new ArrayInput([
            'command'   => 'app:motis:start',
            'id'        => $target->getId(),
            '--data'    => $dataPath,
            '--timeout' => $input->getOption('start-timeout'),
            '--restart' => true,
        ]), $output);

        if ($started !== Command::SUCCESS) {
            $this->fail($target, 'démarrage en échec', $output);

            return Command::FAILURE;
        }

        // 3. Vérification qu'elle calcule vraiment des itinéraires : un serveur
        //    qui répond mais dont l'horaire est vide ne doit pas prendre le
        //    trafic.
        $output->writeln('> Vérification du calcul d\'itinéraire...');
        $routing = $this->motis->probeRouting($target);

        if (!$routing['ok']) {
            $this->fail($target, 'sonde d\'itinéraire en échec : ' . $routing['error'], $output);

            $output->writeln('<error>L\'instance en service n\'a pas été touchée.</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('    %d itinéraire(s) sur le trajet témoin ✅', $routing['itineraries']));

        // 4. Bascule. À partir d'ici l'API interroge la nouvelle instance.
        $previous = $active;
        $this->motis->promote($target);
        $output->writeln(sprintf('<info>Bascule effectuée sur %s ✅</info>', $target->getId()));
        $this->logger->log([
            'message' => sprintf(
                '[motis][deploy] bascule sur %s (données %s)%s',
                $target->getId(),
                $dataPath,
                $previous !== null ? ', ancienne instance : ' . $previous->getId() : ''
            ),
        ], 'INFO');

        // 5. Extinction de l'ancienne, une fois la nouvelle en service.
        if ($previous !== null && $previous->getId() !== $target->getId()) {
            $output->writeln('> Arrêt de l\'ancienne instance ' . $previous->getId() . '...');
            $this->getApplication()->doRun(new ArrayInput([
                'command' => 'app:motis:stop',
                'id'      => $previous->getId(),
            ]), $output);
        }

        $this->pruneDatasets((int) $input->getOption('keep'), $output);

        return Command::SUCCESS;
    }

    private function pickTarget(InputInterface $input, OutputInterface $output): ?MotisInstances
    {
        $id = $input->getOption('instance');

        if ($id !== null) {
            $target = $this->motisInstancesRepository->find($id);

            if ($target === null) {
                $output->writeln("<error>Instance inconnue : $id</error>");

                return null;
            }

            if ($target->isActive()) {
                $output->writeln("<error>$id est l'instance active : déployer dessus couperait l'API</error>");

                return null;
            }

            return $target;
        }

        $target = $this->motisInstancesRepository->findStandby();

        if ($target === null) {
            // Première mise en service : aucune instance n'est encore active.
            $target = $this->motisInstancesRepository->findOneBy([], ['id' => 'ASC']);
        }

        if ($target === null) {
            $output->writeln('<error>Aucune instance enregistrée. Lancer app:motis:add au préalable.</error>');
        }

        return $target;
    }

    /**
     * Construit le jeu de données dans un répertoire horodaté.
     *
     * On n'écrase jamais un jeu existant : l'instance en service continue de
     * lire le sien pendant toute la construction.
     */
    private function import(MotisInstances $target, OutputInterface $output): ?string
    {
        $configFile = $this->writeConfig($target, $output);
        $dataPath = $this->getDatasetsPath() . '/' . date('Ymd-His');

        $binary = rtrim((string) $this->params->get('motis_path'), '/') . '/build/motis';

        if (!is_file($binary)) {
            $output->writeln("<error>Binaire motis introuvable : $binary</error>");

            return null;
        }

        $target->setState(MotisInstances::STATE_BUILDING);
        $this->entityManager->flush();

        $output->writeln("> Import motis vers $dataPath (peut durer longtemps)...");
        $this->logger->log(['message' => "[motis][deploy] import vers $dataPath"], 'INFO');

        $process = new Process(
            [$binary, 'import', '-c', $configFile, '-d', $dataPath],
            dirname($binary)
        );
        $process->setTimeout(null);
        $process->run(static function ($type, $buffer) use ($output): void {
            $output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $message = 'import motis en échec : ' . trim(mb_substr(($process->getErrorOutput() !== '' ? $process->getErrorOutput() : $process->getOutput()), -800));
            $this->fail($target, $message, $output);

            return null;
        }

        if (!is_file($dataPath . '/config.yml')) {
            $this->fail($target, "l'import n'a pas produit $dataPath/config.yml", $output);

            return null;
        }

        $output->writeln('<info>Import terminé ✅</info>');

        return $dataPath;
    }

    /**
     * Écrit le config.yml d'import à partir du modèle et des providers actifs.
     */
    private function writeConfig(MotisInstances $target, OutputInterface $output): string
    {
        $template = rtrim((string) $this->params->get('kernel.project_dir'), '/') . '/motis_default_config.yml';
        $config = Yaml::parseFile($template);

        $gtfsPath = rtrim((string) $this->params->get('gtfs_path'), '/');
        $datasets = [];

        foreach ($this->providerRepository->findBy(['type' => 'tc']) as $provider) {
            if ($provider->getGtfsUrl() === null) {
                continue;
            }

            $id = (string) $provider->getId();
            $file = $gtfsPath . '/' . $id . '_gtfs.zip';

            if (!is_file($file)) {
                $output->writeln("    <comment>GTFS absent, ignoré : $file</comment>");
                continue;
            }

            $key = str_replace(':', '-', strtolower($id));
            $datasets[$key] = ['path' => $file];
        }

        if ($datasets === []) {
            throw new \RuntimeException('Aucun GTFS disponible pour construire le jeu de données');
        }

        $config['timetable']['datasets'] = $datasets;
        $config['server']['port'] = (string) $target->getPort();

        $file = $this->getDatasetsPath() . '/config-' . $target->getId() . '.yml';
        file_put_contents($file, Yaml::dump($config, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $output->writeln(sprintf('    %d jeu(x) GTFS, configuration écrite dans %s', count($datasets), $file));

        return $file;
    }

    private function fail(MotisInstances $instance, string $message, OutputInterface $output): void
    {
        $output->writeln("<error>[motis][deploy] $message</error>");

        $instance->setState(MotisInstances::STATE_FAILED);
        $instance->setHealthy(false);
        $instance->setLastError($message);
        $this->entityManager->flush();

        $this->logger->log(['message' => "[motis][deploy][{$instance->getId()}] $message"], 'ERROR');
    }

    private function getDatasetsPath(): string
    {
        $path = rtrim((string) $this->params->get('motis_path'), '/') . '/datasets';

        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        return $path;
    }

    /**
     * Jeu de données le plus récent qui n'est pas déjà servi par une instance
     * vivante — on ne peut pas en faire servir deux à la fois.
     */
    private function getLatestDataset(): ?string
    {
        $inUse = [];
        foreach ($this->motisInstancesRepository->findAll() as $instance) {
            if ($instance->getDataPath() !== null && $this->motis->isProcessRunning($instance->getPid())) {
                $inUse[$instance->getDataPath()] = true;
            }
        }

        foreach (array_reverse($this->listDatasets()) as $dataset) {
            if (!isset($inUse[$dataset])) {
                return $dataset;
            }
        }

        return null;
    }

    /**
     * @return string[] chemins des jeux de données, du plus ancien au plus récent
     */
    private function listDatasets(): array
    {
        // glob() renvoie false en cas d'erreur, d'ou le repli sur un tableau vide.
        $entries = glob($this->getDatasetsPath() . '/*');

        $datasets = array_filter(
            $entries === false ? [] : $entries,
            static fn(string $path): bool => is_dir($path) && is_file($path . '/config.yml')
        );

        sort($datasets);

        return array_values($datasets);
    }

    /**
     * Supprime les vieux jeux de données, sans jamais toucher à ceux qu'une
     * instance sert encore.
     */
    private function pruneDatasets(int $keep, OutputInterface $output): void
    {
        $inUse = [];
        foreach ($this->motisInstancesRepository->findAll() as $instance) {
            if ($instance->getDataPath() !== null) {
                $inUse[$instance->getDataPath()] = true;
            }
        }

        $datasets = $this->listDatasets();
        $removable = array_slice($datasets, 0, max(0, count($datasets) - max(1, $keep)));

        foreach ($removable as $dataset) {
            if (isset($inUse[$dataset])) {
                continue;
            }

            $output->writeln("> Suppression de l'ancien jeu de données $dataset");
            $process = new Process(['rm', '-rf', $dataset]);
            $process->setTimeout(600);
            $process->run();
        }
    }

    /**
     * Verrou de fichier : évite qu'un déploiement déclenché par le cron GTFS
     * n'en chevauche un autre.
     *
     * @return resource|null
     */
    private function acquireLock()
    {
        $file = sys_get_temp_dir() . '/navika/motis_deploy.lock';

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        $handle = fopen($file, 'c');

        if ($handle === false) {
            return null;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        return $handle;
    }

    /**
     * @param resource $lock
     */
    private function releaseLock($lock): void
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
