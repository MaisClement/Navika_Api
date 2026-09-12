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

/**
 * Arrête une instance MOTIS.
 *
 * Refuse par défaut d'arrêter l'instance active, pour qu'une erreur de frappe
 * ne coupe pas l'API.
 */
class Stop extends Command
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
            ->setName('app:motis:stop')
            ->setDescription('Arrête une instance motis')
            ->addArgument('id', InputArgument::REQUIRED, 'Identifiant de l\'instance')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Autorise l\'arrêt de l\'instance active');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $instance = $this->motisInstancesRepository->find($id);

        if ($instance === null) {
            $output->writeln("<error>Instance inconnue : $id</error>");

            return Command::FAILURE;
        }

        if ($instance->isActive() && $input->getOption('force') !== true) {
            $output->writeln("<error>[motis][$id] instance active : l'arrêter couperait l'API. Basculer d'abord (app:motis:deploy) ou passer --force.</error>");

            return Command::FAILURE;
        }

        $pid = $instance->getPid();

        if (!$this->motis->isProcessRunning($pid)) {
            $output->writeln("[motis][$id] déjà arrêtée");

            $instance->setPid(null);
            $instance->setHealthy(false);
            $instance->setState(MotisInstances::STATE_STOPPED);
            $this->entityManager->flush();

            return Command::SUCCESS;
        }

        posix_kill((int) $pid, SIGTERM);

        // On laisse le serveur fermer proprement ses fichiers mappés avant
        // d'employer la manière forte.
        for ($i = 0; $i < 60 && $this->motis->isProcessRunning($pid); $i++) {
            usleep(500000);
        }

        if ($this->motis->isProcessRunning($pid)) {
            $output->writeln("[motis][$id] arrêt propre sans effet, envoi de SIGKILL");
            posix_kill((int) $pid, SIGKILL);

            for ($i = 0; $i < 20 && $this->motis->isProcessRunning($pid); $i++) {
                usleep(500000);
            }
        }

        if ($this->motis->isProcessRunning($pid)) {
            $message = "Impossible d'arrêter le processus $pid";
            $output->writeln("<error>[motis][$id] $message</error>");

            $instance->setLastError($message);
            $this->entityManager->flush();

            return Command::FAILURE;
        }

        $instance->setPid(null);
        $instance->setHealthy(false);
        $instance->setState(MotisInstances::STATE_STOPPED);
        $this->entityManager->flush();

        $output->writeln("<info>[motis][$id] arrêtée ✅</info>");
        $this->logger->log(['message' => "[motis][$id] arrêtée"], 'INFO');

        return Command::SUCCESS;
    }
}
