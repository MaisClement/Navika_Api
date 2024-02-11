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

class Refresh extends Command
{
    private ProviderRepository $providerRepository;
    private $entityManager;
    private $params;


    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, ProviderRepository $providerRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

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

        $progressIndicator  = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator ->start('Refresh provider...');

        // --
        $providers = $this->providerRepository->findAll();

        // Loader
        $progressIndicator ->advance();

        foreach($providers as $provider) {
            // Loader
            $progressIndicator ->advance();
            
            $url = $this->params->get('open_data_url') . $provider->getUrl();

            // echo $provider->getUrl();

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
                            break;

                        case "SIRI":
                            $provider->getSiriUrl($resource->original_url);
                            break;

                        case "gtfs-rt":
                            switch ($resource->features[0]) {
                                case "service_alerts":
                                    $provider->setGtfsRtServicesAlerts($resource->original_url);
                                    break;

                                case "trip_updates":
                                    $provider->setGtfsRtTripUpdates($resource->original_url);
                                    break;

                                case "vehicle_positions":
                                    $provider->setGtfsRtVehiclePositions($resource->original_url);
                                    break;
                            }
                            break;

                        case "gbfs":
                            $provider->setGbfsUrl($resource->original_url);
                            break;
                    }
                }
                $this->entityManager->flush();
            }
        }
        $progressIndicator ->finish('  OK ✅');
        
        return Command::SUCCESS;
    }
}