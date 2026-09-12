<?php

namespace App\Command\Provider;

use App\Command\CommandFunctions;
use App\Repository\ProviderRepository;
use App\Service\Logger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;

class Refresh extends Command
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private Logger $logger;
    private ProviderRepository $providerRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        Logger $logger,
        ProviderRepository $providerRepository
    ) {
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->entityManager->getConnection();
        $eventId = uniqid();

        $this->logger->log(
            [
                'event_id' => $eventId,
                'message' => "[app:provider:refresh][$eventId] Task began"
            ],
            'INFO'
        );

        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Refresh provider...');

        $providers = $this->providerRepository->findAll();
        $progressIndicator->advance();

        foreach ($providers as $provider) {
            $progressIndicator->advance();

            $id = $provider->getId();
            $uid = $provider->getUrl();
            $url = $this->params->get('open_data_url') . $provider->getUrl();

            if (!empty($provider->getUrl())) {
                $client = HttpClient::create();
                $response = $client->request('GET', $url);
                $status = $response->getStatusCode();

                if ($status === 200) {
                    $content = $response->getContent();
                    $results = json_decode($content);

                    foreach ($results->resources as $resource) {
                        switch ($resource->format) {
                            case "GTFS":
                                $provider->setGtfsUrl($resource->original_url);
                                $this->logger->log(
                                    [
                                        'event_id' => $eventId,
                                        'message' => "[$eventId][$id] GTFS url updated: $resource->original_url"
                                    ],
                                    'INFO'
                                );
                                break;

                            case "SIRI":
                                $this->logger->log(
                                    [
                                        'event_id' => $eventId,
                                        'message' => "[$eventId][$id] SIRI url updated: $resource->original_url"
                                    ],
                                    'INFO'
                                );
                                $provider->setSiriUrl($resource->original_url);
                                break;

                            case "gtfs-rt":
                                foreach ($resource->features as $feature) {
                                    switch ($feature) {
                                        case "service_alerts":
                                            $this->logger->log(
                                                [
                                                    'event_id' => $eventId,
                                                    'message' => "[$eventId][$id] GTFSRT Service Alerts url updated: $resource->original_url"
                                                ],
                                                'INFO'
                                            );
                                            $provider->setGtfsRtServicesAlerts($resource->original_url);
                                            break;

                                        case "trip_updates":
                                            $this->logger->log(
                                                [
                                                    'event_id' => $eventId,
                                                    'message' => "[$eventId][$id] GTFSRT Trips Alerts url updated: $resource->original_url"
                                                ],
                                                'INFO'
                                            );
                                            $provider->setGtfsRtTripUpdates($resource->original_url);
                                            break;

                                        case "vehicle_positions":
                                            $this->logger->log(
                                                [
                                                    'event_id' => $eventId,
                                                    'message' => "[$eventId][$id] GTFSRT Vehicle Position url updated: $resource->original_url"
                                                ],
                                                'INFO'
                                            );
                                            $provider->setGtfsRtVehiclePositions($resource->original_url);
                                            break;
                                    }
                                }
                                break;

                            case "gbfs":
                                $this->logger->log(
                                    [
                                        'event_id' => $eventId,
                                        'message' => "[$eventId][$id] GBFS url updated: $resource->original_url"
                                    ],
                                    'INFO'
                                );
                                $provider->setGbfsUrl(str_replace("gbfs.json", "", $resource->original_url));
                                break;
                        }
                    }
                    $this->entityManager->flush();
                } elseif ($status === 404) {
                    $this->logger->log(
                        [
                            'event_id' => $eventId,
                            'message' => "[$eventId] The $id provider ($uid) does not exist in the transport.data.gouv.fr API. Please check the ID"
                        ],
                        'WARN'
                    );
                } else {
                    $this->logger->log(
                        [
                            'event_id' => $eventId,
                            'message' => "[$eventId] $url return HTTP $status error"
                        ],
                        'WARN'
                    );
                }
            } else {
                $this->logger->log(
                    [
                        'event_id' => $eventId,
                        'message' => "[$eventId] Provider $id ignored as there is no API id registered"
                    ],
                    'INFO'
                );
            }
        }
        $progressIndicator->finish('  OK ✅');
        $this->logger->log(
            [
                'event_id' => $eventId,
                'message' => "[$eventId] Task ended successfully"
            ],
            'INFO'
        );

        return Command::SUCCESS;
    }
}