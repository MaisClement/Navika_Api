<?php

namespace App\Command\SPECIFIC;

use App\Controller\Notify;
use App\Controller\Functions;
use App\Entity\Trafic;
use App\Repository\StopsRepository;
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

    private StopsRepository $stopsRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, StopsRepository $stopsRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->stopsRepository = $stopsRepository;

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

            $order = 0;
            $len = count($el->EstimatedCalls->EstimatedCall);
            $stop_times = [];
            
            foreach($el->EstimatedCalls->EstimatedCall as $stops) {
                $call = $stops;
                
                $_stop_id = 'IDFM:' . Functions::idfmFormat($stops->StopPointRef->value);
                if ( count($stops->DestinationDisplay) > 0 ) {
                    $_stop_name = Functions::gareFormat($stops->DestinationDisplay[0]->value);
                } else {
                    $_stop_name = '';
                }
                $_stops = null;

                $_stops =  $this->stopsRepository->findStopById($_stop_id);
                if ($_stops == null) {
                   echo $_stop_id;
                }

                $state = array(
                    "ON_TIME" => 'unchanged',
                    "EARLY" => 'unchanged',
                    "ARRIVED" => 'unchanged',
                    "CANCELLED" => 'deleted',
                    "MISSED" => 'deleted',
                    "DELAYED" => 'delayed',
                    "NO_REPORT" => 'theorical',
                );

                $stop_times[] = array(
                    "name"          => $_stops != null ? $_stops->getStopName() : "uh?",
                    "id"            => $_stops != null ? $_stops->getStopId()   : $_stop_id,
                    "order"         => (int) $order,
                    "type"          => (int) $len - 1 === $order ? 'terminus' : ($order == 0 ? 'origin' : ''),
                    "coords" => array(
                        "lat"       => $_stops != null ? $_stops->getStopLat() : '',
                        "lon"       => $_stops != null ? $_stops->getStopLon() : '',
                    ),
                    "stop_time" => array(
                        "departure_date_time"       =>  (string)  isset($call->ExpectedDepartureTime)   ? $call->ExpectedDepartureTime  :  ( isset($call->AimedDepartureTime)    ? $call->AimedDepartureTime        : '' ),
                        "arrival_date_time"         =>  (string)  isset($call->ExpectedArrivalTime)     ? $call->ExpectedArrivalTime    :  ( isset($call->AimedArrivalTime)      ? $call->AimedArrivalTime          : '' ),
                    ),
                    "disruption"    => array(
                        "departure_state"           =>  (string)  isset($call->DepartureStatus) ? $state[$call->DepartureStatus] : 'theorical',
                        "arrival_state"             =>  (string)  isset($call->ArrivalStatus) ? $state[$call->ArrivalStatus] : 'theorical',
                        "message"                   =>  (string)  '',
                        "base_departure_date_time"  =>  (string)  isset($call->AimedDepartureTime)      ? $call->AimedDepartureTime     :  ( isset($call->ExpectedDepartureTime) ? $call->ExpectedDepartureTime     : '' ),
                        "departure_date_time"       =>  (string)  isset($call->ExpectedDepartureTime)   ? $call->ExpectedDepartureTime  :  ( isset($call->AimedDepartureTime)    ? $call->AimedDepartureTime        : '' ),
                        "base_arrival_date_time"    =>  (string)  isset($call->AimedArrivalTime)        ? $call->AimedArrivalTime       :  ( isset($call->ExpectedArrivalTime)   ? $call->ExpectedArrivalTime       : '' ),
                        "arrival_date_time"         =>  (string)  isset($call->ExpectedArrivalTime)     ? $call->ExpectedArrivalTime    :  ( isset($call->AimedArrivalTime)      ? $call->AimedArrivalTime          : '' ),
                    ),
                );
                $route_type = $_stops != null ? $_stops->getVehicleType() : '';
                $order++;
            }
            $_vehicle_journey_id = 'IDFM:' . $el->DatedVehicleJourneyRef->value;

            $data[$_vehicle_journey_id] = array(
                "informations"  => array(
                    "id"            => $_vehicle_journey_id,
                    "mode"          => $route_type,
                    "name"          => '',// $el->JourneyNote[0]->JourneyNote,
                    "headsign"      => '',// $el->JourneyNote[0]->JourneyNote,
                    "description"   => '',
                    "message"       => '',
                    "origin"        => array(
                        "id"        => $stop_times[0]['id'],
                        "name"      => $stop_times[0]['name'],
                    ),
                    "direction"     => array(
                        "id"        => $stop_times[count($stop_times) - 1]['id'],
                        "name"      => $stop_times[count($stop_times) - 1]['name'],
                    ),
                    "line"         => 'IDFM:' . Functions::idfmFormat($el->LineRef->value),
                ),
                "stop_times" => $stop_times,
            );
        }

        $content = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($dir . '/NAVIKA_idfm_departures.json', $content);

        $content = json_encode($json, JSON_PRETTY_PRINT);
        file_put_contents($dir . '/NAVIKA_idfm_departures2.json', $content);

        return Command::SUCCESS;
    }
}