<?php

namespace App\Command;

use App\Command\CommandFunctions;
use App\Controller\Functions;
use App\Entity\StopRoute;
use App\Entity\Stops;
use App\Entity\StopTown;
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
use Symfony\Component\Console\Helper\ProgressBar;
use App\Controller\PointLocation;

class Town_Update extends Command
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

        // ---

        ProgressBar::setFormatDefinition('custom', '%percent%% [%bar%] %elapsed% - %remaining% | %message%');
        
        // ---

        // $towns = $this->townRepository->findBy(['town_id' => '56121']);
        $towns = $this->townRepository->findAll();
        $stops = $this->stopsRepository->findBy(['town_id' => null, 'location_type' => '1']);

        $progressBar = new ProgressBar($output, 100);
        $progressBar->setFormat('custom');
        $progressBar->start();
        $progressBar->setMessage('Looking for town...');

        $progressBar->setMaxSteps( count($towns) * count($stops) );

        $pointLocation = new PointLocation();

        foreach( $towns as $town ) {
            $polygon = $town->getTownPolygon()->toArray()[0];
            
            foreach( $stops as $stop ) {
                $progressBar->advance();
                try {
                    $point = [$stop->getStopLon(), $stop->getStopLat()];

                    $res = $pointLocation->pointInPolygon($point, $polygon, false);

                    if ($res) {
                        $stopTown = new StopTown();
                        $stopTown->setStopId( $stop );
                        $stopTown->setTownId( $town );
                        $this->entityManager->persist($stopTown);
                        $this->entityManager->flush();
                    }
                } catch (\Exception $e) {
                    echo $e;
                }                
            }
        }
        $this->entityManager->flush();
        
        $output->writeln('> Preparing for query...');
        CommandFunctions::generateQueryRoute($db);
        
        return Command::SUCCESS;
    }
}