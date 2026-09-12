<?php

namespace App\Command\SPECIFIC;

use App\Controller\Functions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;

class IDFM_Departures extends Command
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
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
            echo 'Pas content' . PHP_EOL;
            return Command::FAILURE;
        }

        $content = $response->getContent();
        $json = json_decode($content);

        $json = $json->Siri->ServiceDelivery->EstimatedTimetableDelivery[0]->EstimatedJourneyVersionFrame[0]->EstimatedVehicleJourney;

        $data = [];
        $stops = [];
        foreach ($json as $el) {
            $order = 0;
            $len = count($el->EstimatedCalls->EstimatedCall);
            $stopDateTime = [];

            foreach ($el->EstimatedCalls->EstimatedCall as $stop) {
                $call = $stop;

                $stopId = 'IDFM:' . Functions::idfmFormat($stop->StopPointRef->value);
                $stopName = count($stop->DestinationDisplay) > 0 ? Functions::gareFormat($stop->DestinationDisplay[0]->value) : '';
                $stop = null;

                $state = [
                    "ON_TIME" => 'unchanged',
                    "EARLY" => 'unchanged',
                    "ARRIVED" => 'unchanged',
                    "CANCELLED" => 'deleted',
                    "MISSED" => 'deleted',
                    "DELAYED" => 'delayed',
                    "NO_REPORT" => 'theorical',
                    "DEPARTED" => 'departed',
                ];

                $stopDateTime[] = [
                    "stop_id" => $stopId,
                    "stop_date_time" => Functions::getStopDateTime($call),
                    "disruption" => [
                        "departure_state" => isset($call->DepartureStatus) ? $state[$call->DepartureStatus] : 'theorical',
                        "arrival_state" => isset($call->ArrivalStatus) ? $state[$call->ArrivalStatus] : 'theorical',
                        "message" => '',
                    ],
                ];
                $order++;
            }
            $vehicleJourneyId = 'IDFM:' . $el->DatedVehicleJourneyRef->value;

            $data[$vehicleJourneyId] = [
                "informations" => [
                    "id" => $vehicleJourneyId,
                    "name" => isset($el->TrainNumbers->TrainNumberRef[0]->value) && (string)$el->TrainNumbers->TrainNumberRef[0]->value !== '0' 
                        ? $el->TrainNumbers->TrainNumberRef[0]->value 
                        : (isset($el->VehicleJourneyName[0]->value) ? $el->VehicleJourneyName[0]->value : ''),
                    "mode" => null,
                    "headsign" => isset($el->JourneyNote[0]->value) && (string)$el->JourneyNote[0]->value !== '0' 
                        ? $el->JourneyNote[0]->value 
                        : '',
                    "vehicle_size" => isset($el->VehicleFeatureRef) 
                        ? Functions::getVehicleSize($el->VehicleFeatureRef) 
                        : null,
                    "description" => '',
                    "message" => Functions::getMessage($call),
                    "line" => 'IDFM:' . Functions::idfmFormat($el->LineRef->value),
                ],
                "stop_date_time" => $stopDateTime,
            ];
        }

        $content = json_encode($data, JSON_PRETTY_PRINT);
        $content = str_replace('StopAreaSP', 'monomodalStopPlace:', $content);
        file_put_contents($dir . '/NAVIKA_idfm_departures.json', $content);

        $content = json_encode($json, JSON_PRETTY_PRINT);
        $content = str_replace('StopAreaSP', 'monomodalStopPlace:', $content);
        file_put_contents($dir . '/NAVIKA_idfm_departures2.json', $content);

        return Command::SUCCESS;
    }
}
