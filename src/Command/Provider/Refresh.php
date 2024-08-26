<?php

namespace App\Command\Provider;

use App\Command\CommandFunctions;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Console\Helper\ProgressIndicator;
use App\Service\Logger;

class Refresh extends Command
{
    private $entityManager;
    private $params;

    private Logger $logger;

    private ProviderRepository $providerRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, Logger $logger, ProviderRepository $providerRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->logger = $logger;

        $this->providerRepository = $providerRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:provider:refresh')
            ->setDescription('Refresh data for provider')
            ->addArgument('id', InputArgument::OPTIONAL, 'Id')
            ->addOption(
                'all',
                null,
                InputOption::VALUE_OPTIONAL,
                'All',
                true
            );
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->entityManager->getConnection();
        $event_id = uniqid();

        $this->logger->log(['event_id' => $event_id,'message' => "[app:provider:refresh][$event_id] Task began"], 'INFO');

        $progressIndicator  = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator ->start('Refresh provider...');

        // --
        $providers = $this->providerRepository->findAll();

        // Loader
        $progressIndicator ->advance();

        foreach($providers as $provider) {
            // Loader
            $progressIndicator ->advance();

            $id = $provider->getId();
            $uid = $provider->getUrl();
            $url = $this->params->get('open_data_url') . $provider->getUrl();

            if ($provider->getUrl() != "" && $provider->getUrl() != null) {
                $client = HttpClient::create();
                $response = $client->request('GET', $url);
                $status = $response->getStatusCode();
    
                if ($status == 200) {
                    $content = $response->getContent();
                    $results = json_decode($content);
    
                    foreach ($results->resources as $resource) {
                        switch ($resource->format) {
                            case "GTFS":
                                $provider->setGtfsUrl($resource->original_url);
                                $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$id] GTFS url updated: $resource->original_url"], 'INFO');
                                break;
    
                            case "SIRI":
                                $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$id] SIRI url updated: $resource->original_url"], 'INFO');
                                $provider->getSiriUrl($resource->original_url);
                                break;
    
                            case "gtfs-rt":
                                switch ($resource->features[0]) {
                                    case "service_alerts":
                                        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$id] GTFSRT Service Alerts url updated: $resource->original_url"], 'INFO');
                                        $provider->setGtfsRtServicesAlerts($resource->original_url);
                                        break;
    
                                    case "trip_updates":
                                        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$id] GTFSRT Trips Alerts url updated: $resource->original_url"], 'INFO');
                                        $provider->setGtfsRtTripUpdates($resource->original_url);
                                        break;
    
                                    case "vehicle_positions":
                                        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$id] GTFSRT Vehicle Position url updated: $resource->original_url"], 'INFO');
                                        $provider->setGtfsRtVehiclePositions($resource->original_url);
                                        break;
                                }
                                break;
    
                            case "gbfs":
                                $this->logger->log(['event_id' => $event_id,'message' => "[$event_id][$id] GBFS url updated: $resource->original_url"], 'INFO');
                                $provider->setGbfsUrl( str_replace("gbfs.json", "", $resource->original_url) );
                                break;
                        }
                    }
                    $this->entityManager->flush();
                } else if ($status == 404) {
                    $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] The $id provider ($uid) does not exist in the transport.data.gouv.fr API. Please check the ID"], 'WARN');
                } else {
                    $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] $url return HTTP $status error"], 'WARN');
                }
            } else {
                $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Provider $id ignored as there is no API id registered"], 'INFO');
            }
        }
        $progressIndicator ->finish('  OK ✅');
        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Task ended succesfully"], 'INFO');

        return Command::SUCCESS;
    }
}