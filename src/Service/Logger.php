<?php
// src/Service/Logger.php
namespace App\Service;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Elastic\Elasticsearch\ClientBuilder;

class Logger
{
    private $params;
    private $client;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;

        $this->client = ClientBuilder::create()
        ->setHosts($this->params->get('elastic_hosts'))
        ->setBasicAuthentication($this->params->get('elastic_user'), $this->params->get('elastic_pswd'))
        ->setCABundle($this->params->get('elastic_cert'))
        ->build();
    }

    public function log(array $data, $level = 'INFO'): void
    {
        $data['@timestamp'] = date('c');
        $data['level']      = $level;

        $params = [
            'index' => 'logs',
            'body'  => $data
        ];

        $this->client->index($params);
    }

    public function error($exception, $level = 'ERROR', $message = null): void
    {
        $data = [
            'message' =>  $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' =>    $exception->getTraceAsString(),
            '@timestamp' => date('c'),
        ];

        if ($message != null){
            $data['message'] =  sprintf( '%s %s',
                $message,
                $exception->getMessage()
            );
        }
        $data['level'] = $level;

        $params = [
            'index' => 'logs',
            'body'  => $data
        ];

        $this->client->index($params);
    }
}