<?php

namespace App\Command\GTFS;

use App\Entity\Stops;
use App\Repository\StopsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\Logger;

class StopArea extends Command
{
    private $entityManager;

    private Logger $logger;

    private StopsRepository $stopsRepository;

    public function __construct(EntityManagerInterface $entityManager, Logger $logger, StopsRepository $stopsRepository)
    {
        $this->entityManager = $entityManager;

        $this->logger = $logger;

        $this->stopsRepository = $stopsRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:gtfs:stoparea')
            ->setDescription('Update gtfs');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();
        $event_id = uniqid();

        $this->logger->log(['event_id' => $event_id,'message' => "[app:gtfs:stoparea][$event_id] Task began"], 'INFO');

        
        // --
        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Generate Stop Area...');
        // ----

        $s = [];
        $stops = $this->stopsRepository->FindBy(['location_type' => '0', 'parent_station' => null]);

        foreach ($stops as $stop) {
            $progressIndicator->advance();
            $id = $stop->getProviderId()->getId() . $stop->getStopName();

            if (!isset($s[$id])) {
                $s[$id] = array(
                    'provider_id' => $stop->getProviderId(),
                    'stop_id' => 'ADMIN:' . $stop->getStopId(),
                    'stop_code' => $stop->getStopCode() ?? '',
                    'stop_name' => $stop->getStopName(),
                    'stop_lat' => $stop->getStopLat(),
                    'stop_lon' => $stop->getStopLon(),
                    'stops' => array(),
                );
            }
            $s[$id]['stops'][] = $stop->getStopId();
        }

        foreach ($s as $stop) {
            $progressIndicator->advance();
            $stp = new Stops();
            $stp->setProviderId($stop['provider_id']);
            $stp->setStopId($stop['stop_id']);
            $stp->setStopCode($stop['stop_code']);
            $stp->setStopName($stop['stop_name']);
            $stp->setStopLat($stop['stop_lat']);
            $stp->setStopLon($stop['stop_lon']);
            $stp->setLocationType('1');
            $stp->setWheelchairBoarding('0');

            $this->entityManager->persist($stp);

            foreach ($stop['stops'] as $child_stop) {
                $el = $this->stopsRepository->FindOneBy(['stop_id' => $child_stop]);

                $el->setParentStation($stop['stop_id']);

                $this->entityManager->persist($el);
            }
        }
        $this->entityManager->flush();

        // Loader
        $progressIndicator->finish('✅ OK');
        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Task ended succesfully"], 'INFO');

        return Command::SUCCESS;
    }
}