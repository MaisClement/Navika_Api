<?php

namespace App\Command\Provider;

use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Show extends Command
{
    private ProviderRepository $providerRepository;
    
    public function __construct(ProviderRepository $providerRepository, )
    {
        $this->providerRepository = $providerRepository;
        
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->setName('app:provider:list')
            ->setDescription('List all provider');
    }
    
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $providers = $this->providerRepository->FindAll();

        foreach($providers as $provider) {
            $output->writeln( '> ' . $provider->getId() . ' - ' . $provider->getName() );
            $output->writeln( '   type: ' . $provider->getType() );
            $output->writeln( '   flag: ' . $provider->getFlag() );
            $output->writeln( '   url: '  . $provider->getUrl() );
            if ($provider->getUpdatedAt() != null) {
                $output->writeln( '   updated: ' . $provider->getUpdatedAt()->format('Y-m-d H:i:s') );
            } else {
                $output->writeln( '   updated: never' );
            }
            $output->writeln( '' );
        }
        
        return Command::SUCCESS;
    }
}
