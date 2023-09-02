<?php

namespace App\Command\Provider;

use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Remove extends Command
{
    private $entityManager;
    private $params;

    private ProviderRepository $providerRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, ProviderRepository $providerRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->providerRepository = $providerRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:provider:remove')
            ->setDescription('Add provider')
            ->addArgument('id', InputArgument::OPTIONAL, 'id')
            ->addOption(
                'skip-clear',
                null,
                InputOption::VALUE_OPTIONAL,
                'Skip data deletion',
                true
            )
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
        $i = $input->getOption('skip-clear');

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

        foreach($providers as $provider) {
            if ($i == true) {
                $input = new ArrayInput([
                    'command' => 'app:provider:clear',
                    'id' => $provider->getId(),
                ]);
                $returnCode = $this->getApplication()->doRun($input, $output);
            }

            $this->entityManager->remove($provider);
            $this->entityManager->flush();

            $output->writeln('<info>✅ Provider removed successfully</info>');
        }

        return Command::SUCCESS;
    }
}