<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\RoutesRepository;
use App\Repository\StopRouteRepository;
use App\Repository\StopsRepository;
use App\Repository\StopTimesRepository;
use App\Repository\TripsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class Schedules
{
    private \Doctrine\ORM\EntityManagerInterface $entityManager;
    private StopRouteRepository $stopRouteRepository;
    private TripsRepository $tripsRepository;
    private StopTimesRepository $stopTimesRepository;
    private StopsRepository $stopsRepository;
    private RoutesRepository $routesRepository;
    private ParameterBagInterface $params;
    
    public function __construct(EntityManagerInterface $entityManager, StopRouteRepository $stopRouteRepository, TripsRepository $tripsRepository, StopTimesRepository $stopTimesRepository, StopsRepository $stopsRepository, ParameterBagInterface $params, RoutesRepository $routesRepository)
    {        
        $this->entityManager = $entityManager;
        $this->params = $params;
        
        $this->stopRouteRepository = $stopRouteRepository;
        $this->routesRepository = $routesRepository;
        $this->tripsRepository = $tripsRepository;
        $this->stopTimesRepository = $stopTimesRepository;
        $this->stopsRepository = $stopsRepository;
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
        description: ''
    )]
 
    public function getSchedules($id, Request $request)
    {
        $db = $this->entityManager->getConnection();        

        $json = [];

        if ($request->get('l') != null) {
            $l = $request->get('l');
        }
        if ($request->get('ungroupDepartures') != null) {
            $ungroupDepartures = $request->get('ungroupDepartures');
        }

        if (str_contains($id, 'SNCF:')) {
            $provider = 'SNCF';
        } elseif (str_contains($id, 'IDFM:')) {
            $provider = 'IDFM';
        } else {
            $provider = 'ADMIN';
        }

        //--- On regarde si l'arrêt existe bien et on recuppere toutes les lignes
        $routes = $this->stopRouteRepository->findBy( ['stop_id' => $id] );

        if ( count( $routes ) < 1 ) {
            return new JsonResponse(Functions::ErrorMessage(400, 'Nothing where found for this station'), 400);
        }

        $json['place'] = array(
            'id'        =>              $routes[0]->getStopId()->getStopId(),
            'name'      =>  (string)    $routes[0]->getStopName(),
            'type'      =>  (string)    'stop_area',
            'distance'  =>  (int)       0,
            'town'      =>  (string)    $routes[0]->getTownName(),
            'zip_code'  =>  (string)    '',
            'coord'     => array(
                'lat'       =>      (float) $routes[0]->getStopLat(),
                'lon'       =>      (float) $routes[0]->getStopLon(),
            ),
            'modes'     => array()
        );

        $departures_lines = [];
        $lines_data = [];

        foreach ($routes as $route) {
            $line_id = $route->getRouteId()->getRouteId();
        
            if ( (isset( $l ) && $l == $line_id) || !isset( $l ) ) {
                $lines_data[$line_id] = $route->getRouteId()->getRouteAndTrafic();

                //modes
                if ( !in_array( $route->getTransportMode(), $json['place']['modes'] ) ) {
                    $json['place']['modes'][] = $route->getTransportMode();
                }
        
                // Si c'est du ferré, l'affichage est different
                if ($route->getTransportMode() == "rail" || $route->getTransportMode() == "nationalrail") {
                    $lines_data[$line_id]['departures'] = [];

                    if (!in_array($line_id, $departures_lines)) {
                        $departures_lines[] = $line_id;
                    }

                // Affichage normal
                } else {
                    $lines_data[$line_id]['terminus_schedules'] = [];
                }
            }
        }        


        // ------------
        $qId = Functions::idfmFormat( $id );
        $prim_url = 'https://prim.iledefrance-mobilites.fr/marketplace/stop-monitoring?MonitoringRef=STIF:StopPoint:Q:' . $qId . ':';
        $sncf_url = 'https://garesetconnexions-online.azure-api.net/API/PIV/Departures/00' . $qId;
        $sncf_url_api = 'https://api.sncf.com/v1/coverage/sncf/stop_areas/stop_area:SNCF:' . $qId . '/departures?count=30&data_freshness=realtime';

        
        //------------
        //On utilise l'api (differe selon le provider)

        if ($provider == 'SNCF') {
            //SNCF GC
            $client = HttpClient::create();
            $response = $client->request('GET', $sncf_url, [
                'headers' => [
                    'ocp-apim-subscription-key' => $this->params->get('sncf_gc_key'),
                ],
            ]);
            $status = $response->getStatusCode();
            if ($status != 200){
                return new JsonResponse(Functions::ErrorMessage(520, 'Invalid fetched data'), 520);
            }
            $content = $response->getContent();
            $results = json_decode($content);
            //SNCF API
            $api_client = HttpClient::create();
            $api_response = $api_client->request('GET', $sncf_url_api, [
                'auth_basic' => [$this->params->get('sncf_api_key'), ''],
            ]);
            $api_status = $api_response->getStatusCode();
            if ($api_status != 200){
                return new JsonResponse(Functions::ErrorMessage(520, 'Invalid fetched data'), 520);
            }
            $api_content = $api_response->getContent();
            $api_results = json_decode($api_content);
            $departures = [];
            $ungrouped_departures = [];
            foreach ($results as $result) {

                $details = Functions::getApiDetails($api_results, $result->trainNumber);

                $dep = array(
                    "informations" => array(
                        "direction" => array(
                            "id"         =>  (string)    $details['to_id'],
                            "name"       =>  (string)    $result->traffic->oldDestination != "" ? $result->traffic->oldDestination : $result->traffic->destination,
                        ),
                        "origin" => array(
                            "id"         =>  (string)    "",
                            "name"       =>  (string)    $result->traffic->origin,
                        ),
                        "id"            =>  (string)    $details['id'],
                        "name"          =>  (string)    $result->trainType . ' ' . $result->trainNumber,
                        "mode"          =>  (string)    "nationalrail",
                        "trip_name"     =>  (string)    $result->trainNumber,
                        "headsign"      =>  (string)    str_ireplace('train ', '', $result->trainType),
                        "description"   =>  (string)    "",
                        "message"       =>  (string)    "",
                    ),
                    "disruptions"       => [],
                    "stop_date_time" => array(
                        // Si l'horaire est present          On affiche l'horaire est             Sinon, si l'autre est present            On affiche l'autre            Ou rien  
                        "base_departure_date_time"  =>  (string)  $result->scheduledTime,
                        "departure_date_time"       =>  (string)  $result->actualTime,
                        "base_arrival_date_time"    =>  (string)  "",
                        "arrival_date_time"         =>  (string)  "",
                        "state"                     =>  (string)  Functions::getSNCFState($result->informationStatus->trainStatus, $result->traffic->eventLevel, $result->traffic),
                        "atStop"                    =>  (string)  $result->platform->isTrackactive,
                        "platform"                  =>  (string)  $result->platform->track != null ? $result->platform->track : ($result->trainMode == "CAR" ? "GR" : "-"),
                    )
                );

                // Si groupé
                $departures[] = $dep;

                // Si dégroupé
                $dep['informations']['line'] = array(
                    "id"         =>  (string)    "SNCF",
                    "code"       =>  (string)    "SNCF",
                    "name"       =>  (string)    "Trains SNCF",
                    "mode"       =>  (string)    "nationalrail",
                    "color"      =>  (string)    "aaaaaa",
                    "text_color" =>  (string)    "000000",
                );
                $ungrouped_departures[] = $dep;
            }
            // Train non regroupé
            if (isset($_GET['ungroupDepartures']) && $_GET['ungroupDepartures'] == 'true') {
                $json['departures'] = $ungrouped_departures;

            } else { // Train regroupé

                $json['departures'][] = array(
                    "id"         =>  (string)    "SNCF",
                    "code"       =>  (string)    "SNCF",
                    "name"       =>  (string)    "Trains SNCF",
                    "mode"       =>  (string)    "nationalrail",
                    "color"      =>  (string)    "aaaaaa",
                    "text_color" =>  (string)    "000000",
                    "departures" =>  $departures
                );
            }
            //modes
            $json['place']['modes'] = ['nationalrail'];
        } elseif ($provider == 'IDFM') {
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
            $schedules = [];
            $departures = [];
            $ungrouped_departures = [];
            $direction = [];
            foreach ($results as $result) {
                if (!isset($result->MonitoredVehicleJourney->MonitoredCall)) {
                    return new JsonResponse(Functions::ErrorMessage(520, 'Invalid fetched data'), 520);
                }

                $call = $result->MonitoredVehicleJourney->MonitoredCall;
                $line_id = 'IDFM:' . Functions::idfmFormat( $result->MonitoredVehicleJourney->LineRef->value );

                if ( (isset( $l ) && $l == $line_id) || !isset( $l ) ) {
                    // Direction
                    $destination_ref = 'IDFM:' . Functions::idfmFormat( $result->MonitoredVehicleJourney->DestinationRef->value );
                    if (!isset($direction[$destination_ref])) {

                        $dir = $this->stopsRepository->findStopById( $destination_ref );
                        
                        if ($dir != null && $dir->getStopName() != null) {
                            $direction[$destination_ref] = Functions::gareFormat( $dir->getStopName() );
                        } elseif (isset( $call->DestinationDisplay[0]->value )) {
                            $direction[$destination_ref] = Functions::gareFormat( $call->DestinationDisplay[0]->value );
                        }
                    }

                    // Get lines details
                    if (!isset($lines_data[$line_id])) {
                        $route = $this->routesRepository->findOneBy( ['route_id' => $line_id] );
                        if ( $route != null ) {
                            $lines_data[$line_id] = $route->getRoute();

                            //modes
                            if ( !in_array( $route->getTransportMode(), $json['place']['modes'] ) ) {
                                $json['place']['modes'][] = $route->getTransportMode();
                            }
                        }
                    }

                    if (($lines_data[$line_id]['mode'] == "rail" || $lines_data[$line_id]['mode'] == "nationalrail") && Functions::callIsFuture($call)) {
                        // Si c'est du ferré, l'affichage est different
                        if (!in_array($line_id, $departures_lines)) {
                            $departures_lines[] = $line_id;
                        }
                        $dep = array(
                            "informations" => array(
                                "direction" => array(
                                    "id"         =>  (string)   $destination_ref,
                                    "name"       =>  (string)   $direction[$destination_ref],
                                ),
                                "id"            =>  (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '' && (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '0' ? $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value : ( $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value ? $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value : ''),
                                "name"          =>  (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '' && (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '0' ? $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value : ( $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value ? $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value : ''),
                                "mode"          =>  (string)  $lines_data[$line_id]['mode'],
                                "trip_name"     =>  (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '' && (string)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '0' ? $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value : ( $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value ? $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value : ''),
                                "headsign"      =>  (string)  isset($result->MonitoredVehicleJourney->JourneyNote[0]->value) !== '' && (string)  isset($result->MonitoredVehicleJourney->JourneyNote[0]->value) !== '0' ? $result->MonitoredVehicleJourney->JourneyNote[0]->value : '',
                                "description"   =>  (string)  '',
                                "message"       =>  (string)  Functions::getMessage($call),
                            ),
                            "stop_date_time" => Functions::getStopDateTime($call)
                        );
                        $departures[$line_id][] = $dep;
                        $dep['informations']['line'] = $lines_data[$line_id];
                        $ungrouped_departures[] = $dep;
                    } elseif (Functions::callIsFuture($call)) {
                        // Affichage normal
                        if (!isset($terminus_data[$line_id][$destination_ref])) {
                            $terminus_data[$line_id][$destination_ref] = array(
                                "id"         =>  (string)    $destination_ref,
                                "name"       =>  (string)    $direction[$destination_ref],
                                "schedules"  =>  array()
                            );
                        }
                        if (isset($call->ExpectedDepartureTime)) {
                            $schedules[$line_id][$destination_ref][] = Functions::getStopDateTime($call);
                        }
                    }
                }
            }
            $l = [];
            foreach ($departures_lines as $departures_line) {
                $l[] = $lines_data[$departures_line];
            }
            // Train non regroupé
            if (isset($ungroupDepartures) && $ungroupDepartures == 'true') {
                $ungrouped_departures = Functions::orderDeparture( $ungrouped_departures );
                $json['departures'] = $ungrouped_departures;

            } else { 
                // Train groupé
                $l = Functions::order_line($l);                
                foreach ($l as $line) {
                    if (isset($departures[$line['id']])) {
                        foreach ($departures[$line['id']] as $departure) {
                            $line['departures'][] = $departure;
                        }
                    }
                    $json['departures'][] = $line;
                }
            }
            $lines_data = Functions::order_line($lines_data);
            foreach ($lines_data as $line) {
                if ($line['mode'] != 'rail' && $line['mode'] != 'nationalrail') {

                    if (isset($terminus_data[$line['id']])) {
                        foreach ($terminus_data[$line['id']] as $term) {

                            if (isset($schedules[$line['id']][$term['id']])) {
                                foreach ($schedules[$line['id']][$term['id']] as $schedule) {
                                    $term['schedules'][] = $schedule;
                                }
                                $line['terminus_schedules'][] = $term;
                            }
                        }
                    }
                    $json['schedules'][] = $line;
                }
            }
        } else {
            foreach ($lines_data as $line) {
                if ($line['mode'] != 'rail' && $line['mode'] != 'nationalrail') {
                    $line['terminus_schedules'] = [];
                    $json['schedules'][] = $line;
                }
            }
        }

//FROM BD
        if ( isset( $json['schedules'] ) ) {
            $counter = count($json['schedules']);
            for ($i = 0; $i < $counter; $i++) { 
                $line = $json['schedules'][$i];
                $terminus = [];
                if (count($line['terminus_schedules']) == 0){
                    
                    $objs = Functions::getSchedulesByStop($db, $id, $line['id'], date("Y-m-d"), date("G:i:s"));

                    foreach($objs as $obj) {

                        if ( !isset( $terminus[$obj['trip_headsign']] ) || !isset( $json['schedules'][$i]['terminus_schedules'][$terminus[$obj['trip_headsign']]] ) ) {
                            $json['schedules'][$i]['terminus_schedules'][count($terminus)] = array(
                                "id"         =>  (string)    '',
                                "name"       =>  (string)    Functions::gareFormat($obj['trip_headsign']),
                                "schedules"  =>  array()
                            );
                            $terminus[$obj['trip_headsign']] = count($terminus);
                        }              

                        $json['schedules'][$i]['terminus_schedules'][$terminus[$obj['trip_headsign']]]['schedules'][] = array(
                            "base_departure_date_time"  =>  (string)  Functions::prepareTime($obj['departure_time'], true),
                            "departure_date_time"       =>  (string)  Functions::prepareTime($obj['departure_time'], true),
                            "base_arrival_date_time"    =>  (string)  Functions::prepareTime($obj['arrival_time'], true),
                            "arrival_date_time"         =>  (string)  Functions::prepareTime($obj['arrival_time'], true),
                            "state"                     =>  (string)  $_SERVER['APP_ENV'] == "dev" ? "theorical_api" : "theorical",
                            "atStop"                    =>  (string)  "false",
                            "platform"                  =>  (string)  "-"
                        );
                    }
                }
            }
        }

        return new JsonResponse($json);
    }
}
