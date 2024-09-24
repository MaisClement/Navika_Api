<?php

namespace App\Command\GTFS;

use App\Controller\Notify;
use App\Controller\Functions;
use App\Command\GTFS\CommandFunctions;
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
use Kreait\Firebase\Contract\Messaging;
use App\Service\Logger;
use Symfony\Component\Console\Helper\ProgressIndicator;

class Trafic_GTFS extends Command
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;

    private Logger $logger;

    private Messaging $messaging;
    private ProviderRepository $providerRepository;
    private RoutesRepository $routesRepository;
    private TraficRepository $traficRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, Logger $logger, Messaging $messaging, ProviderRepository $providerRepository, RoutesRepository $routesRepository, TraficRepository $traficRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->logger = $logger;

        $this->messaging = $messaging;
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
        $event_id = uniqid();

        $this->logger->log(['event_id' => $event_id, 'message' => "[app:trafic:update][$event_id] Task began"], 'INFO');

        // --

        // Récupération du trafic
        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Geting trafic...');

        // Récupération du trafic
        $output->writeln('> Geting trafic...');

        $providers = $this->providerRepository->findBy(['type' => 'tc']);

        foreach ($providers as $provider) {
            $id = $provider->getId();
            $name = $provider->getName();
            $url = $provider->getGtfsRtServicesAlerts();
            $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$id] Getting $name GTFSRT trafic reports from $url"], 'INFO');

            // Loader
            $progressIndicator->advance();

            if ($url != null) {
                $output->writeln('  > ' . $url);

                $client = HttpClient::create();
                $response = $client->request('GET', $url);
                $status = $response->getStatusCode();

                if ($status == 200) {
                    // Loader
                    $progressIndicator->advance();

                    $feed = new FeedMessage();
                    $feed->mergeFromString($response->getContent());

                    $service_alerts = json_decode($feed->serializeToJsonString());

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
                    $r = [];

                    $count = count($service_alerts->entity);
                    $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$id] Reading $count trafic reports"], 'INFO');

                    $count = 0;
                    foreach ($service_alerts->entity as $alert) {
                        foreach ($alert->alert->informedEntity as $informedEntity) {

                            // Loader
                            $progressIndicator->advance();

                            if (isset($informedEntity->routeId)) {
                                $route = $this->routesRepository->findOneBy(['route_id' => $provider->getId() . ':' . $informedEntity->routeId]);

                                if ($route != null) {
                                    $status = CommandFunctions::getStatusFromActivePeriods($alert->alert->activePeriod);

                                    if ($status != 'past') {
                                        $msg = new Trafic();
                                        $msg->setReportId($provider->getId() . ':' . $alert->id);
                                        $msg->setStatus($status); // based on activePeriod
                                        $msg->setCause($cause[$alert->alert->cause]);
                                        $msg->setSeverity(Functions::getSeverity($alert->alert->effect, $cause[$alert->alert->cause], $status));
                                        $msg->setEffect($alert->alert->effect);
                                        $msg->setTitle($alert->alert->headerText->translation[0]->text);
                                        $msg->setText($alert->alert->descriptionText->translation[0]->text);
                                        $msg->setRouteId($route);

                                        if (isset($alert->alert->url)) {
                                            $link = new TraficLinks();
                                            $link->setLink($alert->alert->url->translation[0]->text);
                                            $this->entityManager->persist($link);
                                            $msg->addTraficLink($link);
                                        }

                                        $this->entityManager->persist($msg);
                                        $r[$provider->getId() . ':' . $alert->id] = $msg;
                                        $count++;
                                    }
                                }
                            }
                        }
                    }

                    $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$id] Saving $count trafic reports"], 'INFO');

                    // On calcule les notifications
                    $progressIndicator->setMessage('Looking for notification...');

                    $old_messages = $this->traficRepository->findByLikeField('report_id', $provider->getId() . ':');

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
                    foreach ($r as $report) {
                        if ($report->getRouteId() != null) {
                            foreach ($report->getRouteId()->getRouteSubs() as $sub) {
                                $progressIndicator->advance();

                                // On vérifie que l'on soit ne soit pas un jour interdit
                                $allow = true;

                                if ($sub->getType() == 'all' && $report->getSeverity() < 3) {
                                    $allow = false;
                                } else if ($sub->getType() == 'alert' && $report->getSeverity() < 4) {
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

                                    try {
                                        // $notif->sendMessage($token, $report->getReportMessage() );
                                        $notif->sendNotificationToUser(
                                            $this->logger,
                                            $token,
                                            $title,
                                            $body,
                                            $data
                                        );
                                        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Trafic report notification sent to $token"], 'INFO');

                                    } catch (\Exception $e) {
                                        if (get_class($e) == 'Kreait\Firebase\Exception\Messaging\NotFound') {
                                            $this->entityManager->remove($sub);
                                            $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Subscriber $token no longer exists and was removed"], 'INFO');
                                        } else {
                                            $this->logger->error($e);
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
                } else {
                    error_log($status);
                    $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id][$id] $url return HTTP $status error"], 'WARN');
                }
            }
        }

        // On sauvegarde
        $progressIndicator->setMessage('Saving...');
        $this->entityManager->flush();

        // Monitoring
        $output->writeln('> Monitoring...');

        $url = 'https://uptime.betterstack.com/api/v1/heartbeat/pbe86jt9hZHP5sW93MJNxw7C';

        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200) {
            return Command::FAILURE;
        }

        $output->writeln('<info>✅ OK</info>');
        $this->logger->log(['event_id' => $event_id, 'message' => "[$event_id] Task ended succesfully"], 'INFO');

        return Command::SUCCESS;
    }
}
