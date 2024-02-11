<?php

namespace App\Command\Provider;

use App\Entity\Provider;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Add extends Command
{
    private $entityManager;

    private ProviderRepository $providerRepository;

    public function __construct(EntityManagerInterface $entityManager, ProviderRepository $providerRepository, )
    {
        $this->entityManager = $entityManager;

        $this->providerRepository = $providerRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:provider:add')
            ->setDescription('Add a data provider (can be for GTFS or GTFS)')
            ->addArgument('type', InputArgument::REQUIRED, 'Type')
            ->addArgument('id', InputArgument::REQUIRED, 'Id')
            ->addArgument('name', InputArgument::REQUIRED, 'Name')
            ->addArgument('area', InputArgument::REQUIRED, 'Area')
            ->addArgument('url', InputArgument::REQUIRED, 'Url');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $type = $input->getArgument('type');
        $name = $input->getArgument('name');
        $area = $input->getArgument('area');
        $url = $input->getArgument('url');
        $flag = '0';

        // Check if provider is not already registered
        $providers = $this->providerRepository->Find($id);
        if ($providers instanceof \App\Entity\Provider) {
            $output->writeln('<fg=blue>ℹ️ Provider already registered</>');
            return Command::SUCCESS;
        }

        // ---

        $provider = new Provider();
        $provider->setId($id);
        $provider->setType($type);
        $provider->setName($name);
        $provider->setArea($area);
        $provider->setUrl($url);
        $provider->setFlag($flag);

        $this->entityManager->persist($provider);

        $this->entityManager->flush();

        $output->writeln('<info>✅ New provider added successfully</info>');

        return Command::SUCCESS;
    }
}