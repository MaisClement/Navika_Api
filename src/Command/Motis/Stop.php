<?php

namespace App\Command\Motis;

use App\Entity\MotisInstances;
use App\Repository\MotisInstancesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use App\Service\Logger;

class Stop extends Command
{
    private EntityManagerInterface $entityManager;

    private MotisInstancesRepository $motisInstancesRepository;

    private ParameterBagInterface $params;
    private Logger $logger;

    public function __construct(EntityManagerInterface $entityManager, MotisInstancesRepository $motisInstancesRepository, ParameterBagInterface $params,  Logger $logger)
    {
        $this->entityManager = $entityManager;

        $this->motisInstancesRepository = $motisInstancesRepository;

        $this->params = $params;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
        ->setName('app:motis:stop')
        ->setDescription('Stop a motis instance')
        ->addArgument('id', InputArgument::REQUIRED, 'id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        
        // Check if instance is not already registered
        $instance = $this->motisInstancesRepository->Find($id);
        if ($instance == null) {
           $output->writeln('<warning>Unknow instance id</warning>');
            return Command::FAILURE;
        }
        
        $pid = $instance->getPid();

        if ($pid == null) {
            $this->logger->log(['message' => '[motis] Instance is not running.']);
            $output->writeln('[motis] Instance is not running.');
            
            $instance->setState("unknown");    // "unknown" "stoped" "killed" "starting" "running"
            $this->entityManager->flush();

            return Command::SUCCESS;
        }

        if (!$this->isRunning($pid)) {
            $this->logger->log(['message' => '[motis] Instance is not running.']);
            $output->writeln('[motis] Instance is not running.');
            
            $instance->setPid(null);
            $instance->setState("killed");    // "unknown" "stoped" "killed" "starting" "running"
            $this->entityManager->flush();

            return Command::SUCCESS;
        }

        if ($pid) {
            $process = new Process(['kill', $pid]);
            $process->run();

            if (!$process->isSuccessful()) {
                $output->writeln('Unable to stop Motis');
                $this->logger->log(['message' => '[motis]['. $id .'] Unable to stop Motis']);
                return Command::FAILURE;
            }

            $output->writeln('Motis successfully stopped');
            $this->logger->log(['message' => '[motis]['. $id .'] Motis successfully stopped']);

            $instance->setPid(null);
            $instance->setState("stoped");    // "unknown" "stoped" "killed" "starting" "running"
            $this->entityManager->flush();

            return Command::SUCCESS;
        }
        return Command::FAILURE;
    }

    private function isRunning($pid): bool
    {
        try {
            $result = shell_exec(sprintf("ps %d", $pid));
            if (count(preg_split("/\n/", $result)) > 2) {
                return true;
            }
        } catch (Exception $e) {}

        return false;
    }
}