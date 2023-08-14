<?php

namespace App\Command\GTFS;

use App\Command\CommandFunctions;
use App\Controller\Functions;
use App\Entity\StopRoute;
use App\Entity\Stops;
use App\Repository\ProviderRepository;
use App\Repository\RoutesRepository;
use App\Repository\StationsRepository;
use App\Repository\StopsRepository;
use App\Repository\TownRepository;
use App\Repository\StopRouteRepository;
use App\Repository\TempStopRouteRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use ZipArchive;

class UpdateTown extends Command
{
    private $entityManager;
    private $params;

    private ProviderRepository $providerRepository;
    private StationsRepository $stationsRepository;
    private StopsRepository $stopsRepository;
    private TownRepository $townRepository;
    private RoutesRepository $routesRepository;
    private StopRouteRepository $stopRouteRepository;
    private TempStopRouteRepository $tempStopRouteRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, ProviderRepository $providerRepository, StationsRepository $stationsRepository, StopsRepository $stopsRepository, TownRepository $townRepository, StopRouteRepository $stopRouteRepository, TempStopRouteRepository $tempStopRouteRepository, RoutesRepository $routesRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->providerRepository = $providerRepository;
        $this->stationsRepository = $stationsRepository;
        $this->stopsRepository = $stopsRepository;
        $this->townRepository = $townRepository;
        $this->routesRepository = $routesRepository;
        $this->stopRouteRepository = $stopRouteRepository;
        $this->tempStopRouteRepository = $tempStopRouteRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:town:update')
            ->setDescription('Update town for stop route');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();

        $output->writeln('> Updating stop_toute for Town...');

        $_stops = [];
        $stops = $this->stopRouteRepository->findBy(['town_id' => null]);

        foreach( $stops as $stop ) {
            $stop_id = $stop->getStopId()->getStopId();

            if ( !in_array($stop_id, $_stops) ) {
                $_stops[] = $stop_id;

                $town = $this->townRepository->findTownByCoordinates( $stop->getStopLon(), $stop->getStopLat() );
        
                if ( $town != null ) {
                    Functions::setTownForStopRoute($db, $stop_id, $town);
                    // $stop->setTown( $town );
                    // $this->entityManager->flush();
                } else {
                    echo 'Null' . PHP_EOL;
                }
            }            
        }

        $output->writeln('> Preparing for query...');
        CommandFunctions::generateQueryRoute($db);
        
        return Command::SUCCESS;
    }
}