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

class Update extends Command
{
    private $entityManager;
    private DBServices $dbServices;
    private FileSplitter $fileSplitter;

    private ProviderRepository $providerRepository;
    private AgencyRepository $agencyRepository;

    public function __construct(EntityManagerInterface $entityManager, DBServices $dbServices, FileSplitter $fileSplitter, ProviderRepository $providerRepository, AgencyRepository $agencyRepository)
    {
        $this->entityManager = $entityManager;
        $this->dbServices = $dbServices;
        $this->fileSplitter = $fileSplitter;

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

        // --

        $output->writeln('Looking for not up-to-date GTFS...');

        $to_update = [];
        $needupdate = false;

        // Foreach provider ('tc')
        $tc_providers = $this->providerRepository->FindBy(['type' => 'tc']);

        foreach ($tc_providers as $tc_provider) {
            if ($tc_provider->getUrl() != "" && $tc_provider->getUrl() != null){
                $output->writeln('    > ' . $tc_provider->getName());

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
                $output->writeln('<error>Fail to download GTFS !</error>');
                break;
            }

            $zip = $response->getContent();
            $zip_name = $dir . '/' . $provider . '_gtfs.zip';
            file_put_contents($zip_name, $zip);

            unset($zip);

            // ---
            $output->writeln('      > Unzip gtfs...');

            $zip = new ZipArchive;
            if (!$zip->open($zip_name)) {
                $output->writeln('    X Failed to unzip');
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
            
            $step = 7 * count($ressource['filenames']);
            $progressBar = new ProgressBar($output, $step);
            $progressBar->setFormat('custom');
            $progressBar->start();            

            foreach ($types as $type => $columns) {
                $file = $dir . '/' . $provider . '/' . $type . '.txt';
                if (is_file($file)) {
                    $progressBar->setMessage("Importing $type... (Reading headers...)");
                    $progressBar->advance();
                    // echo "        $type      ";

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

                    $header = implode(",", $file_head);

                    $set = implode(",", $set);

                    try {
                        $progressBar->setMessage("Importing $type... (Preparing table...)");
                        $progressBar->advance();

                        $table = 'temp_' . $type;
                        $this->dbServices->perpareTempTable($db, $type, $table);

                        $progressBar->setMessage("Importing $type... (Spliting file...)");
                        $progressBar->advance();

                        $count = $this->fileSplitter->exec($dir, $provider, $type);

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

                        $progressBar->setMessage("Importing $type... (Clearing data in table...)");
                        $progressBar->advance($count);

                        // On enleve la vérification des clé quand on supprime (on supprime toutes les tables de toute façon)
                        $this->dbServices->initDBUpdate($db);

                        $this->dbServices->clearProviderDataInTable($db, $type, $provider);
                        
                        if ($tc_provider->getParentProvider() != null) {
                            $this->dbServices->clearProviderDataInTable($db, $type, $tc_provider->getParentProvider());
                        }

                        $progressBar->setMessage("Importing $type... (Validating data...)");
                        $progressBar->advance();

                        $this->dbServices->copyTable($db, $table, $type);

                        $progressBar->setMessage("Perfect 👌");
                        $progressBar->advance();

                        // On réactive la vérification
                        $this->dbServices->endDBUpdate($db);

                    } catch (\Exception $e) {
                        print_r($e);
                        error_log($e->getMessage());
                        $err++;
                    }
                }
            }
            $tc_provider->setFlag('1');
            $tc_provider->setUpdatedAt(new DateTime());
            $this->entityManager->flush();
            $progressBar->clear();
        }

        foreach ($to_update as $update) {
            $tc_provider = $update['provider'];
            $tc_provider->setFlag('2');
            $tc_provider->setUpdatedAt(new DateTime());
            $this->entityManager->flush();
        }

        // Concat stops area
        $input = new ArrayInput([
            'command' => 'app:gtfs:concatstoparea'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);

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

        $output->writeln('<fg=white;bg=green>           </>');
        $output->writeln('<fg=white;bg=green> Ready ✅  </>');
        $output->writeln('<fg=white;bg=green>           </>');

        $this->dbServices->prepareStopRoute($db);

        $output->writeln('> Preparing for query...');
        $this->dbServices->generateQueryRoute($db);

        $output->writeln('Finished');
        
        // Monitoring
        file_get_contents('https://betteruptime.com/api/v1/heartbeat/SrRkcBMzc4AgsXXzzZa2qFDa');

        return Command::SUCCESS;
    }
}