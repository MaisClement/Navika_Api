<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\RoutesRepository;
use App\Repository\StopRouteRepository;
use App\Repository\TownRepository;
use App\Repository\StopsRepository;
use Google\Transit\Realtime\FeedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class Schedules
{
    private $entityManager;
    private $params;

    private StopRouteRepository $stopRouteRepository;
    private StopsRepository $stopsRepository;
    private RoutesRepository $routesRepository;
    private TownRepository $townRepository;

    public function __construct(EntityManagerInterface $entityManager, StopRouteRepository $stopRouteRepository, StopsRepository $stopsRepository, TownRepository $townRepository, ParameterBagInterface $params, RoutesRepository $routesRepository)
    {        
        $this->entityManager = $entityManager;
        $this->params = $params;
        
        $this->stopRouteRepository = $stopRouteRepository;
        $this->routesRepository = $routesRepository;
        $this->stopsRepository = $stopsRepository;
        $this->townRepository = $townRepository;
    }
 
    /**
     * Get schedules
     * 
     * Get schedules at a stop
     * 
     * `id` can be get with `/stops`
     */
    #[Route('/schedules/{id}', name: 'get_schedules', methods: ['GET'])]
    #[OA\Tag(name: 'Schedules')]
    #[OA\Parameter(
        name:"id",
        in:"path",
        description:"stop_id",
        required: true,
        schema: new OA\Schema( type: "string" )
    )]
    #[OA\Parameter(
        name:"l",
        in:"query",
        description:"To get schedules for only one line",
        schema: new OA\Schema( type: "string" )
    )]
    #[OA\Parameter(
        name:"ungroupDepartures",
        in:"query",
        description:"if not null, disposition of departures will change for better time-sorted display",
        required: true,
        schema: new OA\Schema( type: "string" )
    )]

    #[OA\Response(
        response: 200,
        description: 'OK'
    )]
 
    public function getSchedules($id, Request $request)
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();

        $json = [];

        if ($request->get('l') != null) {
            $l = $request->get('l');
        }
        if ($request->get('ungroupDepartures') != null) {
            $ungroupDepartures = $request->get('ungroupDepartures');
        }

        if (str_contains($id, 'IDFM:')) {
            $provider = 'IDFM';
        } else {
            $provider = 'ADMIN';
        }

        // --- On regarde si l'arrêt existe bien et on recuppere toutes les lignes
        $routes = $this->stopRouteRepository->findBy( ['stop_id' => $id] );

        if ( count( $routes ) < 1 ) {
            return new JsonResponse(Functions::ErrorMessage(400, 'Nothing where found for this stop'), 400);
        }

        $town = $this->townRepository->findTownByCoordinates($routes[0]->getStopLat(), $routes[0]->getStopLon());

        $json['place'] = array(
            'id'        =>              $routes[0]->getStopId()->getStopId(),
            'name'      =>  (string)    $routes[0]->getStopName(),
            'type'      =>  (string)    $routes[0]->getLocationType() == 0 ? 'stop_point' : 'stop_area',
            'distance'  =>  (int)       0,
            'town'      =>  (string)    isset($town) ? $town->getTownName() : '',
            'zip_code'  =>  (string)    isset($town) ? $town->getZipCode() : '',
            'coord'     => array(
                'lat'       =>      (float) $routes[0]->getStopLat(),
                'lon'       =>      (float) $routes[0]->getStopLon(),
            ),
            'modes'     => array()
        );

        $providers = [];
        $lines = [];
        $terminus_schedules = [];
        $direction = [];
        $departures = [];
        $ungrouped_departures = [];

        foreach ($routes as $route) {
            $line_id = $route->getRouteId()->getRouteId();
            $providers[$route->getRouteId()->getProviderId()->getId()] = $route->getRouteId()->getProviderId();
        
            // Limit lines
            if ( (isset( $l ) && $l == $line_id) || !isset( $l ) ) {
                $lines[$line_id] = $route->getRouteId()->getRouteAndTrafic();

                if ( !in_array( $route->getTransportMode(), $json['place']['modes'] ) ) {
                    $json['place']['modes'][] = $route->getTransportMode();
                }
            }
        }

        # Get GTFS-RT Trip Update
        $trips_update = [];
        foreach( $providers as $p ) {
            $trips_update = array_merge($trips_update, Functions::getRealtimeData($p));
        }
        
        $file_name = $dir . '/test_gtfsrt.pb';
        file_put_contents($file_name, json_encode($trips_update, JSON_PRETTY_PRINT));
        
        if ($provider == 'IDFM') {        
            $qId = Functions::idfmFormat( $id );
            $prim_url = 'https://prim.iledefrance-mobilites.fr/marketplace/stop-monitoring?MonitoringRef=STIF:StopPoint:Q:' . $qId . ':';

            $client = HttpClient::create();
            $response = $client->request('GET', $prim_url, [
                'headers' => [
                    'apiKey' => $this->params->get('prim_api_key'),
                ],
            ]);
            $status = $response->getStatusCode();
            if ($status != 200){
                return new JsonResponse(Functions::ErrorMessage(520, 'Invalid fetched data'), 520);
            }
            
            $content = $response->getContent();
            $results = json_decode($content);
            $results = $results->Siri->ServiceDelivery->StopMonitoringDelivery[0]->MonitoredStopVisit;
            foreach ($results as $result) {
                if (!isset($result->MonitoredVehicleJourney->MonitoredCall)) {
                    return new JsonResponse(Functions::ErrorMessage(520, 'Invalid fetched data'), 520);
                }

                $call = $result->MonitoredVehicleJourney->MonitoredCall;
                $line_id = 'IDFM:' . Functions::idfmFormat( $result->MonitoredVehicleJourney->LineRef->value );

                // Limit lines
                if ( (isset( $l ) && $l == $line_id) || !isset( $l ) ) {
                    // Direction
                    $direction_id = 'IDFM:' . Functions::idfmFormat( $result->MonitoredVehicleJourney->DestinationRef->value );
                    if (!isset($direction[$direction_id])) {
                        $dir = Functions::getParentId($db, $direction_id);
                        $dir = $this->stopsRepository->findStopById( $dir );
                        
                        if ($dir != null && $dir->getStopName() != null) {
                            $direction[$direction_id] = Functions::gareFormat( $dir->getStopName() );
                        } elseif (isset( $call->DestinationDisplay[0]->value )) {
                            $direction[$direction_id] = Functions::gareFormat( $call->DestinationDisplay[0]->value );
                        }
                    }

                    // Get lines details
                    if (!isset($lines[$line_id])) {
                        $route = $this->routesRepository->findOneBy( ['route_id' => $line_id] );
                        if ( $route != null ) {
                            $lines[$line_id] = $route->getRoute();

                            //modes
                            if ( !in_array( $route->getTransportMode(), $json['place']['modes'] ) ) {
                                $json['place']['modes'][] = $route->getTransportMode();
                            }
                        }
                    }

                    if (($lines[$line_id]['mode'] == "rail" || $lines[$line_id]['mode'] == "nationalrail") && Functions::callIsFuture($call)) {
                        // Si c'est du ferré, l'affichage est different

                        if (!(isset($call->ExpectedArrivalTime) && isset($call->ExpectedDepartureTime) && ($call->ExpectedArrivalTime == $call->ExpectedDepartureTime && $result->MonitoredVehicleJourney->OperatorRef->value == "SNCF_ACCES_CLOUD:Operator::SNCF:")) ){
                            // On vérifie que l'heure d'arrivé et de départ ne soit pas strictement la meme

                            $dep = array(
                                "informations" => array(
                                    "direction" => array(
                                        "id"         =>  (string)   $direction_id,
                                        "name"       =>  (string)   $direction[$direction_id],
                                    ),
                                    // "id"            =>  (string)  isset($result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef) ? 'IDFM:' . $result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef : '',
                                    "id"            =>  (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '' && (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '0' ? $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value : ( $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value ? $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value : ''),
                                    "name"          =>  (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '' && (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '0' ? $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value : ( $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value ? $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value : ''),
                                    "mode"          =>  (string)  $lines[$line_id]['mode'],
                                    "trip_name"     =>  (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '' && (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '0' ? $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value : ( $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value ? $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value : ''),
                                    "headsign"      =>  (string)  isset($result->MonitoredVehicleJourney->JourneyNote[0]->value) !== '' && (string)  isset($result->MonitoredVehicleJourney->JourneyNote[0]->value) !== '0' ? $result->MonitoredVehicleJourney->JourneyNote[0]->value : '',
                                    "description"   =>  (string)  '',
                                    "message"       =>  (string)  Functions::getMessage($call),
                                ),
                                "stop_date_time" => Functions::getStopDateTime($call)
                            );
                           
                            if (isset($ungroupDepartures) && $ungroupDepartures == 'true') {
                                $dep['informations']['line'] = $lines[$line_id];
                            }
            
                            $departures[ $line_id ][] = $dep;
                            $ungrouped_departures[] = $dep;
                        }
                    } else if (isset($call->ExpectedDepartureTime) && Functions::callIsFuture($call)) {
                        // Affichage normal
                        if (!isset($terminus_schedules[$line_id][$direction_id])) {
                            $terminus_schedules[$line_id][$direction_id] = array(
                                "id"         =>  (string)    $direction_id,
                                "name"       =>  (string)    $direction[$direction_id],
                                "schedules"  =>  array()
                            );
                        }
                        $s = Functions::getStopDateTime($call);
                        $s['id'] = isset($result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef) ? 'IDFM:' . $result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef : '';
                        $terminus_schedules[$line_id][$direction_id]['schedules'][] = $s;
                    }
                }
            }
        }
                
        foreach ($lines as $line) {
            if ($line['mode'] == 'rail' || $line['mode'] == 'nationalrail') {
                if ( !isset($departures[ $line['id'] ]) ) {
                    $objs = Functions::getSchedulesByStop($db, $id, $line['id'], date("Y-m-d"), true);
                    $objs_1 = Functions::getSchedulesByStop($db, $id, $line['id'], date("Y-m-d", strtotime('+1 day')));
                    $objs = array_merge($objs, $objs_1);
                    foreach ($objs as $obj) {
    
                        $direction = Functions::getLastStopOfTrip($db, $obj['trip_id'])[0];
                        $direction_id = $direction['stop_id'];

                        /*
                        ['state']
                        ['departure_date_time']
                        ['arrival_date_time']
                        */
                        $real_time = array(
                            'departure_date_time' => $obj['departure_time'],
                            'arrival_date_time' => $obj['arrival_time']
                        );
                        if (Functions::shouldAddRealTime($obj['departure_time'], $obj['arrival_time'])){
                            $trip_update = Functions::getTripRealtime($trips_update, $obj['trip_id'], $obj['stop_id']);
                            $real_time = Functions::getTripRealtimeDateTime($trip_update, $obj['stop_id']);
                        }
                        if (Functions::isFuture($real_time['departure_date_time'], $real_time['arrival_date_time'], $obj['departure_time'], $obj['arrival_time'])) {
                            $dep = array(
                                "informations" => array(
                                    "direction" => array(
                                        "id"        =>  (string)  $direction['stop_id'],
                                        "name"      =>  (string)  $direction['stop_name'],
                                    ),
                                    "id"            =>  (string)  $obj['trip_id'],
                                    "name"          =>  (string)  $obj['trip_short_name'],
                                    "mode"          =>  (string)  $line['mode'],
                                    "trip_name"     =>  (string)  $obj['trip_short_name'],
                                    "headsign"      =>  (string)  $obj['trip_headsign'],
                                    "description"   =>  (string)  '',
                                    //"message"       =>  (string)  Functions::getMessage($call),
                                ),
                                "stop_date_time" => array(
                                    "base_departure_date_time"  =>  (string)  Functions::prepareTime($obj['departure_time'], true),
                                    "departure_date_time"       =>  (string)  $real_time['departure_date_time'] != null ? Functions::prepareTime($real_time['departure_date_time'], true) : Functions::prepareTime($obj['departure_time'], true),
                                    "base_arrival_date_time"    =>  (string)  Functions::prepareTime($obj['arrival_time'], true),
                                    "arrival_date_time"         =>  (string)  $real_time['arrival_date_time'] != null ? Functions::prepareTime($real_time['arrival_date_time'], true) : Functions::prepareTime($obj['arrival_time'], true),
                                    "state"                     =>  (string)  isset($trip_update) && $trip_update['state'] != null ? $trip_update['state'] : 'theorical',
                                    "atStop"                    =>  (string)  'false',
                                    "platform"                  =>  (string)  '-'
                                )
                            );
            
                            if (isset($ungroupDepartures) && $ungroupDepartures == 'true') {
                                $dep['informations']['line'] = $line;
                            }
            
                            $departures[ $line['id'] ][] = $dep;
                            $ungrouped_departures[] = $dep;
                        }
                    }
                }
               
            } else {
                if ( !isset( $terminus_schedules[$line['id']] ) ) {
                    $objs = Functions::getSchedulesByStop($db, $id, $line['id'], date("Y-m-d"));
                    $objs_1 = Functions::getSchedulesByStop($db, $id, $line['id'], date("Y-m-d", strtotime(' +1 day')));
                    $objs = array_merge($objs, $objs_1);

                    $terminus_schedules[$line['id']] = [];
                    foreach ($objs as $obj) {
                        $direction = Functions::getLastStopOfTrip($db, $obj['trip_id'])[0];
                        // Get Realtime data
                        $direction_id = $direction['stop_id'];

                        if (!isset($terminus_schedules[$line['id']][$direction_id])) {
                            $terminus_schedules[$line['id']][$direction_id] = [
                                "id"        =>  $direction['stop_id'],
                                "name"      =>  $direction['stop_name'],
                                "schedules" =>  [],
                            ];
                        }

                        $trip_update = Functions::getTripRealtime($trips_update, $obj['trip_id'], $obj['stop_id']);
                        $real_time = Functions::getTripRealtimeDateTime($trip_update, $obj['stop_id']);
                        
                        if (Functions::isFuture($real_time['departure_date_time'], $real_time['arrival_date_time'], $obj['departure_time'], $obj['arrival_time'])) {
                            $terminus_schedules[$line['id']][$direction_id]['schedules'][] = [
                                "id"                        =>  $obj['trip_id'],
                                "base_departure_date_time"  =>  Functions::prepareTime($obj['departure_time'], true),
                                "departure_date_time"       =>  $real_time['departure_date_time'] != null ? Functions::prepareTime($real_time['departure_date_time'], true) : Functions::prepareTime($obj['departure_time'], true),
                                "base_arrival_date_time"    =>  Functions::prepareTime($obj['arrival_time'], true),
                                "arrival_date_time"         =>  $real_time['arrival_date_time'] != null ? Functions::prepareTime($real_time['arrival_date_time'], true) : Functions::prepareTime($obj['arrival_time'], true),
                                "state"                     =>  isset($trip_update) && $trip_update['state'] != null ? $trip_update['state'] : 'theorical',
                                "atStop"                    =>  "false",
                                "platform"                  =>  "-",
                            ];
                        }
                    }
                }
            }
        }

        $lines = Functions::order_line($lines);

// Schedule departure
        foreach ($lines as $line) {
            if ($line['mode'] != 'rail' && $line['mode'] != 'nationalrail') {
                $terminus = [];

                if ( isset( $terminus_schedules[$line['id']] ) ) {
                    foreach( $terminus_schedules[$line['id']] as $key => $value) {
                        $terminus[] = $value;
                    }
                }
                $line['terminus_schedules'] = $terminus;
                $json['schedules'][] = $line;
            }
        }

// Display departure
        if (isset($departures) || isset($ungrouped_departures)) {
            // Train non regroupé
            if (isset($ungroupDepartures) && $ungroupDepartures == 'true') {
                $ungrouped_departures = Functions::orderDeparture( $ungrouped_departures );
                $json['departures'] = $ungrouped_departures;

            } else { 
                // Train groupé
                foreach ($lines as $line) {
                    if ($line['mode'] == 'rail' || $line['mode'] == 'nationalrail') {
                        if (isset($departures[$line['id']])) {
                            foreach ($departures[$line['id']] as $departure) {
                                $line['departures'][] = $departure;
                            }
                        } else {
                            $line['departures'] = [];
                        }
                        $line['departures'] = Functions::orderDeparture( $line['departures'] );
                        $json['departures'][] = $line;
                    }
                }
            }
        }

        return new JsonResponse($json);
    }
}