<?php

namespace App\Command;

use App\Controller\Functions;
use App\Entity\Trafic;
use App\Entity\TraficLinks;
use App\Repository\RoutesRepository;
use App\Repository\TraficRepository;
use App\Repository\ProviderRepository;
use Google\Transit\Realtime\FeedMessage;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;

class Trafic_GTFS extends Command
{
    private $entityManager;
    private $params;
    
    private ProviderRepository $providerRepository;
    private RoutesRepository $routesRepository;
    private TraficRepository $traficRepository;
    
    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, ProviderRepository $providerRepository, RoutesRepository $routesRepository, TraficRepository $traficRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        
        $this->providerRepository = $providerRepository;
        $this->routesRepository = $routesRepository;
        $this->traficRepository = $traficRepository;

        parent::__construct();
    }
 
    protected function configure(): void
    {
        $this
            ->setName('app:trafic:update')
            ->setDescription('Update trafic from gtfs rt');
    }
    
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();
        
        // --
        
        // Récupération du trafic
        $output->writeln('> Geting trafic...');
        
        $providers = $this->providerRepository->FindBy(['type' => 'tc']);
        
        foreach ($providers as $provider) {
            $url = $provider->getGtfsRtServicesAlerts();
        
            if ($url != null) {
                $output->writeln('  > ' . $url);
                
                $client = HttpClient::create();
                $response = $client->request('GET', $url);
                $status = $response->getStatusCode();
                
                if ($status != 200){
                    error_log($status);
                    return Command::FAILURE;
                }
                                
                $feed = new FeedMessage();
                $feed->mergeFromString($response->getContent());
        
                $service_alerts = json_decode($feed->serializeToJsonString());
        
                $file_name = $dir . '/test_gtfsrt.pb';
                file_put_contents($file_name, json_encode($service_alerts, JSON_PRETTY_PRINT));
        
                echo $provider->getId();
        
                //---
                $cause = array(
                    'UNKNOWN_CAUSE' => 'perturbation',
                    'OTHER_CAUSE' => 'perturbation',
                    'TECHNICAL_PROBLEM' => 'perturbation',
                    'STRIKE' => 'perturbation',
                    'DEMONSTRATION' => 'perturbation',
                    'ACCIDENT' => 'perturbation',
                    'HOLIDAY' => 'perturbation',
                    'WEATHER' => 'perturbation',
                    'MAINTENANCE' => 'travaux',
                    'CONSTRUCTION' => 'travaux',
                    'POLICE_ACTIVITY' => 'perturbation',
                    'MEDICAL_EMERGENCY' => 'perturbation'
                );
        
                foreach($service_alerts->entity as $alert) {
                    foreach( $alert->alert->informedEntity as $informedEntity) {
                        if (isset($informedEntity->routeId)) {
                            $route = $this->routesRepository->findOneBy(['route_id' => $provider->getId() . ':' . $informedEntity->routeId ]);

                            if ($route != null) {
                                $link = new TraficLinks();
                                $link->setLink        ( $alert->alert->url->translation[0]->text );
                            
                                $msg = new Trafic();
                                $msg->setReportId   ( $alert->id                                                                                      );
                                $msg->setStatus     (  $disruption->status                                                    ); // based on activePeriod
                                $msg->setCause      ( $cause[$alert->alert->cause]                                                                    );
                                $msg->setSeverity   (  Functions::getSeverity($disruption->severity->effect, $disruption->cause, $disruption->status) );
                                $msg->setEffect     ( $alert->alert->effect                                                                           );
                                $msg->setUpdatedAt  (  DateTime::createFromFormat('Ymd\THis', $disruption->updated_at)                                );
                                $msg->setTitle      ( $alert->alert->headerText->translation[0]->text                                                 );
                                $msg->setText       ( $alert->alert->descriptionText->translation[0]->text                                            );
                                $msg->setRouteId    ( $route                                                                                         );
                                
                                $msg->addTraficLink ( $link );

                                $this->entityManager->persist($msg);
                            }
                        }
                    }
                }
            }
        }

        $this->entityManager->flush();
        
        // Monitoring
        $output->writeln('> Monitoring...');
        
        $url = 'https://uptime.betterstack.com/api/v1/heartbeat/pbe86jt9hZHP5sW93MJNxw7C';
        
        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();
        
        if ($status != 200){
            return Command::FAILURE;
        }
        
        $output->writeln('<info>✅ OK</info>');
        
        return Command::SUCCESS;
    }
}
