<?php

namespace App\Command\RouteDetails;

use App\Entity\RouteDetails;
use App\Repository\RoutesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
            ->addArgument('route_id', InputArgument::REQUIRED, 'routeId')
            ->addArgument('vehicule_name', InputArgument::REQUIRED, 'vehiculeName')
            ->addArgument('vehicule_img', InputArgument::REQUIRED, 'vehiculeImg')
            ->addArgument('is_air_conditioned', InputArgument::REQUIRED, 'isAirConditioned')
            ->addArgument('has_power_sockets', InputArgument::REQUIRED, 'hasPowerSockets')
            ->addArgument('is_bike_accesible', InputArgument::REQUIRED, 'isBikeAccesible')
            ->addArgument('is_wheelchair_accesible', InputArgument::REQUIRED, 'isWheelchairAccesible');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $route_id = $input->getArgument('route_id');
        $vehicule_name = $input->getArgument('vehicule_name');
        $vehicule_img = $input->getArgument('vehicule_img');
        $is_air_conditioned = $input->getArgument('is_air_conditioned');
        $has_power_sockets = $input->getArgument('has_power_sockets');
        $is_bike_accesible = $input->getArgument('is_bike_accesible');
        $is_wheelchair_accesible = $input->getArgument('is_wheelchair_accesible');


        $route = $this->routesRepository->findOneBy(['route_id' => $route_id]);

        if ($route == null) {
            $output->writeln('<info>The given route canot be found in database</info>');
            return Command::SUCCESS;
        }

        $details = $route->getDetails();

        if (count($details) > 0) {
            $this->entityManager->remove($details[0]);
            $this->entityManager->flush();
        }
        // ---

        $route_details = new RouteDetails();
        $route_details->setRouteId($route);
        $route_details->setVehiculeName($vehicule_name);
        $route_details->setVehiculeImg($vehicule_img);
        $route_details->setIsAirConditioned($is_air_conditioned);
        $route_details->setHasPowerSockets($has_power_sockets);
        $route_details->setIsBikeAccesible($is_bike_accesible);
        $route_details->setIsWheelchairAccesible($is_wheelchair_accesible);

        $this->entityManager->persist($route_details);
        $this->entityManager->flush();

        $output->writeln('<info>✅ Details added successfully</info>');

        return Command::SUCCESS;
    }
}