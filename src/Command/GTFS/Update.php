<?php

namespace App\Command\GTFS;

use App\Command\CommandFunctions;
use App\Service\DBServices;
use App\Service\FileSplitter;
use App\Controller\Functions;
use App\Repository\AgencyRepository;
use App\Repository\ProviderRepository;
use App\Repository\RoutesRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Console\Helper\ProgressBar;
use ZipArchive;
use App\Service\Logger;

class Update extends Command
{
    private $entityManager;
    private $params;
    private DBServices $dbServices;
    private FileSplitter $fileSplitter;
    private Logger $logger;

    private ProviderRepository $providerRepository;
    private AgencyRepository $agencyRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, DBServices $dbServices, FileSplitter $fileSplitter, Logger $logger, ProviderRepository $providerRepository, AgencyRepository $agencyRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->dbServices = $dbServices;
        $this->fileSplitter = $fileSplitter;
        $this->logger = $logger;

        $this->providerRepository = $providerRepository;
        $this->agencyRepository = $agencyRepository;

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
        $event_id = uniqid();

        $this->logger->log(['event_id' => $event_id,'message' => "[app:gtfs:update][$event_id] Task began"], 'INFO');

        // --

        $output->writeln('Looking for not up-to-date GTFS...');
        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Looking for not up-to-date GTFS..."], 'INFO');

        $to_update = [];
        $needupdate = false;

        // Foreach provider ('tc')
        $tc_providers = $this->providerRepository->FindBy(['type' => 'tc']);

        foreach ($tc_providers as $tc_provider) {
            if ($tc_provider->getUrl() != "" && $tc_provider->getUrl() != null){
                $name = $tc_provider->getName();
                $output->writeln('    > ' . $name);

                $ressource = CommandFunctions::getGTFSDataFromApi($tc_provider);

                if ($tc_provider->getFlag() == 1) {
                    $output->writeln('    i Not fully updated, ignored');
                    $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] $name GTFS is not fully updated, ignored"], 'INFO');


                } else if ($tc_provider->getFlag() == 0 || $tc_provider->getUpdatedAt() == null) {
                    $output->writeln('    i New file');
                    $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] $name is new"], 'INFO');
                    $to_update[] = array(
                        'provider' => $tc_provider,
                        'ressource' => $ressource
                    );

                } else if (strtotime($ressource['updated']) > strtotime($tc_provider->getUpdatedAt()->format('Y-m-d H:i:s'))) {
                    $output->writeln('    i ' . $ressource['updated'] . ' - ' . $tc_provider->getUpdatedAt()->format('Y-m-d H:i:s'));
                    $this->logger->log(['event_id' => $event_id,'message' => sprintf("[$event_id] $name have to be updated : %s - %s", $ressource['updated'], $tc_provider->getUpdatedAt()->format('Y-m-d H:i:s'))], 'INFO');
                    $to_update[] = array(
                        'provider' => $tc_provider,
                        'ressource' => $ressource
                    );
                }
            }
        }
                    
        if (count($to_update) == 0) {
            $output->writeln('<info>Nothing to do ✅</info>');
            $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Task ended succesfully"], 'INFO');

            // Monitoring
            file_get_contents('https://betteruptime.com/api/v1/heartbeat/SrRkcBMzc4AgsXXzzZa2qFDa');
            exit;
        }

        $output->writeln("");
        $output->writeln("Lets's update !");

        $step = 0;
        foreach ($to_update as $update) {
            $step++;
            $tc_provider = $update['provider'];
            $ressource = $update['ressource'];

            $provider = $tc_provider->getId();
            $output->writeln('    ' . $provider);
            $output->writeln('      ' . $ressource['url']);

            $this->logger->log(['event_id' => $event_id,'message' => sprintf("[$event_id][$step] updating $provider GTFS from %s", $ressource['url'])], 'INFO');
        
            // ---

            $url = $ressource['url'];

            $client = HttpClient::create();
            $response = $client->request('GET', $ressource['url']);
            $status = $response->getStatusCode();

            if ($status != 200) {
                $output->writeln('<error>Fail to download GTFS !</error>');
                $this->logger->log(['event_id' => $event_id,'message' => sprintf("[$event_id][$step] fail to download GTFS from %s", $ressource['url'])], 'WARN');
        
                break;
            }

            $zip = $response->getContent();
            $zip_name = $dir . '/' . $provider . '_gtfs.zip';
            file_put_contents($zip_name, $zip);
            $this->logger->log(['event_id' => $event_id,'message' => sprintf("[$event_id][$step] GTFS saved to $zip_name - size : %s", filesize($zip_name))], 'INFO');
            
            $otp_zip_name = $this->params->get('otp_gtfs_path') . 'GTFS/' . $provider . '_gtfs.zip';
            file_put_contents($otp_zip_name, $zip);
            $this->logger->log(['event_id' => $event_id,'message' => sprintf("[$event_id][$step] GTFS saved to $otp_zip_name - size : %s", filesize($otp_zip_name))], 'INFO');
        
            unset($zip);

            // ---
            $output->writeln('      > Unzip gtfs...');

            $zip = new ZipArchive;
            if (!$zip->open($zip_name)) {
                $output->writeln('    X Failed to unzip');
                $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step] Fail to unzip $zip_name !"], 'ERROR');
                break;
            }

            $zip->extractTo($dir . '/' . $provider . '/');
            $zip->close();

            $output->writeln('      > Format file...');

            foreach ($ressource['filenames'] as $filename) {
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
            $output->writeln('      > Import new GTFS...');
            $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step] Start GTFS import"], 'INFO');
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
            //    'transfers' => ['from_stop_id', 'to_stop_id'],
                'pathways' => ['pathway_id', 'from_stop_id', 'to_stop_id'],

                'stop_times' => ['trip_id', 'stop_id'],

                'fare_rules' => ['fare_id', 'route_id', 'origin_id', 'destination_i'],
                'fare_attributes' => ['fare_id', 'agency_id'],

                'frequencies' => ['trip_id'],

                'feed_info' => [],
            //    'translations' => [],
                'attributions' => []
            ];

            ProgressBar::setFormatDefinition('custom', '%percent%% [%bar%] %elapsed% - %remaining% | %message%');
            
            $steps = 7 * count($ressource['filenames']);
            $progressBar = new ProgressBar($output, $steps);
            $progressBar->setFormat('custom');
            $progressBar->start();            

            foreach ($types as $type => $columns) {
                $file = $dir . '/' . $provider . '/' . $type . '.txt';
                
                if (is_file($file)) {
                    $progressBar->setMessage("Importing $type... (Reading headers...)");
                    $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step][$type] Reading $file"], 'INFO');
            
                    $progressBar->advance();

                    // Get field of the table in bd
                    $table_head = [];
                    $results = $this->dbServices->getColumns($db, $type);
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

                    $table_header = implode(",", $table_head);
                    $header = implode(",", $file_head);
                    $set = implode(",", $set);

                    $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step][$type] Table header $table_header"], 'DEBUG');
                    $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step][$type] File header : $header"], 'DEBUG');
                    $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step][$type] SQL set : $set"], 'DEBUG');
            

                    try {
                        $progressBar->setMessage("Importing $type... (Preparing table...)");
                        $progressBar->advance();

                        $table = 'temp_' . $type;
                        $this->dbServices->perpareTempTable($db, $type, $table);

                        $progressBar->setMessage("Importing $type... (Spliting file...)");
                        $progressBar->advance();

                        $count = $this->fileSplitter->exec($dir, $provider, $type);

                        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step][$type] File splitted in $count part(s) "], 'INFO');

                        $progressBar->setMaxSteps( $progressBar->getMaxSteps() + ($count * 3) );
                        
                        for ($i = 1; $i <= $count; $i++) {
                            $progressBar->setMessage("Importing $type... (Importing file $i / $count...)");
                            $progressBar->advance();

                            $splited_file = $dir . '/' . $provider . '/' . $type . '_' . $i . '.txt';
                            $this->dbServices->insertFile($db, $table, $splited_file, $header, $set, ',');
                        }

                        $progressBar->setMessage("Importing $type... (Add prefix...)");
                        $progressBar->advance($count);

                        $prefix = $provider . ':';

                        foreach ($columns as $column) {
                            $this->dbServices->prefixTable($db, $table, $column, $prefix);
                            $progressBar->advance(0);
                        }

                        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step][$type] Prefix added"], 'INFO');

                        $progressBar->setMessage("Importing $type... (Clearing data in table...)");
                        $progressBar->advance($count);

                        // On enleve la vérification des clé quand on supprime (on supprime toutes les tables de toute façon)
                        $this->dbServices->initDBUpdate($db);
                        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step][$type] Disable FOREIGN_KEY_CHECKS"], 'INFO');

                        $this->dbServices->clearProviderDataInTable($db, $type, $provider);
                        
                        if ($tc_provider->getParentProvider() != null) {
                            $this->dbServices->clearProviderDataInTable($db, $type, $tc_provider->getParentProvider());
                        }
                        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step][$type] Data cleared"], 'INFO');

                        $progressBar->setMessage("Importing $type... (Validating data...)");
                        $progressBar->advance();

                        $this->dbServices->copyTable($db, $table, $type);
                        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step][$type] Data copied from temp table"], 'INFO');

                        $progressBar->setMessage("Perfect 👌");
                        $progressBar->advance();

                        // On réactive la vérification
                        $this->dbServices->endDBUpdate($db);
                        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$step][$type] Enable FOREIGN_KEY_CHECKS"], 'INFO');

                    } catch (\Exception $e) {
                        print_r($e);
                        error_log($e->getMessage());
                        $this->logger->error($e, 'WARN', "[$event_id] ");
                        $err++;
                    }
                }
            }
            $tc_provider->setFlag('1');
            $tc_provider->setUpdatedAt(new DateTime());
            $this->entityManager->flush();
            $progressBar->clear();
        }
        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] GTFS imported"], 'INFO');

        foreach ($to_update as $update) {
            $tc_provider = $update['provider'];
            $tc_provider->setFlag('2');
            $tc_provider->setUpdatedAt(new DateTime());
            $this->entityManager->flush();
        }

        // Concat stops area
        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Running app:gtfs:concatstoparea"], 'INFO');
        $input = new ArrayInput([
            'command' => 'app:gtfs:concatstoparea'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);

        // Stop Area
        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Running app:gtfs:stoparea"], 'INFO');
        $input = new ArrayInput([
            'command' => 'app:gtfs:stoparea'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);

        // Stop Route
        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Running app:gtfs:stoproute"], 'INFO');
        $input = new ArrayInput([
            'command' => 'app:gtfs:stoproute'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);

        // ----

        $output->writeln('<fg=white;bg=green>           </>');
        $output->writeln('<fg=white;bg=green> Ready ✅  </>');
        $output->writeln('<fg=white;bg=green>           </>');

        $this->dbServices->prepareStopRoute($db);

        $output->writeln('> Preparing for query...');
        $this->dbServices->generateQueryRoute($db);

        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Query StopRoute updated"], 'INFO');
        

        $output->writeln('Finished');
        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Task ended succesfully"], 'INFO');
        
        // Monitoring
        file_get_contents('https://betteruptime.com/api/v1/heartbeat/SrRkcBMzc4AgsXXzzZa2qFDa');

        return Command::SUCCESS;
    }
}