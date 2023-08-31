<?php

namespace App\Command;

use App\Command\CommandFunctions;
use App\Controller\PointLocation;
use App\Entity\StopTown;
use App\Repository\StopsRepository;
use App\Repository\TownRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Town_Update extends Command
{
    private $entityManager;
    private $params;

    private StopsRepository $stopsRepository;
    private TownRepository $townRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, StopsRepository $stopsRepository, TownRepository $townRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->stopsRepository = $stopsRepository;
        $this->townRepository = $townRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:town:update')
            ->setDescription('Update town for stop route');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();

        // ---

        ProgressBar::setFormatDefinition('custom', '%percent%% [%bar%] %elapsed% - %remaining% | %message%');

        // ---

        // $towns = $this->townRepository->findBy(['town_id' => '56121']);
        $towns = $this->townRepository->findAll();
        $stops = $this->stopsRepository->findBy(['town_id' => null, 'location_type' => '1']);

        $progressBar = new ProgressBar($output, 100);
        $progressBar->setFormat('custom');
        $progressBar->start();
        $progressBar->setMessage('Looking for town...');

        $progressBar->setMaxSteps(count($towns) * count($stops));

        $pointLocation = new PointLocation();

        foreach ($towns as $town) {
            $polygon = $town->getTownPolygon()->toArray()[0];

            foreach ($stops as $stop) {
                $progressBar->advance();
                try {
                    $point = [$stop->getStopLon(), $stop->getStopLat()];

                    $res = $pointLocation->pointInPolygon($point, $polygon, false);

                    if ($res) {
                        $stopTown = new StopTown();
                        $stopTown->setStopId($stop);
                        $stopTown->setTownId($town);
                        $this->entityManager->persist($stopTown);
                        $this->entityManager->flush();
                    }
                } catch (\Exception $e) {
                    echo $e;
                }
            }
        }
        $this->entityManager->flush();

        $output->writeln('> Preparing for query...');
        CommandFunctions::generateQueryRoute($db);

        return Command::SUCCESS;
    }
}