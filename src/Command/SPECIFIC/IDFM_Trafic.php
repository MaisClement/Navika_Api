<?php

namespace App\Command\SPECIFIC;

use App\Controller\Notify;
use App\Controller\Functions;
use App\Entity\Trafic;
use App\Repository\RoutesRepository;
use App\Repository\TraficRepository;
use App\Entity\TraficApplicationPeriods;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Kreait\Firebase\Contract\Messaging;
use App\Repository\SubscribersRepository;

use Symfony\Component\Console\Helper\ProgressIndicator;

class IDFM_Trafic extends Command
{
    private $entityManager;
    private $params;

    private Messaging $messaging;
    private RoutesRepository $routesRepository;
    private TraficRepository $traficRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, Messaging $messaging, RoutesRepository $routesRepository, TraficRepository $traficRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        
        $this->messaging = $messaging;
        $this->routesRepository = $routesRepository;
        $this->traficRepository = $traficRepository;

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

        $disruptions = [];
        $line_reports = [];

        // Paramètres de pagination
        $page = 0;
        $itemsPerPage = 0;
        $itemsOnPage = 1;

        // Boucler à travers les pages jusqu'à ce que tous les résultats soient récupérés
        while ($itemsOnPage >= $itemsPerPage) {
            $url = $this->params->get('prim_url_trafic') . '/line_reports?count=1000&start_page=' . $page;

            $client = HttpClient::create();
            $response = $client->request('GET', $url, [
                'headers' => [
                    'apiKey' => $this->params->get('prim_api_key'),
                ],
            ]);
            $status = $response->getStatusCode();

            if ($status != 200) {
                echo '';
                return Command::FAILURE;
            }

            // Loader
            $progressIndicator->advance();

            $content = $response->getContent();
            $results = json_decode($content);

            // Pagination
                $itemsPerPage = $results->pagination->items_per_page;
                $itemsOnPage = $results->pagination->items_on_page;
                $page++;

            $disruptions = array_merge($disruptions, $results->disruptions);
            $line_reports = array_merge($line_reports, $results->line_reports);
        }

        // On crée les messages
        $reports = [];
        $r = [];
        foreach ($disruptions as $disruption) {
            $progressIndicator->advance();

            if ($disruption->status != 'past') {
                $reports['IDFM:' . $disruption->id] = $disruption;
            }
        }

        // On assigne une ligne aux messages
        foreach ($line_reports as $line) {
            $progressIndicator->advance();

            foreach ($line->line->links as $link) {
                $id = 'IDFM:' . $link->id;

                if ($link->type == "disruption") {
                    if (isset($reports[$id])) {
                        $route = $this->routesRepository->findOneBy(['route_id' => 'IDFM:' . Functions::idfmFormat($line->line->id)]);

                        if ($route != null) {
                            $disruption = $reports[$id];

                            $msg = new Trafic();
                            $msg->setReportId   ( 'IDFM:' . $disruption->id                                                                      );
                            $msg->setStatus     ( $disruption->status                                                                            );
                            $msg->setCause      ( $disruption->cause                                                                             );
                            $msg->setSeverity   ( Functions::getSeverity($disruption->severity->effect, $disruption->cause, $disruption->status) );
                            $msg->setEffect     ( $disruption->severity->effect                                                                  );
                            $msg->setUpdatedAt  ( DateTime::createFromFormat('Ymd\THis', $disruption->updated_at)                                );
                            $msg->setTitle      ( Functions::getReportsMesageTitle($disruption->messages)                                        );
                            $msg->setText       ( Functions::getReportsMesageText($disruption->messages)                                         );
                            $msg->setRouteId    ( $route                                                                                         );
                            
                            print_r($disruption->application_periods);

                            foreach($disruption->application_periods as $application_period) {
                                $period = new TraficApplicationPeriods();
                                $period->setBegin  ( DateTime::createFromFormat('Ymd\THis', $application_period->begin));
                                $period->setEnd  ( DateTime::createFromFormat('Ymd\THis', $application_period->end));

                                $msg->addApplicationPeriod($period);
                                $this->entityManager->persist($period);
                            }

                            $this->entityManager->persist($msg);
                            $r['IDFM:' . $disruption->id] = $msg;
                        }
                    }
                }
            }
            foreach ($line->line->network->links as $link) {
                $id = 'IDFM:' . $link->id;

                if ($link->type == "disruption") {
                    if (isset($reports[$id])) {
                        $route = $this->routesRepository->findOneBy(['route_id' => 'IDFM:' . Functions::idfmFormat($line->line->id)]);

                        if ($route != null) {
                            $disruption = $reports[$id];

                            $msg = new Trafic();
                            $msg->setReportId   ( 'IDFM:' . $disruption->id                                                                      );
                            $msg->setStatus     ( $disruption->status                                                                            );
                            $msg->setCause      ( $disruption->cause                                                                             );
                            $msg->setSeverity   ( Functions::getSeverity($disruption->severity->effect, $disruption->cause, $disruption->status) );
                            $msg->setEffect     ( $disruption->severity->effect                                                                  );
                            $msg->setUpdatedAt  ( DateTime::createFromFormat('Ymd\THis', $disruption->updated_at)                                );
                            $msg->setTitle      ( Functions::getReportsMesageTitle($disruption->messages)                                        );
                            $msg->setText       ( Functions::getReportsMesageText($disruption->messages)                                         );
                            $msg->setRouteId    ( $route                                                                                         );
                            
                            print_r($disruption->application_periods);

                            foreach($disruption->application_periods as $application_period) {
                                print($application_period);
                                $period = new TraficApplicationPeriods();
                                $period->setBegin  ( DateTime::createFromFormat('Ymd\THis', $application_period->begin));
                                $period->setEnd  ( DateTime::createFromFormat('Ymd\THis', $application_period->end));
                                
                                $msg->addApplicationPeriod($period);
                                $this->entityManager->persist($period);
                            }

                            $this->entityManager->persist($msg);
                            $r['IDFM:' . $disruption->id] = $msg;
                        }
                    }
                }
            }
        }

        // On calcule les notifications
        $progressIndicator->setMessage('Looking for notification...');

        $old_messages = $this->traficRepository->findByLikeField('report_id', 'IDFM:');

        // Pour tous les old_messages, si il existe deja un message avec le meme ReportId on supprime
        foreach ($old_messages as $old_message) {
            $progressIndicator->advance();
            $id = $old_message->getReportId();

            if (isset($r[$id])) {
                unset($r[$id]);
            }
        }

        // Init Notif
        $notif = new Notify($this->messaging);

        // On envoie les notification
        foreach($r as $report) {            
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
                    
                    if (date('N') == "1" && $sub->getMonday() != "1") {
                        $allow = false;
                    } else if (date('N') == 2 && $sub->getTuesday() != "1") {
                        $allow = false;
                    } else if (date('N') == "3" && $sub->getWednesday() != "1") {
                        $allow = false;
                    } else if (date('N') == "4" && $sub->getThursday() != "1") {
                        $allow = false;
                    } else if (date('N') == "5" && $sub->getFriday() != "1") {
                        $allow = false;
                    } else if (date('N') == "6" && $sub->getSaturday() != "1") {
                        $allow = false;
                    } else if (date('N') == "7" && $sub->getSunday() != "1") {
                        $allow = false;
                    }
                    
                    $startTime = DateTime::createFromFormat('H:i:s', $sub->getStartTime()->format('H:i:s'));
                    $endTime = DateTime::createFromFormat('H:i:s', $sub->getEndTime()->format('H:i:s'));

                    $now = new DateTime();
                    if ($endTime < $startTime) {
                        $endTime->modify('+1 day');
                    }
                    
                    if ($startTime > $now || $endTime < $now) {
                        $allow = false;
                    }
    
                    if ($allow == true) {
                        $token = $sub->getSubscriberId()->getFcmToken();
                        $title = $report->getTitle();
                        $body = $report->getText();
                        $data = [];

                        print_r(['SEND NOTIFICATIONS !']);

                        try {
                            // $notif->sendMessage($token, $report->getReportMessage() );
                            $notif->sendNotificationToUser(
                               $token,
                               $title,
                               $body,
                               $data
                            );
                        } catch (\Exception $e) {
                            if (get_class($e) == 'Kreait\Firebase\Exception\Messaging\NotFound') {
                                $this->entityManager->remove($sub);
                            }
                        }
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