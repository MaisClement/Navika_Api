<?php

namespace App\Command\RouteDetails;

use App\Entity\RouteDetails;
use App\Repository\RoutesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Add extends Command
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
            ->setName('app:routedetails:add')
            ->setDescription('Add a data provider (can be for GTFS or GTFS)')
            ->addArgument('routeId', InputArgument::REQUIRED, 'routeId')
            ->addArgument('vehiculeName', InputArgument::REQUIRED, 'vehiculeName')
            ->addArgument('vehiculeImg', InputArgument::REQUIRED, 'vehiculeImg')
            ->addArgument('isAirConditioned', InputArgument::REQUIRED, 'isAirConditioned')
            ->addArgument('hasPowerSockets', InputArgument::REQUIRED, 'hasPowerSockets')
            ->addArgument('isBikeAccesible', InputArgument::REQUIRED, 'isBikeAccesible')
            ->addArgument('isWheelchairAccesible', InputArgument::REQUIRED, 'isWheelchairAccesible');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routeId = $input->getArgument('routeId');
        $vehiculeName = $input->getArgument('vehiculeName');
        $vehiculeImg = $input->getArgument('vehiculeImg');
        $isAirConditioned = $input->getArgument('isAirConditioned');
        $hasPowerSockets = $input->getArgument('hasPowerSockets');
        $isBikeAccesible = $input->getArgument('isBikeAccesible');
        $isWheelchairAccesible = $input->getArgument('isWheelchairAccesible');

    // The Routes entity uses snake_case property names (route_id), so criteria must match the property name
    $route = $this->routesRepository->findOneBy(['route_id' => $routeId]);

        if ($route === null) {
            $output->writeln('<info>The given route cannot be found in database</info>');
            return Command::SUCCESS;
        }

        $details = $route->getDetails();

        if (count($details) > 0) {
            $this->entityManager->remove($details[0]);
            $this->entityManager->flush();
        }

        $routeDetails = new RouteDetails();
        $routeDetails->setRouteId($route);
        $routeDetails->setVehiculeName($vehiculeName);
        $routeDetails->setVehiculeImg($vehiculeImg);
        $routeDetails->setIsAirConditioned($isAirConditioned);
        $routeDetails->setHasPowerSockets($hasPowerSockets);
        $routeDetails->setIsBikeAccesible($isBikeAccesible);
        $routeDetails->setIsWheelchairAccesible($isWheelchairAccesible);

        $this->entityManager->persist($routeDetails);
        $this->entityManager->flush();

        $output->writeln('<info>✅ Details added successfully</info>');

        return Command::SUCCESS;
    }
}
