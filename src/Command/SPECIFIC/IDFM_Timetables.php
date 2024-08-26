<?php

namespace App\Command\SPECIFIC;

use App\Controller\Functions;
use App\Entity\Timetables;
use App\Repository\RoutesRepository;
use App\Repository\TimetablesRepository;
use DateTime;
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
    private $entityManager;
    private $params;

    private Logger $logger;

    private RoutesRepository $routesRepository;
    private TimetablesRepository $timetablesRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, Logger $logger, TimetablesRepository $timetablesRepository, RoutesRepository $routesRepository)
    {
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

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $file = $dir . '/timetables.csv';
        $event_id = uniqid();

        $this->logger->log(['event_id' => $event_id,'message' => "[app:timetables:update][$event_id] Task began"], 'INFO');


        // Récupération du trafic
        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Geting timetable...');
        
        $url = $this->params->get('prim_url_timetables');
        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Getting IDFM timetables from $url"], 'INFO');
        
        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200) {
            $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] $url return HTTP $status error"], 'ERROR');
            return Command::FAILURE;
        }

        $content = $response->getContent();
        file_put_contents($file, $content);

        $content = Functions::readCsv($file);

        $count = 0;
        foreach ($content as $row) {
            // Loader
            $progressIndicator->advance();

            if (!is_bool($row) && $row[0] != 'ID_Line') {

                $route_id = 'IDFM:' . $row[0];
                $type = $row[3] == 'HORAIRE' ? 'timetables' : 'map';

                $route = $this->routesRepository->findOneBy(['route_id' => $route_id]);

                if ($route != null) {
                    $timetables = new Timetables();
                    $timetables->setRouteId($route);
                    $timetables->setType($type);
                    $timetables->setName($row[1]);
                    $timetables->setUrl($row[2]);

                    $this->entityManager->persist($timetables);
                    $count++;
                }
            }
        }

        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Saving $count timetables"], 'INFO');
        
        // On efface les messages existant
        $progressIndicator->setMessage('Removing old timetables...');

        $old_timetables = $this->timetablesRepository->findAll();

        foreach ($old_timetables as $old_timetables) {

            // Loader
            $progressIndicator->advance();

            $this->entityManager->remove($old_timetables);
        }

        // On sauvegarde
        $progressIndicator->setMessage('Saving data...');

        $this->entityManager->flush();

        $progressIndicator->finish('<info>✅ OK</info>');
        $this->logger->log(['event_id' => $event_id,'message' => "[$event_id] Task ended succesfully"], 'INFO');

        return Command::SUCCESS;
    }
}