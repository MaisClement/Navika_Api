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
            ->addArgument('id', InputArgument::REQUIRED, 'id')
            ->addOption(
                'clear-data',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Skip data deletion',
                true
            );
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $i = $input->getOption('clear-data');

        $provider = $this->providerRepository->find($id);
        if ($provider == null) {
            $output->writeln('<error>Unknow provider</error>');
            return Command::FAILURE;
        }

        if ($i == true) {
            $input = new ArrayInput([
                'command' => 'app:provider:clear',
                'id' => $id,
            ]);
            $returnCode = $this->getApplication()->doRun($input, $output);
        }

        $this->entityManager->remove($provider);

        $this->entityManager->flush();

        $output->writeln('<info>✅ Provider removed successfully</info>');

        return Command::SUCCESS;
    }
}