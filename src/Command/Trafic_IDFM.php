<?php

namespace App\Command;

use App\Controller\Notify;
use App\Controller\Functions;
use App\Entity\Trafic;
use App\Repository\RoutesRepository;
use App\Repository\TraficRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;
use App\Repository\SubscribersRepository;

use Symfony\Component\Console\Helper\ProgressIndicator;

class Trafic_IDFM extends Command
{
    private $entityManager;
    private $params;

    private Messaging $messaging;
    private RoutesRepository $routesRepository;
    private TraficRepository $traficRepository;
    private SubscribersRepository $subscribersRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, Messaging $messaging, RoutesRepository $routesRepository, TraficRepository $traficRepository, SubscribersRepository $subscribersRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        
        $this->messaging = $messaging;
        $this->routesRepository = $routesRepository;
        $this->traficRepository = $traficRepository;
        $this->subscribersRepository = $subscribersRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:trafic:update:IDFM')
            ->setDescription('Update trafic data');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        // Récupération du trafic
        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Geting trafic...');

        $url = $this->params->get('prim_url') . '/line_reports?count=10000';

        $client = HttpClient::create();
        $response = $client->request('GET', $url, [
            'headers' => [
                'apiKey' => $this->params->get('prim_api_key'),
            ],
        ]);
        $status = $response->getStatusCode();

        if ($status != 200) {
            return Command::FAILURE;
        }

        // Loader
        $progressIndicator->advance();

        $content = $response->getContent();
        $results = json_decode($content);

        // On crée les messages
        $reports = [];
        foreach ($results->disruptions as $disruption) {
            $progressIndicator->advance();

            if ($disruption->status != 'past') {
                $msg = new Trafic();
                $msg->setReportId($disruption->id ?? '');
                $msg->setStatus($disruption->status);
                $msg->setCause($disruption->cause);
                $msg->setCategory($disruption->category);
                $msg->setSeverity(Functions::getSeverity($disruption->severity->effect, $disruption->cause, $disruption->status));
                $msg->setEffect($disruption->severity->effect);
                $msg->setUpdatedAt(DateTime::createFromFormat('Ymd\THis', $disruption->updated_at));
                $msg->setTitle(Functions::getReportsMesageTitle($disruption->messages));
                $msg->setText(Functions::getReportsMesageText($disruption->messages));

                $reports[$disruption->id] = $msg;
            }
        }

        // On assigne une ligne aux messages
        foreach ($results->line_reports as $line) {
            $progressIndicator->advance();

            foreach ($line->line->links as $link) {
                $id = $link->id;

                if ($link->type == "disruption") {
                    if (isset($reports[$id])) {
                        $route = $this->routesRepository->findOneBy(['route_id' => 'IDFM:' . Functions::idfmFormat($line->line->id)]);

                        if ($route != null) {
                            $r = $reports[$id];
                            $r->setRouteId($route);
                            $this->entityManager->persist($r);
                        }
                    }
                }
            }
        }

        // On calcule les notifications
        $progressIndicator->setMessage('Looking for notification...');

        $old_messages = $this->traficRepository->findAll();

        // Pour tous les old_messages, si il existe deja un message avec le meme ReportId on supprime
        foreach ($old_messages as $old_message) {
            $progressIndicator->advance();
            $id = $old_message->getReportId();

            if (isset($reports[$id])) {
                unset($reports[$id]);
            }
        }

        // Init Notif
        $notif = new Notify($this->messaging);

        // On envoie les notification
        foreach($reports as $report) {

            //TOPIC  if ($report->getRouteId() != null) {
            //TOPIC      $topic = str_replace( ':' , '_', $report->getRouteId()->getRouteId() );
            //TOPIC      $title = $report->getTitle();
            //TOPIC      $body = $report->getText();
            //TOPIC
            //TOPIC      $notif->sendNotificationToTopic($topic, $title, $body);
            //TOPIC  }
            
            if ($report->getRouteId() != null) {
                foreach ($report->getRouteId()->getRouteSubs() as $sub) {
                    $progressIndicator->advance();

                    // On vérifie que l'on soit ne soit pas un jour interdit
                    $allow = true;
                   
                    if ($sub->getType() == 'all' && $report->getSeverity() < 3 ) {
                        $allow = false;
                    } else if ($sub->getType() == 'alert' && $report->getSeverity() < 4 ) {
                        $allow = false;
                    }
    
                    if ($allow == true) {
                        $token = $sub->getSubscriberId()->getFcmToken();
                        $title = $report->getTitle();
                        $body = $report->getText();
                
                        $res = $notif->sendMessage($token, $report->getReportMessage() );
        
                        // if ($res == 'NotFound') {
                        //     $this->entityManager->remove($sub);
                        // }
                    }
                }
            }
        }

        // On supprime
        $progressIndicator->setMessage('Remove old...');

        // On efface les messages existant
        foreach ($old_messages as $old_message) {
            $progressIndicator->advance();
            $this->entityManager->remove($old_message);
        }

        // On sauvegarde
        $progressIndicator->setMessage('Saving...');

        $this->entityManager->flush();

        // Monitoring
        $progressIndicator->setMessage('Monitoring...');

        $url = 'https://uptime.betterstack.com/api/v1/heartbeat/pbe86jt9hZHP5sW93MJNxw7C';

        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200) {
            return Command::FAILURE;
        }

        $progressIndicator->finish('<info>✅ OK</info>');

        return Command::SUCCESS;
    }
}