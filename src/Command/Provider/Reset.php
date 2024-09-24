<?php

namespace App\Command\Provider;

use App\Repository\ProviderRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Reset extends Command
{
    private EntityManagerInterface $entityManager;

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
            ->setName('app:provider:reset')
            ->setDescription('Reset provider');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $providers = $this->providerRepository->findAll();

        foreach ($providers as $provider) {
            $provider->setFlag('0');
            $provider->setUpdatedAt(new DateTime());
            $this->entityManager->flush();
        }

        $output->writeln('<fg=white;bg=green>           </>');
        $output->writeln('<fg=white;bg=green> Ready ✅  </>');
        $output->writeln('<fg=white;bg=green>           </>');

        return Command::SUCCESS;
    }
}