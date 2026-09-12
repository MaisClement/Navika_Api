<?php

namespace App\Command\SPECIFIC;

use App\Controller\Functions;
use App\Controller\Notify;
use App\Entity\Trafic;
use App\Entity\TraficApplicationPeriods;
use App\Repository\RoutesRepository;
use App\Repository\TraficRepository;
use App\Service\Logger;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Messaging;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;

class IDFM_Trafic extends Command
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private Logger $logger;
    private Messaging $messaging;
    private RoutesRepository $routesRepository;
    private TraficRepository $traficRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        Logger $logger,
        Messaging $messaging,
        RoutesRepository $routesRepository,
        TraficRepository $traficRepository
    ) {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->logger = $logger;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eventId = uniqid();
        $this->logger->log(['event_id' => $eventId, 'message' => "[app:trafic:update:IDFM][$eventId] Task began"], 'INFO');

        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Getting trafic...');

        $disruptions = [];
        $lineReports = [];

        $page = 0;
        $itemsPerPage = 0;
        $itemsOnPage = 1;

        while ($itemsOnPage >= $itemsPerPage) {
            $url = $this->params->get('prim_url_trafic') . '/line_reports?count=1000&start_page=' . $page;
            $this->logger->log(['event_id' => $eventId, 'message' => "[$eventId] Getting IDFM trafic reports from $url"], 'INFO');

            $client = HttpClient::create();
            $response = $client->request('GET', $url, [
                'headers' => [
                    'apiKey' => $this->params->get('prim_api_key'),
                ],
            ]);
            $status = $response->getStatusCode();

            if ($status != 200) {
                $this->logger->log(['event_id' => $eventId, 'message' => "[$eventId] $url return HTTP $status error"], 'ERROR');
                return Command::FAILURE;
            }

            $progressIndicator->advance();

            $content = $response->getContent();
            file_put_contents('/tmp/navika_trafic_' . $page . '.json', $content);
            $results = json_decode($content);

            $itemsPerPage = $results->pagination->items_per_page;
            $itemsOnPage = $results->pagination->items_on_page;
            $page++;

            $disruptions = array_merge($disruptions, $results->disruptions);
            $lineReports = array_merge($lineReports, $results->line_reports);
        }

        $reports = [];
        foreach ($disruptions as $disruption) {
            $progressIndicator->advance();
            if ($disruption->status != 'past') {
                $reports['IDFM:' . $disruption->id] = $disruption;
            }
        }

        $count = 0;
        $r = [];
        foreach ($lineReports as $line) {
            $progressIndicator->advance();
            foreach (array_merge($line->line->links, $line->line->network->links) as $link) {
                $id = 'IDFM:' . $link->id;
                if ($link->type == "disruption" && isset($reports[$id])) {
                    $route = $this->routesRepository->findOneBy(['route_id' => 'IDFM:' . Functions::idfmFormat($line->line->id)]);
                    if ($route != null) {
                        $disruption = $reports[$id];
                        $msg = new Trafic();
                        $msg->setReportId('IDFM:' . $disruption->id);
                        $msg->setStatus($disruption->status);
                        $msg->setCause($disruption->cause);
                        $msg->setSeverity(Functions::getSeverity($disruption->severity->effect, $disruption->cause, $disruption->status));
                        $msg->setEffect($disruption->severity->effect);
                        $msg->setUpdatedAt(DateTime::createFromFormat('Ymd\THis', $disruption->updated_at));
                        $msg->setTitle(Functions::getReportsMesageTitle($disruption->messages));
                        $msg->setText(Functions::getReportsMesageText($disruption->messages));
                        $msg->setRouteId($route);

                        foreach ($disruption->application_periods as $applicationPeriod) {
                            $period = new TraficApplicationPeriods();
                            $period->setBegin(DateTime::createFromFormat('Ymd\THis', $applicationPeriod->begin));
                            $period->setEnd(DateTime::createFromFormat('Ymd\THis', $applicationPeriod->end));
                            $msg->addApplicationPeriod($period);
                            $this->entityManager->persist($period);
                        }

                        $this->entityManager->persist($msg);
                        $r['IDFM:' . $disruption->id] = $msg;
                        $count++;
                    }
                }
            }
        }

        $this->logger->log(['event_id' => $eventId, 'message' => "[$eventId] Saving $count trafic reports"], 'INFO');

        $progressIndicator->setMessage('Looking for notification...');
        $oldMessages = $this->traficRepository->findByLikeField('report_id', 'IDFM:');

        foreach ($oldMessages as $oldMessage) {
            $progressIndicator->advance();
            $id = $oldMessage->getReportId();
            if (isset($r[$id])) {
                unset($r[$id]);
            }
        }

        $notif = new Notify($this->messaging);

        foreach ($r as $report) {
            if ($report->getRouteId() != null) {
                foreach ($report->getRouteId()->getRouteSubs() as $sub) {
                    $progressIndicator->advance();
                    $allow = $this->isNotificationAllowed($sub, $report);
                    if ($allow) {
                        $token = $sub->getSubscriberId()->getFcmToken();
                        $title = $report->getTitle();
                        $body = $report->getText();
                        $data = [];

                        try {
                            $notif->sendNotificationToUser($this->logger, $token, $title, $body, $data);
                            $this->logger->log(['event_id' => $eventId, 'message' => "[$eventId] Trafic report notification sent to $token"], 'INFO');
                        } catch (\Exception $e) {
                            if (get_class($e) == 'Kreait\Firebase\Exception\Messaging\NotFound') {
                                $this->entityManager->remove($sub);
                                $this->logger->log(['event_id' => $eventId, 'message' => "[$eventId] Subscriber $token no longer exists and was removed"], 'INFO');
                            } else {
                                $this->logger->error($e);
                            }
                        }
                    }
                }
            }
        }

        $progressIndicator->setMessage('Remove old...');
        foreach ($oldMessages as $oldMessage) {
            $progressIndicator->advance();
            $this->entityManager->remove($oldMessage);
        }

        $progressIndicator->setMessage('Saving...');
        $this->entityManager->flush();

        $progressIndicator->setMessage('Monitoring...');
        $url = 'https://uptime.betterstack.com/api/v1/heartbeat/pbe86jt9hZHP5sW93MJNxw7C';
        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200) {
            return Command::FAILURE;
        }

        $progressIndicator->finish('<info>✅ OK</info>');
        $this->logger->log(['event_id' => $eventId, 'message' => "[$eventId] Task ended successfully"], 'INFO');

        return Command::SUCCESS;
    }

    private function isNotificationAllowed($sub, $report): bool
    {
        $allow = true;
        if ($sub->getType() == 'all' && $report->getSeverity() < 3) {
            $allow = false;
        } elseif ($sub->getType() == 'alert' && $report->getSeverity() < 4) {
            $allow = false;
        }

        $dayOfWeek = date('N');
        if (($dayOfWeek == "1" && $sub->getMonday() != "1") ||
            ($dayOfWeek == "2" && $sub->getTuesday() != "1") ||
            ($dayOfWeek == "3" && $sub->getWednesday() != "1") ||
            ($dayOfWeek == "4" && $sub->getThursday() != "1") ||
            ($dayOfWeek == "5" && $sub->getFriday() != "1") ||
            ($dayOfWeek == "6" && $sub->getSaturday() != "1") ||
            ($dayOfWeek == "7" && $sub->getSunday() != "1")) {
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

        return $allow;
    }
}
