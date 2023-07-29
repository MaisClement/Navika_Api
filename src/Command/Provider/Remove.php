<?php

namespace App\Command\Provider;

use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Remove extends Command
{
    private ProviderRepository $providerRepository;
    private $entityManager;
    private $params;
    
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
            ->setName('app:provider:remove')
            ->setDescription('Add provider')
            ->addArgument('id', InputArgument::REQUIRED, 'Id');
    }
    
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');

        $provider = $this->providerRepository->find( $id );
        $this->entityManager->remove($provider);
        
        $this->entityManager->flush();
        
        $output->writeln('<info>✅ Provider removed successfully</info>');
        
        return Command::SUCCESS;
    }
}
