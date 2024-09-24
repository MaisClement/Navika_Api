<?php

namespace App\Command\RouteDetails;

use App\Repository\RoutesRepository;
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
    private EntityManagerInterface $entityManager;

    private RoutesRepository $routesRepository;

    public function __construct(EntityManagerInterface $entityManager, RoutesRepository $routesRepository)
    {
        $this->entityManager = $entityManager;

        $this->routesRepository = $routesRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:route:details:remove')
            ->setDescription('Remove the details of a given route')
            ->addArgument('id', InputArgument::OPTIONAL, 'id');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');

        // --
        $route = $this->routesRepository->findOneBy(['route_id' => $id]);

        if ($route == null) {
            $output->writeln('<info>The given route canot be found in database</info>');
            return Command::SUCCESS;
        }

        $details = $route->getDetails();

        if (count($details) == 0) {
            $output->writeln('<info>The given route doesn’t have any details</info>');
            return Command::SUCCESS;
        }

        $this->entityManager->remove($details[0]);
        $this->entityManager->flush();

        $output->writeln('<info>Details for the given route have been removed</info>');
        return Command::SUCCESS;
    }
}