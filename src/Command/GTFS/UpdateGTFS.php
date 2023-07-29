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
use App\Repository\TempStopRouteRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use ZipArchive;

class UpdateGTFS extends Command
{
    private $entityManager;
    private $params;

    private ProviderRepository $providerRepository;
    private StationsRepository $stationsRepository;
    private StopsRepository $stopsRepository;
    private RoutesRepository $routesRepository;
    private TempStopRouteRepository $tempStopRouteRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, ProviderRepository $providerRepository, StationsRepository $stationsRepository, StopsRepository $stopsRepository, TempStopRouteRepository $tempStopRouteRepository, RoutesRepository $routesRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->providerRepository = $providerRepository;
        $this->stationsRepository = $stationsRepository;
        $this->stopsRepository = $stopsRepository;
        $this->routesRepository = $routesRepository;
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

        $output->writeln('> Looking for not up-to-date GTFS...');

        // Foreach provider ('tc')
        $tc_providers = $this->providerRepository->FindBy(['type' => 'tc']);

        $needupdate = false;

        foreach ($tc_providers as $tc_provider) {
            $output->writeln('  > ' . $tc_provider->getName());

            $ressource = CommandFunctions::getGTFSDataFromApi($tc_provider);

            if ($tc_provider->getFlag() == 1) {
                $output->writeln('    i Not fully updated, ignored');

            } else {
                if ($tc_provider->getFlag() == 0 || $tc_provider->getUpdatedAt() == null) {
                    $output->writeln('    i New file');

                } else if (strtotime($ressource['updated']) > strtotime($tc_provider->getUpdatedAt()->format('Y-m-d H:i:s'))) {
                    $output->writeln('    i ' . $ressource['updated'] . ' - ' . $tc_provider->getUpdatedAt()->format('Y-m-d H:i:s'));
                }

                // Let's update
                $needupdate = true;

                $provider = $tc_provider->getId();
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
                        $content = str_replace('\r\n', '\n', $content);
                        file_put_contents($dir . '/' . $provider . '/' . $filename, $content);

                        if (strpos($filename, '/')) {
                            $new = substr($filename, strpos($filename, '/') + 1);
                            rename($dir . '/' . $provider . '/' . $filename, $dir . '/' . $provider . '/' . $new);
                        }
                    }

                    unlink($zip_name);

                    // remove file and clear data
                    // $output->writeln('    > Remove old data...');
                    // CommandFunctions::clearProviderData($db, $provider);

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
                            echo '        ' . $type . '        ';

                            $table_head = [];
                            $results = CommandFunctions::getColumn($db, $type);
                            foreach ($results as $obj) {
                                $table_head[] = $obj['COLUMN_NAME'];
                            }

                            $header = Functions::getCSVHeader($file)[0][0];
                            $file_head = explode(",", $header);

                            $set = [];
                            // foreach file_head, if not in $table_head array, then it became '@dummy'
                            for($i = 0; $i < count($file_head); $i++) {
                                if (!in_array($file_head[$i], $table_head)) {
                                    $file_head[$i] = '@dummy';
                                } else {
                                    $set[] = "$file_head[$i] = NULLIF($file_head[$i], '')";
                                }
                            }

                            $set[] = "provider_id = '$provider'";
                            
                            $header = implode(",", $file_head);
                            $set = implode(",", $set);

                            try {
                                // On enleve la vérification des clé quand on supprime (on supprime toutes les tables de toute façon)
                                CommandFunctions::initDBUpdate($db);

                                
                                CommandFunctions::clearProviderDataInTable($db, $type, $provider);
                                echo '1/5 ';

                                // On réactive la vérification
                                CommandFunctions::endDBUpdate($db);
                                
                                $table = 'temp_' . $type;
                                CommandFunctions::perpareTempTable($db, $type, $table);
                                echo '2/5 ';

                                CommandFunctions::insertFile($db, $table, $file, $header, $set, ',');
                                echo '3/5 ';

                                $prefix = $provider . ':';
                                foreach ($columns as $column) {
                                    CommandFunctions::prefixTable($db, $table, $column, $prefix);
                                }

                                echo '4/5 ';

                                CommandFunctions::copyTable($db, $table, $type);
                                echo '5/5 ' . PHP_EOL;

                            } catch (\Exception $e) {
                                echo PHP_EOL;
                                error_log($e->getMessage());
                                // echo $e;
                                // echo PHP_EOL . 'ERROR ' . $table . ' !' . PHP_EOL;
                                $err++;
                            }
                        }
                    }

                    echo '      ' . $err . ' errors' . PHP_EOL;

                }
                $tc_provider->setFlag(2);
                $tc_provider->setUpdatedAt(new DateTime());
                $this->entityManager->flush();
            }
        }

        if (!$needupdate) {
            $output->writeln('<info>Nothing to do ✅</info>');

            // Monitoring
            file_get_contents('https://betteruptime.com/api/v1/heartbeat/SrRkcBMzc4AgsXXzzZa2qFDa');
            exit;
        }

        // ----
        $output->writeln('> Update route...');

        $routes = $this->routesRepository->FindBy(['route_short_name' => 'TER']);

        foreach($routes as $route) {
            $route->setRouteShortName('TER');
            $route->setRouteLongName('TER');
            $route->setRouteType('99');
            $route->setRouteColor("000000");
            $route->setRouteTextColor("aaaaaa");
        }
        $this->entityManager->flush();
        
        // ----
        $output->writeln('> Generate Stop Area...');

        $s = [];
        $stops = $this->stopsRepository->FindBy(['location_type' => '0', 'parent_station' => '']);

        foreach ($stops as $stop) {
            $id = $stop->getProviderId() . $stop->getStopName();

            if (!isset($stops[$id])) {
                $s[$id] = array(
                    'provider_id' => $stop->getProviderId(),
                    'stop_id' => 'ADMIN:' . $stop->getStopId(),
                    'stop_code' => $stop->getStopCode(),
                    'stop_name' => $stop->getStopName(),
                    'stop_lat' => $stop->getStopLat(),
                    'stop_lon' => $stop->getStopLon(),
                    'stops' => array(),
                );
            }
            $s[$id]['stops'][] = $stop->getStopId();
        }

        foreach ($s as $stop) {
            $stp = new Stops();
            $stp->setProviderId($stop['provider_id']);
            $stp->setStopId($stop['stop_id']);
            $stp->setStopCode($stop['stop_code']);
            $stp->setStopName($stop['stop_code']);
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


        // ---
        $output->writeln('> Generate Temp Stop Route...');

        CommandFunctions::truncateTempStopRoute($db);
        CommandFunctions::generateTempStopRoute($db);


        // ---
        $output->writeln('> Updating Stop Route...');

        CommandFunctions::autoDeleteStopRoute($db);
        CommandFunctions::autoInsertStopRoute($db);

        
        // ----
        $output->writeln('  > Import SNCF stops...');

        $SNCF_FORBIDDEN_DEPT = array("75", "92", "93", "94", "77", "78", "91", "95");
        $SNCF_FORCE = array("Bréval", "Gazeran", "Angerville", "Monnerville", "Guillerval");
        $SNCF_FORBIDDEN = array("Crépy-en-Valois", "Château-Thierry", "Montargis", "Malesherbes", "Dreux", "Gisors", "Creil", "Le Plessis-Belleville", "Nanteuil-le-Haudouin ", "Ormoy-Villers", "Mareuil-sur-Ourcq", "La Ferté-Milon", "Nogent-l'Artaud - Charly", "Dordives", "Ferrières - Fontenay", "Marchezais - Broué", "Vernon - Giverny", "Trie-Château", "Chaumont-en-Vexin", "Liancourt-Saint-Pierre", "Lavilletertre", "Boran-sur-Oise", "Précy-sur-Oise", "Saint-Leu-d'Esserent", "Chantilly - Gouvieux", "Orry-la-Ville - Coye", "La Borne Blanche");

        $provider = $this->providerRepository->FindOneBy(['id' => 'SNCF']);

        $url = $provider->getUrl();
        $id = $provider->getId();
        $file = $dir . '/' . $id . '.csv';

        $output->writeln('    ' . $url);
        $sncf = file_get_contents($url);
        file_put_contents($file, $sncf);
        

        $route = $this->routesRepository->FindOneBy(['route_id' => 'SNCF']);

        $s = [];
        $sncf = Functions::readCsv($file);
        
        foreach ($sncf as $row) {
            if ( !is_bool($row) && $row[0] != 'code' && $row[1] != '' && $row[1] != false) {

                $id = 'SNCF:' . substr($row[2], 2);

                if ( !in_array($id, $s) ) {
                    $s[] = $id;

                    $allowed = true;
                    if (in_array($row[8], $SNCF_FORBIDDEN_DEPT)) {
                        $allowed = false;
                    }
                    if (in_array($row[4], $SNCF_FORBIDDEN)) {
                        $allowed = false;
                    }
                    if (in_array($row[4], $SNCF_FORCE)){
                        $allowed = true;
                    }

                    if ($allowed == true) {
                        try {

                            $stop = $this->stopsRepository->findOneBy(['stop_id' => $id]);
                            if ( $stop == null ) {
                                $stop = new Stops();
                                $stop->setStopId( $id );
                                $stop->setStopCode( $row[27] );
                                $stop->setStopName( $row[4] );
                                $stop->setStopLat( isset($row[11]) ? $row[11] : '' );
                                $stop->setStopLon( isset($row[10]) ? $row[10] : '' );
                                $stop->setLocationType( '0' );
                                $stop->setVehicleType( '2' );
                            }
            
                            $stop_route = new StopRoute();
                            $stop_route->setRouteKey( 'SNCF-' . $id );
                            $stop_route->setRouteId( $route );
                            $stop_route->setRouteShortName( $route->getRouteShortName() );
                            $stop_route->setRouteLongName( $route->getRouteLongName() );
                            $stop_route->setRouteType( $route->getRouteType() );
                            $stop_route->setRouteColor( $route->getRouteColor() );
                            $stop_route->setRouteTextColor( $route->getRouteTextColor() );
                            $stop_route->setStopId( $stop );
                            $stop_route->setStopName( $stop->getStopName() );
                            $stop_route->setStopQueryName( $stop->getStopName() );
                            $stop_route->setStopLat( $stop->getStopLat() );
                            $stop_route->setStopLon( $stop->getStopLon() );
                            
                            $this->entityManager->persist($stop);
                            $this->entityManager->persist($stop_route);
                        } catch (\Exception $e) {
                            echo $e;
                        }
                    }                    
                }
            }
        }
        $this->entityManager->flush();

        $output->writeln('<fg=white;bg=green>           </>');
        $output->writeln('<fg=white;bg=green> Ready ✅  </>');
        $output->writeln('<fg=white;bg=green>           </>');

        CommandFunctions::prepareStopRoute($db);

    //TODO    $output->writeln('> Updating stop_toute for Town...');
    //TODO    CommandFunctions::generateTownInStopRoute($db);

        $output->writeln('> Preparing for query...');
        CommandFunctions::generateQueryRoute($db);
        
        return Command::SUCCESS;
    }
}