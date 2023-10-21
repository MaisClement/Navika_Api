<?php

namespace App\Command\Provider;

use App\Command\CommandFunctions;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Helper\ProgressIndicator;

class Clear extends Command
{
    private $entityManager;

    private ProviderRepository $providerRepository;

    public function __construct(EntityManagerInterface $entityManager, ProviderRepository $providerRepository)
    {
        $this->entityManager = $entityManager;

        $this->providerRepository = $providerRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:provider:clear')
            ->setDescription('Add provider')
            ->addArgument('id', InputArgument::OPTIONAL, 'Id')
            ->addOption(
                'all',
                null,
                InputOption::VALUE_OPTIONAL,
                'All',
                true
            );
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $all = $input->getOption('all');
        $db = $this->entityManager->getConnection();

        // --
        $providers = [];

        if ($all == null) {
            $providers = $this->providerRepository->findAll();

        } else if ($id != null) {
            $provider = $this->providerRepository->find($id);
            if ($provider == null) {
                $output->writeln('<warning>Unknow provider</warning>');
                return Command::FAILURE;
            }
            $providers[] = $provider;
        } else {
            return Command::FAILURE;
        }

        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Clearing data...');

        $tables = [
            'attributions',
            'translations',
            'feed_info',
            'frequencies',
            'fare_attributes',
            'fare_rules',
            'stop_times',
            'pathways',
            'transfers',
            'stops',
            'levels',
            'trips',
            'shapes',
            'calendar_dates',
            'calendar',
            'routes',
            'agency'
        ];

        foreach($providers as $provider) {
            foreach ($tables as $table) {
                $progressIndicator->advance();
                $progressIndicator->setMessage("Clearing $table...");
                CommandFunctions::clearProviderDataInTable($db, $table, $provider->getId(), false);
            }
            $this->entityManager->flush();
            $output->writeln('<info>✅ Provider data cleared successfully</info>');
        }
        $progressIndicator->finish('Finished');
        
        return Command::SUCCESS;
    }
}