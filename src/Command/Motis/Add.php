<?php

namespace App\Command\Motis;

use App\Entity\MotisInstances;
use App\Repository\MotisInstancesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Helper\ProgressIndicator;
use App\Service\Logger;

class Add extends Command
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
        ->setName('app:motis:add')
        ->setDescription('Add a motis instance')
        ->addArgument('id', InputArgument::REQUIRED, 'id')
        ->addArgument('port', InputArgument::REQUIRED, 'port')
        ->addArgument('state', InputArgument::REQUIRED, 'state');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $port = $input->getArgument('port');
        $state = $input->getArgument('state');
        
        // Check if instance is not already registered
        $instance = $this->motisInstancesRepository->Find($id);
        if ($instance != null) {
            $output->writeln('<fg=blue>ℹ️ Instance already existing</>');
            return Command::SUCCESS;
        }

        $instance = new MotisInstances();
        $instance->setId($id);
        $instance->setPort($port);
        $instance->setState($state);
        
        $this->entityManager->persist($instance);
        $this->entityManager->flush();

        $output->writeln('<info>✅ New instance registered successfully</info>');

        return Command::SUCCESS;
    }
}