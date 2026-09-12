<?php

namespace App\Command\GBFS;

use App\Entity\Stations;
use App\Repository\ProviderRepository;
use App\Repository\StationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;
use App\Service\Logger;

class Update extends Command
{
    private EntityManagerInterface $entityManager;
    private Logger $logger;
    private StationsRepository $stationsRepository;
    private ProviderRepository $providerRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        Logger $logger,
        StationsRepository $stationsRepository,
        ProviderRepository $providerRepository
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->stationsRepository = $stationsRepository;
        $this->providerRepository = $providerRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:gbfs:update')
            ->setDescription('Update GBFS');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();
        $eventId = uniqid();

        $this->logger->log([
            'event_id' => $eventId,
            'message' => "[app:gbfs:update][$eventId] Task began"
        ], 'INFO');

        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, [
            '⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇'
        ]);
        $progressIndicator->start('Looking for GBFS...');

        $stations = $this->stationsRepository->findAll();
        foreach ($stations as $station) {
            $this->entityManager->remove($station);
        }
        $this->entityManager->flush();

        $stations = $this->stationsRepository->findAll();
        if (count($stations) > 0) {
            $this->logger->log([
                'event_id' => $eventId,
                'message' => sprintf("[$eventId] %s bike stations were not removed", count($stations))
            ], 'WARN');
        }

        $progressIndicator->advance();

        $bikeProviders = $this->providerRepository->findBy(['type' => 'bikes']);

        foreach ($bikeProviders as $gbfs) {
            $progressIndicator->advance();

            $id = $gbfs->getId();
            $name = $gbfs->getName();
            $url = $gbfs->getGbfsUrl() . "gbfs.json";

            $this->logger->log([
                'event_id' => $eventId,
                'message' => "[$eventId][$id] Getting $name GBFS from $url"
            ], 'INFO');

            if ($url !== null) {
                try {
                    $client = HttpClient::create();
                    $response = $client->request('GET', $url);
                    $status = $response->getStatusCode();

                    $progressIndicator->advance();

                    if ($status === 200) {
                        $content = json_decode($response->getContent());

                        $feeds = $content->data->fr->feeds ?? $content->data->en->feeds ?? null;

                        if ($feeds !== null) {
                            foreach ($feeds as $feed) {
                                if ($feed->name === 'station_information') {
                                    $_client = HttpClient::create();
                                    $_response = $_client->request('GET', $feed->url);
                                    $_status = $_response->getStatusCode();

                                    if ($_status === 200) {
                                        $_content = json_decode($_response->getContent());

                                        $count = 0;
                                        foreach ($_content->data->stations as $s) {
                                            $progressIndicator->advance();

                                            if ($s->lat !== null && $s->lon !== null) {
                                                $station = new Stations();
                                                $station->setProviderId($gbfs);
                                                $station->setStationId($gbfs->getId() . ':' . $s->station_id);
                                                $station->setStationName($s->name);
                                                $station->setStationLat($s->lat);
                                                $station->setStationLon($s->lon);
                                                $station->setStationCapacity($s->capacity);

                                                $this->entityManager->persist($station);
                                            }
                                            $count++;
                                        }
                                        $this->logger->log([
                                            'event_id' => $eventId,
                                            'message' => "[$eventId][$id] $count stations saved"
                                        ], 'INFO');
                                    } else {
                                        $this->logger->log([
                                            'event_id' => $eventId,
                                            'message' => "[$eventId][$id] $feed->url returned HTTP $_status error"
                                        ], 'WARN');
                                    }
                                }
                            }
                        }
                    } else {
                        $this->logger->log([
                            'event_id' => $eventId,
                            'message' => "[$eventId][$id] $url returned HTTP $status error"
                        ], 'WARN');
                    }
                } catch (\Exception $e) {
                    error_log($e->getMessage());
                    $this->logger->error($e, 'WARN', "[$eventId][$id] ");
                }
            }
        }

        $this->entityManager->flush();

        $progressIndicator->finish('  OK ✅');
        $this->logger->log([
            'event_id' => $eventId,
            'message' => "[$eventId] Task ended successfully"
        ], 'INFO');

        return Command::SUCCESS;
    }
}
