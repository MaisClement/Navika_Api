<?php

namespace App\Command\Index;

use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\ClientBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Command
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;

    protected static $defaultName = 'app:index:create';
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
            'index' => 'stops',
            'body' => [
                'mappings' => [
                    'properties' => [
                        'name' => [
                            'type' => 'text',
                            'analyzer' => 'standard',
                        ],
                    ],
                ],
            ],
        ];

        $response = $client->indices()->create($params);
        $output->writeln('Index created: ' . json_encode($response));

        $params = [
            'index' => 'logs',
            'body' => [
                'mappings' => [
                    'properties' => [
                        '@timestamp' => [
                            'type' => 'date'
                        ],
                        'level' => [
                            'type' => 'keyword'
                        ],
                        'message' => [
                            'type' => 'text'
                        ],
                        'trace' => [
                            'type' => 'text'
                        ],
                        'context' => [
                            'type' => 'object'
                        ]
                    ]
                ]
            ]
        ];
        $response = $client->indices()->create($params);
        $output->writeln('Index created: ' . json_encode($response));

        return Command::SUCCESS;
    }
}
