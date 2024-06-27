<?php

namespace App\Command\SPECIFIC;

use App\Controller\Notify;
use App\Controller\Functions;
use App\Entity\Trafic;
use App\Repository\RoutesRepository;
use App\Repository\TraficRepository;
use App\Entity\TraficApplicationPeriods;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Kreait\Firebase\Contract\Messaging;
use App\Repository\SubscribersRepository;

use Symfony\Component\Console\Helper\ProgressIndicator;

class IDFM_Departures extends Command
{
    private $entityManager;
    private $params;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:departures:get:IDFM')
            ->setDescription('Get all departures');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        
        // Récupération du trafic
        $url = $this->params->get('prim_url_all_departures');
        $client = HttpClient::create();
        $response = $client->request('GET', $url, [
            'headers' => [
                'apiKey' => $this->params->get('prim_api_key'),
            ],
        ]);
        $status = $response->getStatusCode();

        if ($status != 200) {
            echo '';
            return Command::FAILURE;
        }

        $content = $response->getContent();
        $json = json_decode($content);

        $json = $json->Siri->ServiceDelivery->EstimatedTimetableDelivery[0]->EstimatedJourneyVersionFrame[0]->EstimatedVehicleJourney;
        
        $data = [];
        foreach($json as $el)  {
            $data[] = array(
                "line_id" => "",
            );
        }

        $content = json_encode($json);
        file_put_contents($dir . '/NAVIKA_idfm_departures.json', $content);

        return Command::SUCCESS;
    }
}