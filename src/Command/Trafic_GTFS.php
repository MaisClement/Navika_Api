<?php

namespace App\Command;

use App\Controller\Functions;
use App\Entity\Trafic;
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
        //DISABLED $dir = sys_get_temp_dir();
        //DISABLED $db = $this->entityManager->getConnection();
        //DISABLED 
        //DISABLED // --
        //DISABLED 
        //DISABLED // Récupération du trafic
        //DISABLED $output->writeln('> Geting trafic...');
        //DISABLED 
        //DISABLED $providers = $this->providerRepository->FindBy(['type' => 'tc']);
        //DISABLED 
        //DISABLED foreach ($providers as $provider) {
        //DISABLED     $url = $provider->getGtfsRtServicesAlerts();
        //DISABLED 
        //DISABLED     if ($url != null) {
        //DISABLED         $output->writeln('  > ' . $url);
        //DISABLED         
        //DISABLED         $client = HttpClient::create();
        //DISABLED         $response = $client->request('GET', $url);
        //DISABLED         $status = $response->getStatusCode();
        //DISABLED         
        //DISABLED         if ($status != 200){
        //DISABLED             error_log($status);
        //DISABLED             return Command::FAILURE;
        //DISABLED         }
        //DISABLED                         
        //DISABLED         $feed = new FeedMessage();
        //DISABLED         $feed->mergeFromString($response->getContent());
        //DISABLED 
        //DISABLED         $service_alerts = json_decode($feed->serializeToJsonString());
        //DISABLED 
        //DISABLED         $file_name = $dir . '/test_gtfsrt.pb';
        //DISABLED         file_put_contents($file_name, json_encode($service_alerts, JSON_PRETTY_PRINT));
        //DISABLED 
        //DISABLED         echo $provider->getId();
        //DISABLED 
        //DISABLED         //---
        //DISABLED 
        //DISABLED         foreach($service_alerts->entity as $alert) {
        //DISABLED         
        //DISABLED            $msg = new Trafic();
        //DISABLED            $msg->setReportId   ( $alert->id                                                                                      );
        //DISABLED            $msg->setStatus     (  $disruption->status   // based on activePeriod                                                 );
        //DISABLED            $msg->setCause      (  $disruption->cause    //                                                                       );
        //DISABLED            $msg->setCategory   (  $disruption->category //                                                                       );
        //DISABLED            $msg->setSeverity   (  Functions::getSeverity($disruption->severity->effect, $disruption->cause, $disruption->status) );
        //DISABLED            $msg->setEffect     ( $alert->alert->effect                                                                           );
        //DISABLED            $msg->setUpdatedAt  (  DateTime::createFromFormat('Ymd\THis', $disruption->updated_at)                                );
        //DISABLED            $msg->setTitle      ( $alert->alert->headerText->translation[0]->text                                                 );
        //DISABLED            $msg->setText       ( $alert->alert->descriptionText->translation[0]->text                                            );
        //DISABLED            $msg->setRouteId    (  $route                                                                                         );
        //DISABLED             
        //DISABLED         }
        //DISABLED 
        //DISABLED         exit;
        //DISABLED     }
        //DISABLED }
        //DISABLED 
        //DISABLED // Monitoring
        //DISABLED $output->writeln('> Monitoring...');
        //DISABLED 
        //DISABLED $url = 'https://uptime.betterstack.com/api/v1/heartbeat/pbe86jt9hZHP5sW93MJNxw7C';
        //DISABLED 
        //DISABLED $client = HttpClient::create();
        //DISABLED $response = $client->request('GET', $url);
        //DISABLED $status = $response->getStatusCode();
        //DISABLED 
        //DISABLED if ($status != 200){
        //DISABLED     return Command::FAILURE;
        //DISABLED }
        //DISABLED 
        //DISABLED $output->writeln('<info>✅ OK</info>');
        //DISABLED 
        return Command::SUCCESS;
    }
}
