<?php

namespace App\Command\GTFS;

use App\Command\CommandFunctions;
use App\Controller\Functions;
use App\Entity\StopRoute;
use App\Entity\Stops;
use App\Repository\ProviderRepository;
use App\Repository\AgencyRepository;
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
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Helper\ProgressBar;

class GTFS_StopArea extends Command
{
    private $entityManager;
    private $params;

    private ProviderRepository $providerRepository;
    private StationsRepository $stationsRepository;
    private StopsRepository $stopsRepository;
    private TownRepository $townRepository;
    private RoutesRepository $routesRepository;
    private AgencyRepository $agencyRepository;
    private StopRouteRepository $stopRouteRepository;
    private TempStopRouteRepository $tempStopRouteRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, ProviderRepository $providerRepository, StationsRepository $stationsRepository, StopsRepository $stopsRepository, TownRepository $townRepository, StopRouteRepository $stopRouteRepository, TempStopRouteRepository $tempStopRouteRepository, RoutesRepository $routesRepository, AgencyRepository $agencyRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->providerRepository = $providerRepository;
        $this->stationsRepository = $stationsRepository;
        $this->stopsRepository = $stopsRepository;
        $this->townRepository = $townRepository;
        $this->routesRepository = $routesRepository;
        $this->agencyRepository = $agencyRepository;
        $this->stopRouteRepository = $stopRouteRepository;
        $this->tempStopRouteRepository = $tempStopRouteRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:gtfs:stoparea')
            ->setDescription('Update gtfs');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();

        // --
        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Generate Stop Area...');

        // ----

        $s = [];
        $stops = $this->stopsRepository->FindBy(['location_type' => '0', 'parent_station' => null]);

        foreach ($stops as $stop) {
            $progressIndicator->advance();
            $id = $stop->getProviderId()->getId() . $stop->getStopName();

            if (!isset($s[$id])) {
                $s[$id] = array(
                    'provider_id' => $stop->getProviderId(),
                    'stop_id' => 'ADMIN:' . $stop->getStopId(),
                    'stop_code' => $stop->getStopCode() ?? '',
                    'stop_name' => $stop->getStopName(),
                    'stop_lat' => $stop->getStopLat(),
                    'stop_lon' => $stop->getStopLon(),
                    'stops' => array(),
                );
            }
            $s[$id]['stops'][] = $stop->getStopId();
        }

        foreach ($s as $stop) {
            $progressIndicator->advance();
            $stp = new Stops();
            $stp->setProviderId($stop['provider_id']);
            $stp->setStopId($stop['stop_id']);
            $stp->setStopCode($stop['stop_code']);
            $stp->setStopName($stop['stop_name']);
            $stp->setStopLat($stop['stop_lat']);
            $stp->setStopLon($stop['stop_lon']);
            $stp->setLocationType('1');
            $stp->setWheelchairBoarding('0');

            $this->entityManager->persist($stp);

            foreach ($stop['stops'] as $child_stop) {
                $el = $this->stopsRepository->FindOneBy(['stop_id' => $child_stop]);

                $el->setParentStation($stop['stop_id']);

                $this->entityManager->persist($el);
            }
        }
        $this->entityManager->flush();

        // Loader
        $progressIndicator->finish('✅ OK');

        return Command::SUCCESS;
    }
}