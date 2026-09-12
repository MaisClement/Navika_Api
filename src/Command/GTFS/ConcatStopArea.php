<?php

namespace App\Command\GTFS;

use App\Entity\Stops;
use App\Repository\StopsRepository;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\Logger;

class ConcatStopArea extends Command
{
    private EntityManagerInterface $entityManager;
    private Logger $logger;
    private StopsRepository $stopsRepository;
    private ProviderRepository $providerRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        Logger $logger,
        ProviderRepository $providerRepository,
        StopsRepository $stopsRepository
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->stopsRepository = $stopsRepository;
        $this->providerRepository = $providerRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:gtfs:concatstoparea')
            ->setDescription('Update gtfs');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();
        $eventId = uniqid();

        $this->logger->log([
            'event_id' => $eventId,
            'message' => "[app:gtfs:concatstoparea][$eventId] Task began"
        ], 'INFO');

        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Concat Stop Area...');

        $newStops = [];
        $providers = $this->providerRepository->findBy(['type' => 'tc']);

        foreach ($providers as $provider) {
            if ($provider->getParentProvider() != "" && $provider->getParentProvider() != null) {
                $parentProvider = $this->providerRepository->findOneBy(['id' => $provider->getParentProvider()]);

                $stops = $this->stopsRepository->findBy([
                    'location_type' => '1',
                    'provider_id' => $provider->getId()
                ]);

                foreach ($stops as $stop) {
                    $progressIndicator->advance();

                    $id = $stop->getStopId();
                    $id = str_replace($provider->getId(), $parentProvider->getId(), $id);

                    if (!isset($newStops[$id])) {
                        $newStop = clone $stop;
                        $newStop->setStopId($id);
                        $newStop->setProviderId($parentProvider);

                        $this->entityManager->persist($newStop);

                        $newStops[$id] = $newStop;
                    }
                    $stop->setLocationType('0');
                    $stop->setParentStation($id);

                    $subStops = $this->stopsRepository->findBy(['parent_station' => $stop->getStopId()]);
                    foreach ($subStops as $subStop) {
                        $subStop->setParentStation($id);
                    }
                }
            }
        }

        $this->entityManager->flush();

        $progressIndicator->finish('✅ OK');
        $this->logger->log([
            'event_id' => $eventId,
            'message' => "[$eventId] Task ended successfully"
        ], 'INFO');

        return Command::SUCCESS;
    }
}