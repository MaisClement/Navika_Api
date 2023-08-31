<?php

namespace App\Command\Provider;

use App\Command\CommandFunctions;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Helper\ProgressIndicator;

class Clear extends Command
{
    private ProviderRepository $providerRepository;
    private \Doctrine\ORM\EntityManagerInterface $entityManager;
    private \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $params;
    
    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, ProviderRepository $providerRepository)
    {
        $this->providerRepository = $providerRepository;
        $this->entityManager = $entityManager;
        $this->params = $params;
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->setName('app:provider:clear')
            ->setDescription('Add provider')
            ->addArgument('id', InputArgument::REQUIRED, 'Id');
    }
    
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $db = $this->entityManager->getConnection();

        $provider = $this->providerRepository->find( $id );
        if ($provider == null) {
            $output->writeln('<warning>Unknow provider</warning>');
        
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

        foreach( $tables as $table ) {
            $progressIndicator->advance();
            $progressIndicator->setMessage("Clearing $table...");
            CommandFunctions::clearProviderDataInTable($db, $table, $id);
            $progressIndicator->advance();
        }

        $progressIndicator->finish('Finished');

        // ---
        
        $this->entityManager->flush();
        
        $output->writeln('<info>✅ Provider data cleared successfully</info>');
        
        return Command::SUCCESS;
    }
}
