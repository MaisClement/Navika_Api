<?php

namespace App\Command\Logs;

use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\ClientBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Clear extends Command
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;

    protected static $defaultName = 'app:logs:clear';
    private $client;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = ClientBuilder::create()
            ->setHosts($this->params->get('elastic_hosts'))
            ->setBasicAuthentication($this->params->get('elastic_user'), $this->params->get('elastic_pswd'))
            ->setCABundle($this->params->get('elastic_cert'))
            ->build();

        // Calculate the date one month ago
        $oneMonthAgo = date('c', strtotime('-1 month'));

        // Construct the query to delete logs older than one month
        $params = [
            'index' => 'logs',
            'body' => [
                'query' => [
                    'range' => [
                        '@timestamp' => [
                            'lt' => $oneMonthAgo
                        ]
                    ]
                ]
            ]
        ];

        // Execute the delete by query
        $this->client->deleteByQuery($params);
        $output->writeln('Old logs deleted');

        return Command::SUCCESS;
    }
}
