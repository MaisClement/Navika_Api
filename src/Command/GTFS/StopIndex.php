<?php

namespace App\Command\GTFS;

use App\Command\CommandFunctions;
use App\Service\DBServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Repository\StopsRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Elastic\Elasticsearch\ClientBuilder;

class StopIndex extends Command
{
    private $entityManager;
    private $params;

    private StopsRepository $stopsRepository;
    private DBServices $dbServices;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, StopsRepository $stopsRepository, DBServices $dbServices)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->dbServices = $dbServices;
        $this->stopsRepository = $stopsRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:index:update')
            ->setDescription('Update gtfs');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();

        $client = ClientBuilder::create()
        ->setHosts($this->params->get('elastic_hosts'))
        ->setBasicAuthentication($this->params->get('elastic_user'), $this->params->get('elastic_pswd'))
        ->setCABundle($this->params->get('elastic_cert'))
        ->build();

        // --
    
        // Remove all
        $params = [
            'index' => 'stops',
            'body' => [
                'query' => [
                    'match_all' => new \stdClass()
                ]
            ]
        ];

        $client->deleteByQuery($params);
        // return $response;

        // Add by batch

        $stops_to_add = $this->stopsRepository->findAllByLocationType(1);
        
        $params = ['body' => []];

        $i = 1;
        foreach ($stops_to_add as $stop) {
            print( $stop->getStopId());
            $params['body'][] = [
                'index' => [
                    '_index' => 'stops',
                    '_id'    => $stop->getStopId(),
                ],
            ];
            $params['body'][] = [
                'name' => $stop->getStopName(),
            ];

            if ($i % 1000 == 0) {
                $responses = $client->bulk($params);

                $params = ['body' => []];

                unset($responses);
            }
            $i++;
        }

        // Send the last batch if it exists
        if (!empty($params['body'])) {
            $responses = $client->bulk($params);
        }

        return Command::SUCCESS;
    }
}