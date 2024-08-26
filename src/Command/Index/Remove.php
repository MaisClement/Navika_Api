<?php

namespace App\Command\Index;

use Elastic\Elasticsearch\ClientBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Remove extends Command
{
    private $entityManager;
    private $params;

    protected static $defaultName = 'app:index:remove';
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

        $params = [
            'index' => 'stops'
        ];
        $response = $client->indices()->delete($params);

        $params = [
            'index' => 'logs'
        ];
        $response = $client->indices()->delete($params);


        return Command::SUCCESS;
    }
}
