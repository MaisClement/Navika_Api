<?php

namespace App\Command\GBFS;

use App\Entity\Stations;
use App\Repository\ProviderRepository;
use App\Repository\StationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Console\Helper\ProgressIndicator;

class Update extends Command
{
    private $entityManager;

    private StationsRepository $stationsRepository;
    private ProviderRepository $providerRepository;
    
    public function __construct(EntityManagerInterface $entityManager, StationsRepository $stationsRepository, ProviderRepository $providerRepository)
    {
        $this->entityManager = $entityManager;

        $this->providerRepository = $providerRepository;
        $this->stationsRepository = $stationsRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:gbfs:update')
            ->setDescription('Update gbfs');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();

        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Looking for GBFS...');

        // ---
        $stations = $this->stationsRepository->findAll();
        foreach($stations as $station) {
            $this->entityManager->remove($station);
        }
        $this->entityManager->flush();

        // Loader
        $progressIndicator->advance();

        // ---
        $bike_providers = $this->providerRepository->FindBy(['type' => 'bikes']);

        foreach ($bike_providers as $gbfs) {
            // Loader
            $progressIndicator->advance();

            // ---
            $url = $gbfs->getGbfsUrl();

            if ($url != null) {
                try {
                    $client = HttpClient::create();
                    $response = $client->request('GET', $url);
                    $status = $response->getStatusCode();
    
                    // Loader
                    $progressIndicator->advance();
    
                    if ($status == 200) {
                        $content = $response->getContent();
                        $content = json_decode($content);
    
                        // ---
    
                        $content = file_get_contents($url);
                        $content = json_decode($content);
                                        
                        if (isset($feeds)) {
                            unset($feeds);
                        }
    
                        if (isset($content->data->fr)) {
                            $feeds = $content->data->fr->feeds;
                        } elseif (isset($content->data->en)) {
                            $feeds = $content->data->en->feeds;
                        } else {
                            echo '🤔';
                        }
    
                        if (isset($feeds)) {
                            foreach ($feeds as $feed) {
    
                                if ($feed->name == 'station_information') {
                                    $_client = HttpClient::create();
                                    $_response = $_client->request('GET', $feed->url);
                                    $_status = $response->getStatusCode();
    
                                    if ($_status != 200) {
                                        return Command::FAILURE;
                                    }
    
                                    $_content = $_response->getContent();
                                    $_content = json_decode($_content);
                                
                                    foreach ($_content->data->stations as $s) {
                                
                                        // Loader
                                        $progressIndicator->advance();
                                        
                                        if ( !is_null( $s->lat ) && !is_null( $s->lon )) {
                                            $st = new Stations();
                                            $st->setProviderId( $gbfs );
                                            $st->setStationId( $gbfs->getId() . ':' . $s->station_id );
                                            $st->setStationName( $s->name );
                                            $st->setStationLat( $s->lat );
                                            $st->setStationLon( $s->lon );
                                            $st->setStationCapacity( $s->capacity );
                
                                            $this->entityManager->persist($st);
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch(\Exception $e){
                    error_log($e->getMessage());
                }
            }
        }
        
        $this->entityManager->flush();
        
        $progressIndicator->finish('  OK ✅');
        
        return Command::SUCCESS;
    }
}