<?php

namespace App\Command\GTFS;

use App\Command\CommandFunctions;
use App\Service\DB;
use App\Service\FileSplitter;
use App\Service\GTFSCleaner;
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
use Symfony\Component\Yaml\Yaml;
use ZipArchive;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Service\Logger;
use Symfony\Component\Process\Process;

class Update extends Command
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private DB $DB;
    private FileSplitter $fileSplitter;
    private GTFSCleaner $gtfsCleaner;
    private Logger $logger;

    private ProviderRepository $providerRepository;
    private AgencyRepository $agencyRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, DB $DB, FileSplitter $fileSplitter, GTFSCleaner $gtfsCleaner, Logger $logger, ProviderRepository $providerRepository, AgencyRepository $agencyRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->DB = $DB;
        $this->fileSplitter = $fileSplitter;
        $this->gtfsCleaner = $gtfsCleaner;
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
        $dir = sys_get_temp_dir() . '/navika';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $db = $this->entityManager->getConnection();
        $event_id = uniqid();
        
        ProgressBar::setFormatDefinition('custom', '%percent%% [%bar%] %elapsed% - %remaining% | %message%');

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
            'stop_extensions' => ['object_id', 'object_code'],

            'fare_rules' => ['fare_id', 'route_id', 'origin_id', 'destination_id'],
            'fare_attributes' => ['fare_id', 'agency_id'],

            'frequencies' => ['trip_id'],

            'feed_info' => [],
            //    'translations' => [],
            'attributions' => []
        ];

        $this->logger->log(['event_id' => $event_id, 'message' => "[app:gtfs:update][$event_id] Task began"], 'INFO');

        // --

        $output->writeln('Looking for not up-to-date GTFS...');
        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Looking for not up-to-date GTFS..."], 'INFO');

        $to_update = [];
        $needupdate = false;

        // Foreach provider ('tc')
        $tc_providers = $this->providerRepository->findBy(['type' => 'tc']);

        foreach ($tc_providers as $tc_provider) {
            if ($tc_provider->getUrl() != "" && $tc_provider->getUrl() != null) {
                $name = $tc_provider->getName();
                $output->writeln('    > ' . $name);

                $ressource = CommandFunctions::getGTFSDataFromApi($tc_provider);
                print_r($ressource['updated']);

                if ($tc_provider->getFlag() == 1) {
                    $output->writeln('    i Not fully updated, ignored');
                    $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] $name GTFS is not fully updated, ignored"], 'INFO');


                } else if ($tc_provider->getFlag() == 0 || $tc_provider->getUpdatedAt() == null) {
                    $output->writeln('    i New file');
                    $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] $name is new"], 'INFO');
                    $to_update[] = array(
                        'provider' => $tc_provider,
                        'ressource' => $ressource
                    );

                } else if (strtotime($ressource['updated']) > strtotime($tc_provider->getUpdatedAt()->format('Y-m-d H:i:s'))) {
                    $output->writeln('    i ' . $ressource['updated'] . ' - ' . $tc_provider->getUpdatedAt()->format('Y-m-d H:i:s'));
                    $this->logger->log(['event_id' => $event_id, 'message' => sprintf("[$event_id] $name have to be updated : %s - %s", $ressource['updated'], $tc_provider->getUpdatedAt()->format('Y-m-d H:i:s'))], 'INFO');
                    $to_update[] = array(
                        'provider' => $tc_provider,
                        'ressource' => $ressource
                    );
                }
            }
        }
                    
        if (count($to_update) == 0) {
            $output->writeln('<info>Nothing to do ✅</info>');
            $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Task ended succesfully"], 'INFO');

            // Monitoring
            file_get_contents('https://betteruptime.com/api/v1/heartbeat/SrRkcBMzc4AgsXXzzZa2qFDa');
            exit;
        }

        $output->writeln("");
        $output->writeln("Download GTFS");

        $step = 0;
        foreach ($to_update as $update) {
            if (isset($update['ressource']['url'])) {
                $tc_provider = $update['provider'];
                $ressource = $update['ressource'];

                $provider = $tc_provider->getId();

                $output->writeln('    ' . $provider);
                $output->writeln('      ' . $ressource['url']);

                $this->logger->log(['event_id' => $event_id, 'message' => sprintf("[$event_id] updating $provider GTFS from %s", $ressource['url'])], 'INFO');

                // ---

                $url = $ressource['url'];

                $client = HttpClient::create();
                $response = $client->request('GET', $ressource['url']);
                $status = $response->getStatusCode();

                if ($status != 200) {
                    $output->writeln('<error>Fail to download GTFS !</error>');
                    $this->logger->log(['event_id' => $event_id, 'message' => sprintf("[$event_id] fail to download GTFS from %s", $ressource['url'])], 'WARN');

                    continue;
                }

                $zip = $response->getContent();
                $zip_name = $dir . '/' . $provider . '_gtfs.zip';
                file_put_contents($zip_name, $zip);
                $this->logger->log(['event_id' => $event_id, 'message' => sprintf("[$event_id] GTFS saved to $zip_name - size : %s", filesize($zip_name))], 'INFO');

                $output->writeln('     ' . $zip_name);

                $gtfs_path = $this->params->get('gtfs_path');
                $zip_name = $gtfs_path . '/' . $provider . '_gtfs.zip';
                file_put_contents($zip_name, $zip);
                $this->logger->log(['event_id' => $event_id, 'message' => sprintf("[$event_id] GTFS saved to $zip_name - size : %s", filesize($zip_name))], 'INFO');

                $output->writeln('     ' . $zip_name);

                unset($zip);
            } else {
                $output->writeln('PAS DE URL !!!');
            }
        }

        // La construction du jeu de données MOTIS est déclenchée en fin de
        // commande, une fois les GTFS téléchargés : elle dure bien plus
        // longtemps que l'import en base et n'a pas à le retarder.
        // La configuration motis est écrite par app:motis:deploy lui-même.

        // ---

        $output->writeln("");
        $output->writeln("Lets's update !");

        // ---

        $progressBar = new ProgressBar($output, count($types) * 2);
        $progressBar->setFormat('custom');
        $progressBar->start();

        // On crée des tables temporaires pour eviter les impacts
        foreach ($types as $type => $columns) {
            try {
                $progressBar->setMessage("Preparing $type... (Creating temp table...)");
                $progressBar->advance();

                // On enleve la vérification des clé quand on supprime (on supprime toutes les tables de toute façon)
                $this->DB->initDBUpdate($db);
                $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step][$type] Disable FOREIGN_KEY_CHECKS"], 'INFO');

                // On crée la table temporaire
                $table = $type;
                $temp_table = 'temp_' . $type;
                $old_table = 'old_' . $type;
                $this->DB->createTempTable($db, $table, $temp_table);
                $this->DB->perpareTempTable($db, $table, $temp_table);
                
                // On rempli la table temporaire
                $progressBar->setMessage("Preparing $type... (Preparing temp table...)");
                $progressBar->advance();
                
                $this->DB->copyTable($db, $table, $temp_table);

            } catch (\Exception $e) {
                print_r($e);
                error_log($e->getMessage());
                $this->logger->error($e, 'WARN', "[$event_id] ");
            }
        }
        $progressBar->clear();

        foreach ($to_update as $update) {
            $step++;
            $tc_provider = $update['provider'];
            $ressource = $update['ressource'];
            $provider = $tc_provider->getId();
            $output->writeln('    ' . $provider);
            
            if (isset($ressource['url'])) {
                $output->writeln('      ' . $ressource['url']);
            }

            // ---
            $output->writeln('      > Unzip gtfs...');

            $zip_name = $dir . '/' . $provider . '_gtfs.zip';

            $zip = new ZipArchive;
            if ($zip->open($zip_name) !== true) {
                $output->writeln('    X Failed to unzip');
                $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step] Fail to unzip $zip_name !"], 'ERROR');
                continue;
            }

            $zip->extractTo($dir . '/' . $provider . '/');
            $zip->close();

            $output->writeln('      > Normalisation des fichiers...');
            $normalizationSummaries = [];
            foreach ($ressource['filenames'] as $filename) {
                $fullPath = $dir . '/' . $provider . '/' . $filename;
                if (!is_file($fullPath)) { continue; }
                if (strpos($filename, '/')) { // remonter fichier si sous-dossier
                    $new = substr($filename, strpos($filename, '/') + 1);
                    $newFull = $dir . '/' . $provider . '/' . $new;
                    @rename($fullPath, $newFull);
                    $fullPath = $newFull;
                    $filename = $new;
                }
                $typeFile = str_replace('.txt', '', $filename);
                if ($typeFile === '') { continue; }
                try {
                    $result = $this->gtfsCleaner->normalize($fullPath, $typeFile, $provider);
                    $normalizationSummaries[$typeFile] = $result['summary'];
                } catch (\Throwable $e) {
                    $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$provider] Normalisation error $filename : " . $e->getMessage()], 'WARN');
                    $output->writeln('<error>Normalisation error ' . $filename . ' : ' . $e->getMessage() . '</error>');
                }
            }
            foreach ($normalizationSummaries as $typeFile => $s) {
                $output->writeln('        - Fichier: ' . $typeFile);
                $output->writeln('            Attendu: ' . implode(',', $s['expected']));
                $output->writeln('            Fichier: ' . implode(',', $s['file_columns']));
                $output->writeln('            Populé automatiquement : ' . implode(',', $s['auto']));
                $output->writeln('            Manquant: ' . (count($s['missing']) ? implode(',', $s['missing']) : 'Ø'));
                $output->writeln('            Extra: ' . (count($s['extra']) ? implode(',', $s['extra']) : 'Ø'));
                $output->writeln('            Lignes gardées : ' . $s['kept'] . ' | ignorées : ' . $s['ignored'] . ' | valid=' . ($s['valid'] ? 'oui' : 'non'));
            }

            // import gtfs
            $output->writeln('      > Import new GTFS...');
            $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step] Start GTFS import"], 'INFO');
            $err = 0;
            ProgressBar::setFormatDefinition('custom', '%percent%% [%bar%] %elapsed% - %remaining% | %message%');

            $steps = 7 * count($ressource['filenames']);
            $progressBar = new ProgressBar($output, $steps);
            $progressBar->setFormat('custom');
            $progressBar->start();

            foreach ($types as $type => $columns) {
                $origFile = $dir . '/' . $provider . '/' . $type . '.txt';
                $cleanFile = $dir . '/' . $provider . '/clean_' . $type . '.txt';
                $file = is_file($cleanFile) ? $cleanFile : $origFile;
                $table = $type;
                $temp_table = 'temp_' . $type;
                $old_table = 'old_' . $type;
            
                if (is_file($file)) {
                    $progressBar->setMessage("Importing $type... (Reading headers...)");
                    $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step][$type] Reading $file"], 'INFO');
            
                    $progressBar->advance();
            
                    // Lecture header normalisé
                    $fh = fopen($file, 'r');
                    $headerLine = $fh !== false ? trim((string)fgets($fh)) : '';
                    if ($fh !== false) { fclose($fh); }
                    if ($headerLine === '') {
                        $output->writeln("<comment>Header vide pour $type, fichier ignoré</comment>");
                        continue;
                    }
                    $raw_head = array_map(static fn($c) => trim($c), str_getcsv($headerLine, ','));
                    $tableColsRaw = $this->DB->getColumns($db, $type);
                    $tableCols = [];
                    foreach ($tableColsRaw as $c) { $tableCols[] = $c['COLUMN_NAME']; }
                    $autoCols = ['id','provider_id'];
                    // Nouvelle stratégie: on parcourt les colonnes du fichier pour que le nombre de champs corresponde exactement aux champs importés.
                    // Chaque colonne du fichier :
                    //  - Si auto (id, provider_id) => ignorée (pas ajoutée à LOAD DATA)
                    //  - Si correspond à une colonne DB ET est attendue => ajoutée sous son nom
                    //  - Sinon => mappée sur une variable @dummy_file_<col>
                    // Colonnes DB supplémentaires non présentes dans le fichier => traitées plus bas dans SET (NULL)
                    $expected = $normalizationSummaries[$type]['expected'] ?? $raw_head; // fallback
                    $expectedMap = array_flip($expected);
                    $dbColsMap = array_flip($tableCols);
                    $loadCols = [];
                    $presentCols = [];
                    $ignoredFileCols = [];
                    $userVarCols = []; // map col => @v_col si importé
                    foreach ($raw_head as $col) {
                        if (in_array($col, $autoCols, true)) { continue; }
                        if (isset($dbColsMap[$col]) && isset($expectedMap[$col])) {
                            $varName = '@v_' . $col; // variable utilisateur pour contrôler la transformation
                            $loadCols[] = $varName;
                            $presentCols[] = $col;
                            $userVarCols[$col] = $varName;
                        } else {
                            $loadCols[] = '@dummy_file_' . $col; // Colonne ignorée (extra ou non supportée en BDD)
                            $ignoredFileCols[] = $col;
                        }
                    }
                    // S'il n'y a aucune colonne importable mais qu'on a des colonnes ignorées -> on évite l'import inutile
                    if (empty($loadCols)) {
                        $output->writeln("<comment>Aucune colonne exploitable pour $type, ignoré</comment>");
                        continue;
                    }
                    $setParts = [];
                    $forceEmptyString = ['agency_timezone','shape_id'];
                    foreach ($presentCols as $col) {
                        $varName = $userVarCols[$col] ?? $col; // devrait toujours exister
                        if (in_array($col, $forceEmptyString, true)) {
                            // On conserve la chaîne vide si fournie (pas de NULLIF)
                            $setParts[] = "$col = $varName";
                        } else {
                            // Conversion : chaîne vide -> NULL
                            $setParts[] = "$col = NULLIF($varName, '')";
                        }
                    }
                    // Colonnes non présentes -> NULL explicite si elles existent dans la table et ne sont pas auto
                    foreach ($tableCols as $col) {
                        if (in_array($col, $autoCols, true)) { continue; }
                        if (!in_array($col, $presentCols, true)) {
                            if ($col === 'location_type' && $type === 'stops') {
                                $setParts[] = "location_type = '0'";
                            } elseif (in_array($col, $forceEmptyString, true)) {
                                $setParts[] = "$col = ''";
                            } else {
                                $setParts[] = "$col = NULL";
                            }
                        }
                    }
                    $setParts[] = "provider_id = '$provider'";
                    $header = implode(',', $loadCols);
                    $set = implode(',', $setParts);

                    $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step][$type] File header : $header"], 'DEBUG');
                    $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step][$type] SQL set : $set"], 'DEBUG');
                    if (!empty($ignoredFileCols)) {
                        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step][$type] Colonnes fichier ignorées (pas en BDD ou non attendues) : " . implode(',', $ignoredFileCols)], 'INFO');
                    }
            
                    try {
                        // Clearing provider data
                        $progressBar->setMessage("Importing $type... (Clearing data...)");
                        $progressBar->advance();
            
                        $this->DB->clearProviderDataInTable($db, $temp_table, $provider);
            
                        if ($tc_provider->getParentProvider() != null) {
                            $this->DB->clearProviderDataInTable($db, $temp_table, $tc_provider->getParentProvider());
                        }
                        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step][$type] Data cleared"], 'INFO');
            
                        // Split file
                        $progressBar->setMessage("Importing $type... (Preparing import...)");
                        $progressBar->advance();
            
                        $count = $this->fileSplitter->exec($dir, $provider, $type);
            
                        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step][$type] File splitted in $count part(s) "], 'INFO');
            
                        $progressBar->setMaxSteps($progressBar->getMaxSteps() + ($count * 3));
            
                        // Clearing provider data
                        $progressBar->setMessage("Importing $type... (Preparing import...)");
                        $progressBar->advance();
                        $temp_temp_table = 'temp_temp_' . $type;
                        $this->DB->perpareTempTable($db, $temp_table, $temp_temp_table);
            
                        // Import file
                        for ($i = 1; $i <= $count; $i++) {
                            $progressBar->setMessage("Importing $type... (Importing file $i / $count...)");
                            $progressBar->advance();
            
                            $splited_file = $dir . '/' . $provider . '/' . $type . '_' . $i . '.txt';
                            // $output->writeln("$table, $splited_file, $header, $set, ','");
                            $this->DB->importFile($db, $temp_temp_table, $splited_file, $header, $set, ',');
                            // echo $temp_temp_table . PHP_EOL;
                            // echo $splited_file . PHP_EOL;
                            // echo $header . PHP_EOL;
                            // echo $set . PHP_EOL;
                        }
                        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step][$type] Data imported in temp table"], 'INFO');
            
                        // Add prefix
                        $progressBar->setMessage("Importing $type... (Add prefix...)");
                        $progressBar->advance($count);
            
                        $prefix = $provider . ':';
            
                        foreach ($columns as $column) {
                            $this->DB->prefixTable($db, $temp_temp_table, $column, $prefix);
                            $progressBar->advance(0);
                        }
    
                        // Clearing provider data
                        $progressBar->setMessage("Importing $type... (Copy data...)");
                        $progressBar->advance();
                        $this->DB->copyTable($db, $temp_temp_table, $temp_table);
            
                        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step][$type] Prefix added"], 'INFO');
            
                    } catch (\Exception $e) {
                        $this->logger->error($e, 'WARN', "[$event_id][$step][$type] Import error");
                        $output->writeln('<error>Erreur import ' . $type . ' : ' . $e->getMessage() . '</error>');
                        continue; // passe au type suivant
                    }
                }
            }

            $tc_provider->setFlag('1');
            $tc_provider->setUpdatedAt(new DateTime());
            $this->entityManager->flush();
            $progressBar->clear();
        }

        // Remove databases contraints
        $db_name = $db->getDatabase();
        $constraints = $this->DB->getConstraints($db, $db_name);
        foreach($constraints as $constraint) {
            $table = $constraint['TABLE_NAME'];
            $name = $constraint['CONSTRAINT_NAME'];
            $referenced_table = $constraint['REFERENCED_TABLE_NAME'];

            $this->DB->removeConstraints($db, $table, $name);
        }

        // On remplace les tables active par les table temporaires
        foreach ($types as $type => $columns) {
            try {
                $output->writeln("Finishing $type...");

                // On remplace la table active par la temporaire
                $table = $type;
                $temp_table = 'temp_' . $type;
                $old_table = 'old_' . $type;
                $this->DB->replaceTempTable($db, $table, $temp_table, $old_table);

                // La table de staging n'a plus d'utilité : on la supprime pour ne
                // pas laisser une copie complète des données traîner entre deux
                // exécutions (temp_temp_shapes/temp_temp_stop_times pèsent lourd).
                $this->DB->dropTable($db, 'temp_temp_' . $type);

            } catch (\Exception $e) {
                print_r($e);
                error_log($e->getMessage());
                $this->logger->error($e, 'WARN', "[$event_id] ");
                $err++;
            }
        }

        // Re-create databases contraints
        foreach($constraints as $constraint) {
            $table = $constraint['TABLE_NAME'];
            $column = $constraint['COLUMN_NAME'];
            $name = $constraint['CONSTRAINT_NAME'];
            $referenced_table = $constraint['REFERENCED_TABLE_NAME'];
            $referenced_name = $constraint['REFERENCED_COLUMN_NAME'];

            $new_referenced_table = str_replace('old_', '', $referenced_table);
            $this->DB->createConstraints($db, $table, $column, $name, $new_referenced_table, $referenced_name);
        }

        // On réactive la vérification
        $this->DB->endDBUpdate($db);
        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$step][$type] Enable FOREIGN_KEY_CHECKS"], 'INFO');


        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] GTFS imported"], 'INFO');

        foreach ($to_update as $update) {
            $tc_provider = $update['provider'];
            $tc_provider->setFlag('2');
            $tc_provider->setUpdatedAt(new DateTime());
            $this->entityManager->flush();
        }

        // Concat stops area
        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Running app:gtfs:concatstoparea"], 'INFO');
        $input = new ArrayInput([
            'command' => 'app:gtfs:concatstoparea'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);

        // Stop Area
        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Running app:gtfs:stoparea"], 'INFO');
        $input = new ArrayInput([
            'command' => 'app:gtfs:stoparea'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);

        // Stop Route
        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Running app:gtfs:stoproute"], 'INFO');
        $input = new ArrayInput([
            'command' => 'app:gtfs:stoproute'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);

        // Stop Area
        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Running app:index:update"], 'INFO');
        $input = new ArrayInput([
            'command' => 'app:index:update'
        ]);
        $returnCode = $this->getApplication()->doRun($input, $output);
        
        // On réactive la vérification
        $this->DB->endDBUpdate($db);

        // Reconstruction MOTIS : détachée, parce qu'elle se compte en heures et
        // que cette commande passe toutes les 2 heures en cron. app:motis:deploy
        // construit à l'écart, vérifie, puis bascule ; l'instance qui sert le
        // trafic n'est jamais interrompue. Un verrou de fichier dans la commande
        // empêche deux déploiements de se chevaucher, donc rien à coordonner
        // ici, et si les GTFS n'ont pas bougé le déploiement se termine tout
        // seul sans rien reconstruire.
        $this->startMotisDeploy($output, $event_id);

        // ----

        $output->writeln('<fg=white;bg=green>           </>');
        $output->writeln('<fg=white;bg=green> Ready ✅  </>');
        $output->writeln('<fg=white;bg=green>           </>');

        $this->DB->prepareStopRoute($db);

        $output->writeln('> Preparing for query...');
        $this->DB->generateQueryRoute($db);

        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Query StopRoute updated"], 'INFO');

        // Surveillance des compteurs AUTO_INCREMENT : la reconstruction ci-dessus
        // les remet à plat, on vérifie qu'aucune table ne dérive quand même.
        $output->writeln('> Checking AUTO_INCREMENT headroom...');
        foreach ($this->DB->getAutoIncrementStatus($db) as $status) {
            if ($status['usage'] === null || $status['usage'] < 50) {
                continue;
            }

            $message = sprintf(
                "[$event_id] %s.%s : AUTO_INCREMENT %d / %d (%.1f%% de %s) pour %d lignes — lancer app:db:ids:compact",
                $status['table'],
                $status['column'],
                $status['auto_increment'],
                $status['max_value'],
                $status['usage'],
                $status['column_type'],
                $status['rows']
            );

            $output->writeln('<error>' . $message . '</error>');
            $this->logger->log(['event_id' => $event_id, 'message' => $message], $status['usage'] >= 80 ? 'ERROR' : 'WARN');
        }

        $output->writeln('Finished');
        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Task ended succesfully"], 'INFO');

        // Monitoring
        file_get_contents('https://betteruptime.com/api/v1/heartbeat/SrRkcBMzc4AgsXXzzZa2qFDa');

        return Command::SUCCESS;
    }

    /**
     * Lance app:motis:deploy en tâche de fond.
     *
     * setsid détache le processus de cette commande : le déploiement survit à
     * la fin de l'import GTFS et au cron qui l'a lancé.
     */
    private function startMotisDeploy(OutputInterface $output, string $event_id): void
    {
        $project = $this->params->get('kernel.project_dir');
        $log = sys_get_temp_dir() . '/navika/motis_deploy.log';

        if (!is_dir(dirname($log))) {
            mkdir(dirname($log), 0777, true);
        }

        $command = sprintf(
            'cd %s && setsid nohup %s bin/console app:motis:deploy >> %s 2>&1 < /dev/null &',
            escapeshellarg($project),
            escapeshellarg(PHP_BINARY),
            escapeshellarg($log)
        );

        exec($command);

        $output->writeln('> Déploiement MOTIS lancé en tâche de fond (journal : ' . $log . ')');
        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] app:motis:deploy lancé en tâche de fond"], 'INFO');
    }
}