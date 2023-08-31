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

class GTFS_StopRoute extends Command
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
            ->setName('app:gtfs:stoproute')
            ->setDescription('Update gtfs');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();
        
        // --
        $output->writeln('> Generate Temp Stop Route...');

        CommandFunctions::TruncateTable($db, 'temp_stop_route');
        CommandFunctions::generateTempStopRoute($db);

        // ---
        $output->writeln('> Updating Stop Route...');

        CommandFunctions::autoDeleteStopRoute($db);
        CommandFunctions::autoInsertStopRoute($db);

        return Command::SUCCESS;
    }
}