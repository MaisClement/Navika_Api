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
use Symfony\Component\Console\Input\ArrayInput;

class GTFS_Update extends Command
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
            ->setName('app:gtfs:update')
            ->setDescription('Update gtfs');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();
        
        // --
        $output->writeln('Looking for not up-to-date GTFS...');

        $to_update = [];

        // Foreach provider ('tc')
        $tc_providers = $this->providerRepository->FindBy(['type' => 'tc']);

        $needupdate = false;

        foreach ($tc_providers as $tc_provider) {
            $output->writeln('  > ' . $tc_provider->getName());

            $ressource = CommandFunctions::getGTFSDataFromApi($tc_provider);

            if ($tc_provider->getFlag() == 1) {
                $output->writeln('    i Not fully updated, ignored');

            } else if ($tc_provider->getFlag() == 0 || $tc_provider->getUpdatedAt() == null) {
                $output->writeln('    i New file');
                $to_update[] = array(
                    'provider' => $tc_provider,
                    'ressource' => $ressource
                );

            } else if (strtotime($ressource['updated']) > strtotime($tc_provider->getUpdatedAt()->format('Y-m-d H:i:s'))) {
                $output->writeln('    i ' . $ressource['updated'] . ' - ' . $tc_provider->getUpdatedAt()->format('Y-m-d H:i:s'));
                $to_update[] = array(
                    'provider' => $tc_provider,
                    'ressource' => $ressource
                );

            }
        }

        if (count($to_update) == 0) {
            $output->writeln('<info>Nothing to do ✅</info>');

            // Monitoring
            file_get_contents('https://betteruptime.com/api/v1/heartbeat/SrRkcBMzc4AgsXXzzZa2qFDa');
            exit;
        }

        $output->writeln("");
        $output->writeln("Lets's update !");

        foreach ($to_update as $update) {

            $tc_provider = $update['provider'];
            $ressource = $update['ressource'];

            $provider = $tc_provider->getId();
            $output->writeln('    ' . $provider);
            $output->writeln('      ' . $ressource['url']);

            // ---

            $url = $ressource['url'];

            $client = HttpClient::create();
            $response = $client->request('GET', $ressource['url']);
            $status = $response->getStatusCode();

            if ($status != 200) {
                return Command::FAILURE;
            }

            $zip = $response->getContent();

            $zip_name = $dir . '/' . $provider . '_gtfs.zip';
            file_put_contents($zip_name, $zip);
            unset($zip);

            // ---
            $output->writeln('    > Unzip gtfs...');
            $zip = new ZipArchive;
            if (!$zip->open($zip_name)) {
                $output->writeln('    X Failed to unzip');

            } else {
                $zip->extractTo($dir . '/' . $provider . '/');
                $zip->close();

                $output->writeln('    > Format file...');
                foreach ($ressource['filenames'] as $filename) {
                    // on deplace hors d'un potentiel fichier
                    $content = file_get_contents($dir . '/' . $provider . '/' . $filename);
                    unlink($dir . '/' . $provider . '/' . $filename);
                    $content = str_replace("\r\n", "\n", $content);
                    $content = str_replace("\n", ",\n", $content);
                    $regex = '/^(?:(?![×Þß÷þø])[-\'0-9a-zA-ZÀ-ÿ])+$/u';
                    $content = preg_replace($regex, '', $content);
                    file_put_contents($dir . '/' . $provider . '/' . $filename, $content);

                    if (strpos($filename, '/')) {
                        $new = substr($filename, strpos($filename, '/') + 1);
                        rename($dir . '/' . $provider . '/' . $filename, $dir . '/' . $provider . '/' . $new);
                    }
                }

                unlink($zip_name);

                // import gtfs
                $output->writeln('    > Import new GTFS...');
                $err = 0;
                $types = [
                    'agency' => ['agency_id'],
                    'routes' => ['route_id', 'agency_id'],

                    'calendar' => ['service_id'],
                    'calendar_dates' => ['service_id'],
                    'shapes' => ['shape_id'],
                    'trips' => ['route_id', 'service_id', 'trip_id', 'shape_id'],

                    'levels' => ['level_id'],
                    'stops' => ['stop_id', 'level_id', 'parent_station'],
                    'transfers' => ['from_stop_id', 'to_stop_id'],
                    'pathways' => ['pathway_id', 'from_stop_id', 'to_stop_id'],

                    'stop_times' => ['trip_id', 'stop_id'],

                    'fare_rules' => ['fare_id', 'route_id', 'origin_id', 'destination_i'],
                    'fare_attributes' => ['fare_id', 'agency_id'],

                    'frequencies' => ['trip_id'],

                    'feed_info' => [],
                    'translations' => [],
                    'attributions' => []
                ];

                foreach ($types as $type => $columns) {
                    $file = $dir . '/' . $provider . '/' . $type . '.txt';
                    if (is_file($file)) {
                        echo "        $type      ";

                        // Get field of the table in bd
                        $table_head = [];
                        $results = CommandFunctions::getColumn($db, $type);
                        foreach ($results as $obj) {
                            $table_head[] = $obj['COLUMN_NAME'];
                        }

                        // Get field in file
                        $header = Functions::getCSVHeader($file)[0][0];
                        $file_head = explode(",", $header);

                        $set = [];
                        // foreach file_head, if not in $table_head array, then it became '@dummy'

                        for ($i = 0; $i < count($file_head); $i++) {
                            if (!in_array($file_head[$i], $table_head)) {
                                $file_head[$i] = '@dummy';
                            } else {
                                $set[] = "$file_head[$i] = NULLIF($file_head[$i], '')";
                            }
                        }

                        $set[] = "provider_id = '$provider'";

                        if ($type == 'routes' && !in_array("agency_id", $file_head)) {
                            $agency = $this->agencyRepository->FindOneBy(['provider_id' => $provider])->getAgencyId();
                            $set[] = "agency_id = '$agency'";
                        }

                        if ($type == 'stops' && !in_array("location_type", $file_head)) {
                            $set[] = "location_type = '0'";
                        }

                        $header = implode(",", $file_head);

                        $set = implode(",", $set);

                        try {
                            $table = 'temp_' . $type;
                            CommandFunctions::perpareTempTable($db, $type, $table);
                            echo '1/5 ';

                            CommandFunctions::insertFile($db, $table, $file, $header, $set, ',');
                            echo '2/5 ';

                            $prefix = $provider . ':';
                            foreach ($columns as $column) {
                                CommandFunctions::prefixTable($db, $table, $column, $prefix);
                            }

                            echo '3/5 ';

                            // On enleve la vérification des clé quand on supprime (on supprime toutes les tables de toute façon)
                            CommandFunctions::initDBUpdate($db);

                            CommandFunctions::clearProviderDataInTable($db, $type, $provider);
                            echo '4/5 ';

                            CommandFunctions::copyTable($db, $table, $type);
                            echo '5/5 ' . PHP_EOL;

                            // On réactive la vérification
                            CommandFunctions::endDBUpdate($db);

                        } catch (\Exception $e) {
                            echo PHP_EOL;
                            error_log($e->getMessage());
                            // echo $e;
                            // echo PHP_EOL . 'ERROR ' . $table . ' !' . PHP_EOL;
                            $err++;
                        }
                    }
                }
            }
            $tc_provider->setFlag(1);
            $tc_provider->setUpdatedAt(new DateTime());
            $this->entityManager->flush();
        }

        foreach ($to_update as $update) {

            $tc_provider = $update['provider'];
            $tc_provider->setFlag(2);
            $tc_provider->setUpdatedAt(new DateTime());
            $this->entityManager->flush();
        }

        // Format TER
        $routes = $this->routesRepository->FindBy(['route_short_name' => 'TER']);

        foreach ($routes as $route) {
            $route->setRouteShortName('TER');
            $route->setRouteLongName('TER');
            $route->setRouteType('99');
            $route->setRouteColor("000000");
            $route->setRouteTextColor("aaaaaa");
        }
        $this->entityManager->flush();

        // Stop Area

        $input = new ArrayInput([
            'command' => 'app:gtfs:stoparea'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);
        
        // Stop Route
        $input = new ArrayInput([
            'command' => 'app:gtfs:stoproute'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);

        // ----

        // SNCF stop
         $input = new ArrayInput([
            'command' => 'app:sncf:update'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);
        
        // ----
        
        $output->writeln('<fg=white;bg=green>           </>');
        $output->writeln('<fg=white;bg=green> Ready ✅  </>');
        $output->writeln('<fg=white;bg=green>           </>');

        CommandFunctions::prepareStopRoute($db);

        $output->writeln('> Preparing for query...');
        CommandFunctions::generateQueryRoute($db);

        $output->writeln('Finished');

        return Command::SUCCESS;
    }
}