<?php

namespace App\Command\Motis;

use App\Entity\MotisInstances;
use App\Repository\MotisInstancesRepository;
use App\Service\Logger;
use App\Service\Motis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Démarre une instance MOTIS et attend qu'elle réponde vraiment.
 *
 * La commande ne rend la main qu'une fois la sonde HTTP satisfaite : appelée
 * depuis app:motis:deploy, elle garantit qu'on ne bascule jamais la production
 * sur un serveur qui n'a pas fini de charger.
 */
class Start extends Command
{
    private EntityManagerInterface $entityManager;
    private MotisInstancesRepository $motisInstancesRepository;
    private ParameterBagInterface $params;
    private Logger $logger;
    private Motis $motis;

    public function __construct(
        EntityManagerInterface $entityManager,
        MotisInstancesRepository $motisInstancesRepository,
        ParameterBagInterface $params,
        Logger $logger,
        Motis $motis
    ) {
        $this->entityManager = $entityManager;
        $this->motisInstancesRepository = $motisInstancesRepository;
        $this->params = $params;
        $this->logger = $logger;
        $this->motis = $motis;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:motis:start')
            ->setDescription('Démarre une instance motis et attend qu\'elle réponde')
            ->addArgument('id', InputArgument::REQUIRED, 'Identifiant de l\'instance (blue, green...)')
            ->addOption('data', null, InputOption::VALUE_REQUIRED, 'Répertoire de données à servir (défaut : celui enregistré pour l\'instance)')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Délai d\'attente du démarrage, en secondes', '900')
            ->addOption('restart', 'r', InputOption::VALUE_NONE, 'Arrête l\'instance si elle tourne déjà');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $instance = $this->motisInstancesRepository->find($id);

        if ($instance === null) {
            $output->writeln("<error>Instance inconnue : $id</error>");

            return Command::FAILURE;
        }

        $running = $this->motis->isProcessRunning($instance->getPid());

        if ($running && $input->getOption('restart') !== true) {
            $output->writeln("[motis][$id] déjà démarrée (pid " . $instance->getPid() . ')');

            return $this->waitUntilReady($instance, (int) $input->getOption('timeout'), $output);
        }

        if ($running) {
            $output->writeln("[motis][$id] arrêt de l'instance en cours...");
            posix_kill((int) $instance->getPid(), SIGTERM);

            for ($i = 0; $i < 60 && $this->motis->isProcessRunning($instance->getPid()); $i++) {
                usleep(500000);
            }
        }

        $dataPath = $input->getOption('data') ?? $instance->getDataPath();

        if ($dataPath === null) {
            // Rétrocompatibilité avec l'ancienne disposition : le répertoire
            // data/ situé dans le dossier de l'instance.
            $dataPath = $this->getInstancePath($id) . '/data';
        }

        if (!is_dir($dataPath) || !is_file($dataPath . '/config.yml')) {
            $message = "Répertoire de données inutilisable : $dataPath";
            $output->writeln("<error>$message</error>");

            $instance->setState(MotisInstances::STATE_FAILED);
            $instance->setHealthy(false);
            $instance->setLastError($message);
            $this->entityManager->flush();

            return Command::FAILURE;
        }

        // Deux instances ne doivent jamais servir le même répertoire : elles
        // ouvriraient les mêmes fichiers de données, et enforcePort ci-dessous
        // réécrirait le port de celle qui tourne déjà.
        foreach ($this->motisInstancesRepository->findAll() as $other) {
            if ($other->getId() === $id || $other->getDataPath() !== $dataPath) {
                continue;
            }

            if (!$this->motis->isProcessRunning($other->getPid())) {
                continue;
            }

            $message = sprintf('Jeu de données %s déjà servi par l\'instance %s', $dataPath, $other->getId());
            $output->writeln("<error>$message</error>");

            $instance->setState(MotisInstances::STATE_FAILED);
            $instance->setHealthy(false);
            $instance->setLastError($message);
            $this->entityManager->flush();

            return Command::FAILURE;
        }

        // Le port est porté par le config.yml du jeu de données : on le remet en
        // cohérence avec l'instance, pour qu'un répertoire construit pour une
        // instance et réattribué à l'autre écoute bien au bon endroit.
        $this->enforcePort($dataPath, (string) $instance->getPort());

        $binary = $this->getBinary($id);
        if ($binary === null) {
            $output->writeln('<error>Binaire motis introuvable</error>');

            return Command::FAILURE;
        }

        $logDirectory = sys_get_temp_dir() . '/navika';
        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0777, true);
        }
        $log = $logDirectory . '/motis_' . $id . '.log';
        file_put_contents($log, '');

        $instance->setState(MotisInstances::STATE_STARTING);
        $instance->setHealthy(false);
        $instance->setPid(null);
        $instance->setDataPath($dataPath);
        $instance->setStartedAt(new \DateTime());
        $this->entityManager->flush();

        // setsid détache le serveur de la session du cron ou du shell qui lance
        // la commande : il survit à la fin de celle-ci.
        $command = sprintf(
            'cd %s && setsid nohup %s server -d %s > %s 2>&1 < /dev/null & echo $!',
            escapeshellarg(dirname($binary)),
            escapeshellarg($binary),
            escapeshellarg($dataPath),
            escapeshellarg($log)
        );

        $pid = trim((string) shell_exec($command));

        if (!ctype_digit($pid)) {
            $message = 'Impossible de récupérer le pid du serveur motis';
            $output->writeln("<error>$message</error>");

            $instance->setState(MotisInstances::STATE_FAILED);
            $instance->setLastError($message);
            $this->entityManager->flush();

            return Command::FAILURE;
        }

        $instance->setPid($pid);
        $this->entityManager->flush();

        $output->writeln("[motis][$id] démarrage (pid $pid, données $dataPath)");
        $this->logger->log(['message' => "[motis][$id] démarrage, pid $pid, données $dataPath"], 'INFO');

        return $this->waitUntilReady($instance, (int) $input->getOption('timeout'), $output);
    }

    /**
     * Attend que l'instance réponde à la sonde HTTP.
     *
     * On surveille en parallèle la vie du processus : s'il meurt, inutile
     * d'attendre la fin du délai, on remonte l'erreur avec la fin du journal.
     */
    private function waitUntilReady(MotisInstances $instance, int $timeout, OutputInterface $output): int
    {
        $id = (string) $instance->getId();
        $log = sys_get_temp_dir() . '/navika/motis_' . $id . '.log';
        $deadline = time() + $timeout;

        $output->writeln("[motis][$id] attente de disponibilité (max {$timeout}s)...");

        while (time() < $deadline) {
            if (!$this->motis->isProcessRunning($instance->getPid())) {
                $message = 'Le processus motis s\'est arrêté au démarrage : ' . $this->tailLog($log);
                $output->writeln("<error>[motis][$id] $message</error>");

                $instance->setState(MotisInstances::STATE_FAILED);
                $instance->setHealthy(false);
                $instance->setPid(null);
                $instance->setLastError($message);
                $this->entityManager->flush();

                $this->logger->log(['message' => "[motis][$id] échec du démarrage : $message"], 'ERROR');

                return Command::FAILURE;
            }

            if ($this->motis->probe($instance)['ok']) {
                $instance->setState(MotisInstances::STATE_RUNNING);
                $instance->setHealthy(true);
                $instance->setLastError(null);
                $this->entityManager->flush();

                $output->writeln("<info>[motis][$id] prête ✅</info>");
                $this->logger->log(['message' => "[motis][$id] prête"], 'INFO');

                return Command::SUCCESS;
            }

            sleep(2);
        }

        $message = "Délai de démarrage dépassé ({$timeout}s) : " . $this->tailLog($log);
        $output->writeln("<error>[motis][$id] $message</error>");

        $instance->setState(MotisInstances::STATE_FAILED);
        $instance->setHealthy(false);
        $instance->setLastError($message);
        $this->entityManager->flush();

        $this->logger->log(['message' => "[motis][$id] $message"], 'ERROR');

        return Command::FAILURE;
    }

    private function tailLog(string $log, int $length = 500): string
    {
        if (!is_file($log)) {
            return 'journal indisponible';
        }

        $content = (string) file_get_contents($log);

        $tail = trim(mb_substr($content, -$length));

        return $tail === '' ? 'journal vide' : $tail;
    }

    private function getInstancePath(string $id): string
    {
        return rtrim((string) $this->params->get('motis_path'), '/') . '/' . $id;
    }

    private function getBinary(string $id): ?string
    {
        foreach ([$this->getInstancePath($id) . '/motis', rtrim((string) $this->params->get('motis_path'), '/') . '/build/motis'] as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Force le port du serveur dans le config.yml d'un jeu de données.
     */
    private function enforcePort(string $dataPath, string $port): void
    {
        $file = $dataPath . '/config.yml';
        $config = (string) file_get_contents($file);

        $updated = preg_replace('/^(\s*port:\s*)\S+/m', '${1}' . $port, $config, 1);

        if ($updated !== null && $updated !== $config) {
            file_put_contents($file, $updated);
        }
    }
}
