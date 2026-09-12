<?php

namespace App\Command\Provider;

use App\Repository\ProviderRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Show extends Command
{
    private ProviderRepository $providerRepository;

    public function __construct(ProviderRepository $providerRepository)
    {
        $this->providerRepository = $providerRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:provider:list')
            ->setDescription('List all providers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $providers = $this->providerRepository->findAll();

        foreach ($providers as $provider) {
            $output->writeln('> ' . $provider->getId() . ' - ' . $provider->getName());
            $output->writeln('   type: ' . $provider->getType());
            $output->writeln('   flag: ' . $provider->getFlag());
            $output->writeln('   url: ' . $provider->getUrl());
            $updatedAt = $provider->getUpdatedAt();
            $output->writeln('   updated: ' . ($updatedAt ? $updatedAt->format('Y-m-d H:i:s') : 'never'));
            $output->writeln('');
        }

        return Command::SUCCESS;
    }
}
