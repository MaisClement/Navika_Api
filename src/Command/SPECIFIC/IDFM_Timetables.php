<?php

namespace App\Command\SPECIFIC;

use App\Controller\Functions;
use App\Entity\Timetables;
use App\Repository\RoutesRepository;
use App\Repository\TimetablesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use App\Service\Logger;
use Symfony\Component\Console\Helper\ProgressIndicator;

class IDFM_Timetables extends Command
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private Logger $logger;
    private RoutesRepository $routesRepository;
    private TimetablesRepository $timetablesRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        Logger $logger,
        TimetablesRepository $timetablesRepository,
        RoutesRepository $routesRepository
    ) {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->logger = $logger;
        $this->routesRepository = $routesRepository;
        $this->timetablesRepository = $timetablesRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:timetables:update')
            ->setDescription('Update timetables data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $file = $dir . '/timetables.csv';
        $eventId = uniqid();

        $this->logger->log(
            [
                'event_id' => $eventId,
                'message' => "[app:timetables:update][$eventId] Task began"
            ],
            'INFO'
        );

        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Getting timetable...');

        $url = $this->params->get('prim_url_timetables');
        $this->logger->log(
            [
                'event_id' => $eventId,
                'message' => "[$eventId] Getting IDFM timetables from $url"
            ],
            'INFO'
        );

        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status !== 200) {
            $this->logger->log(
                [
                    'event_id' => $eventId,
                    'message' => "[$eventId] $url returned HTTP $status error"
                ],
                'ERROR'
            );
            return Command::FAILURE;
        }

        $content = $response->getContent();
        file_put_contents($file, $content);

        $content = Functions::readCsv($file);

        $count = 0;
        foreach ($content as $row) {
            $progressIndicator->advance();

            if (!is_bool($row) && $row[0] !== 'ID_Line') {
                $routeId = 'IDFM:' . $row[0];
                $type = $row[3] === 'HORAIRE' ? 'timetables' : 'map';

                $route = $this->routesRepository->findOneBy(['route_id' => $routeId]);

                if ($route !== null) {
                    $timetable = new Timetables();
                    $timetable->setRouteId($route);
                    $timetable->setType($type);
                    $timetable->setName($row[1]);
                    $timetable->setUrl($row[2]);

                    $this->entityManager->persist($timetable);
                    $count++;
                }
            }
        }

        $this->logger->log(
            [
                'event_id' => $eventId,
                'message' => "[$eventId] Saving $count timetables"
            ],
            'INFO'
        );

        $progressIndicator->setMessage('Removing old timetables...');

        $oldTimetables = $this->timetablesRepository->findAll();

        foreach ($oldTimetables as $oldTimetable) {
            $progressIndicator->advance();
            $this->entityManager->remove($oldTimetable);
        }

        $progressIndicator->setMessage('Saving data...');
        $this->entityManager->flush();

        $progressIndicator->finish('<info>✅ OK</info>');
        $this->logger->log(
            [
                'event_id' => $eventId,
                'message' => "[$eventId] Task ended successfully"
            ],
            'INFO'
        );

        return Command::SUCCESS;
    }
}