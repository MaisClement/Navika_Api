<?php

namespace App\Command\Index;

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

    protected static $defaultName = 'app:index:clear';
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

        $date = new \DateTime();
        $date->modify('-1 month');
        $formattedDate = $date->format('Y-m-d\TH:i:s');

        $params = [
            'index' => 'logs',
            'body' => [
            'query' => [
                'range' => [
                '@timestamp' => [
                    'lte' => $formattedDate
                ]
                ]
            ]
            ]
        ];

        $response = $client->deleteByQuery($params);

        $output->writeln('Deleted logs older than 1 month.');

        return Command::SUCCESS;
    }
}
