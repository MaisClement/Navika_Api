<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\RoutesRepository;
use App\Repository\StopRouteRepository;
use App\Repository\TownRepository;
use App\Repository\ShapesRepository;
use App\Repository\StopsRepository;
use Google\Transit\Realtime\FeedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use App\Service\Logger;

class Schedules
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;

    private Logger $logger;

    private StopRouteRepository $stopRouteRepository;
    private StopsRepository $stopsRepository;
    private RoutesRepository $routesRepository;
    private TownRepository $townRepository;
    private ShapesRepository $shapesRepository;

    public function __construct(EntityManagerInterface $entityManager, StopRouteRepository $stopRouteRepository, Logger $logger, StopsRepository $stopsRepository, TownRepository $townRepository, ParameterBagInterface $params, RoutesRepository $routesRepository, ShapesRepository $shapesRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->logger = $logger;

        $this->stopRouteRepository = $stopRouteRepository;
        $this->routesRepository = $routesRepository;
        $this->stopsRepository = $stopsRepository;
        $this->townRepository = $townRepository;
        $this->shapesRepository = $shapesRepository;
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
        name: "id",
        in: "path",
        description: "stop_id",
        required: true,
        schema: new OA\Schema(type: "string")
    )]
    #[OA\Parameter(
        name: "l",
        in: "query",
        description: "To get schedules for only one line",
        schema: new OA\Schema(type: "string")
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

        if (str_contains($id, 'IDFM:')) {
            $provider = 'IDFM';
        } else {
            $provider = 'ADMIN';
        }

        // --- On regarde si l'arrêt existe bien et on recuppere toutes les lignes
        $routes = $this->stopRouteRepository->findBy(['stop_id' => $id]);
        // dd($routes);

        if (count($routes) < 1) {
            $this->logger->logHttpErrorMessage($request, "Nothing where found for this stop", 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this stop'), 400);
        }

        $town = $this->townRepository->findTownByCoordinates($routes[0]->getStopLat(), $routes[0]->getStopLon());

        $json['place'] = array(
            'id' => $routes[0]->getStopId()->getStopId(),
            'name' => (string) $routes[0]->getStopName(),
            'type' => (string) $routes[0]->getLocationType() == 0 ? 'stop_point' : 'stop_area',
            'distance' => (int) 0,
            'town' => (string) isset($town) ? $town->getTownName() : '',
            'zip_code' => (string) isset($town) ? $town->getZipCode() : '',
            'coord' => array(
                'lat' => (float) $routes[0]->getStopLat(),
                'lon' => (float) $routes[0]->getStopLon(),
            ),
            'modes' => array()
        );

        $providers = [];
        $lines = [];
        $schedules_by_direction = [];
        $schedules_by_direction = [];
        $direction = [];
        $departures = [];
        $ungrouped_departures = [];

        foreach ($routes as $route) {
            $line_id = $route->getRouteId()->getRouteId();
            $providers[$route->getRouteId()->getProviderId()->getId()] = $route->getRouteId()->getProviderId();

            // Limit lines
            if ((isset($l) && $l == $line_id) || !isset($l)) {
                $lines[$line_id] = $route->getRouteId()->getRouteAndTrafic();

                if (!in_array($route->getTransportMode(), $json['place']['modes'])) {
                    $json['place']['modes'][] = $route->getTransportMode();
                }
            }
        }

        # Get GTFS-RT Trip Update
        $trips_update = [];
        foreach ($providers as $p) {
            $this->logger->log(['message' => "Getting GTFS-RT Trip Update for: " . $p->getId()], 'INFO');
            $realtime = Functions::getRealtimeData($p);
            $trips_update = array_merge($trips_update, $realtime['trip_updates']);
        }

        $existing_departure = [];

        if ($provider == 'IDFM') {
            $qId = Functions::idfmFormat($id);
            $prim_url = 'https://prim.iledefrance-mobilites.fr/marketplace/stop-monitoring?MonitoringRef=STIF:StopPoint:Q:' . $qId . ':';

            $this->logger->log(['message' => "PRIM Schedule query: $prim_url"], 'INFO');
            $client = HttpClient::create();
            $response = $client->request('GET', $prim_url, [
                'headers' => [
                    'apiKey' => $this->params->get('prim_api_key'),
                ],
            ]);
            $status = $response->getStatusCode();
            if ($status != 200) {
                $this->logger->logHttpErrorMessage($request, "No data found for station with id: $id", 'WARN');
                return new JsonResponse(Functions::httpErrorMessage(520, 'Invalid fetched data'), 520);
            }

            $content = $response->getContent();
            $results = json_decode($content);
            $results = $results->Siri->ServiceDelivery->StopMonitoringDelivery[0]->MonitoredStopVisit;

            foreach ($results as $result) {
                if (!isset($result->MonitoredVehicleJourney->MonitoredCall)) {
                    $this->logger->log(["message" => "PRIM Schedule query: Unable to fetch data. HTTP error code $status"], 'ERROR');
                    return new JsonResponse(Functions::httpErrorMessage(520, 'Invalid fetched data'), 520);
                }

                $call = $result->MonitoredVehicleJourney->MonitoredCall;
                $line_id = 'IDFM:' . Functions::idfmFormat($result->MonitoredVehicleJourney->LineRef->value);

                // Limit lines
                if ((isset($l) && $l == $line_id) || !isset($l)) {
                    // Direction
                    $direction_id = 'IDFM:' . Functions::idfmFormat($result->MonitoredVehicleJourney->DestinationRef->value);
                    if (!isset($direction[$direction_id])) {
                        $dir = Functions::getParentId($db, $direction_id);
                        $dir = $this->stopsRepository->findStopById($dir);

                        if ($dir != null && $dir->getStopName() != null) {
                            $direction[$direction_id] = Functions::gareFormat($dir->getStopName());
                        } elseif (isset($call->DestinationDisplay[0]->value)) {
                            $direction[$direction_id] = Functions::gareFormat($call->DestinationDisplay[0]->value);
                        }
                    }

                    // Get lines details
                    if (!isset($lines[$line_id])) {
                        $route = $this->routesRepository->findOneBy(['route_id' => $line_id]);
                        if ($route != null) {
                            $lines[$line_id] = $route->getRoute();

                            //modes
                            if (!in_array($route->getTransportMode(), $json['place']['modes'])) {
                                $json['place']['modes'][] = $route->getTransportMode();
                            }
                        }
                    }
                    if (isset( $lines[$line_id])) {
                        $line = $lines[$line_id];

                    if (Functions::callIsFuture($call)) {
                        if (($line['mode'] == 'rail' || $line['mode'] == 'nationalrail')) {
                            if (!( isset($call->ExpectedArrivalTime)
                                && isset($call->ExpectedDepartureTime)
                                && ($call->ExpectedArrivalTime == $call->ExpectedDepartureTime
                                && $result->MonitoredVehicleJourney->OperatorRef->value == "SNCF_ACCES_CLOUD:Operator::SNCF:"))
                            ) {
                                // On vérifie que l'heure d'arrivé et de départ ne soit pas strictement la meme

                                $trip_id = isset($result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef) ? $result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef : '';
                                $trip_name = '';
                                if (isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0])){
                                    $trip_name = isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '' && (string) isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) !== '0' ? $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value : ($result->MonitoredVehicleJourney->VehicleJourneyName[0]->value ? $result->MonitoredVehicleJourney->VehicleJourneyName[0]->value : '');
                                }
                                $dep = array(
                                    "informations" => array(
                                        "direction" => array(
                                            "id"            => (string) $direction_id,
                                            "name"          => (string) $direction[$direction_id],
                                            "direction_id"  => isset($result->MonitoredVehicleJourney->DirectionRef) ? ((string) $result->MonitoredVehicleJourney->DirectionRef->value == "Aller" ? 0 : 1) : null,
                                        ),
                                        "id"                => (string) strlen($trip_id) > 0 ? 'IDFM:' . $trip_id . '-NAVI:' . $trip_name : '',
                                        "name"              => (string) $trip_name,
                                        "mode"              => (string) $line['mode'],
                                        "headsign"          => (string) isset($result->MonitoredVehicleJourney->JourneyNote[0]->value) !== '' && (string) isset($result->MonitoredVehicleJourney->JourneyNote[0]->value) !== '0' ? $result->MonitoredVehicleJourney->JourneyNote[0]->value : '',
                                        "vehicle_size"      => (string) isset($result->MonitoredVehicleJourney->VehicleFeatureRef) ? Functions::getVehicleSize($result->MonitoredVehicleJourney->VehicleFeatureRef) : null,
                                        "description"       => (string) '',
                                        "message" => (string) Functions::getMessage($call),
                                    ),
                                    "stop_date_time"        => Functions::getStopDateTime($call)
                                );

                                $departures[$line_id][] = $dep;
                            }
                        } else {
                            $id = isset($result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef) ? 'IDFM:' . $result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef : '';
                            
                            if (!in_array($id, $existing_departure) && isset($direction[$direction_id])) {
                                $existing_departure[] = $id;
                            
                                $dep = array(
                                    "informations" => array(
                                        "direction" => array(
                                            "id"            => (string) $direction_id,
                                            "name"          => (string) $direction[$direction_id],
                                            "direction_id"  => isset($result->MonitoredVehicleJourney->DirectionRef) ? ((string) $result->MonitoredVehicleJourney->DirectionRef->value == "Aller" ? 0 : 1) : null,
                                        ),
                                        "id"                => (string) $id,
                                        // "id"                => (string) isset($result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef) ? 'IDFM:' . $result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef : '',
                                        "name"              => (string) isset($result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef) ? 'IDFM:' . $result->MonitoredVehicleJourney->FramedVehicleJourneyRef->DatedVehicleJourneyRef : '',
                                        "mode"              => (string) $line['mode'],
                                        "headsign"          => (string) isset($result->MonitoredVehicleJourney->JourneyNote[0]->value) !== '' && (string) isset($result->MonitoredVehicleJourney->JourneyNote[0]->value) !== '0' ? $result->MonitoredVehicleJourney->JourneyNote[0]->value : '',
                                        "description"       => (string) '',
                                        "message" => (string) Functions::getMessage($call),
                                    ),
                                    "stop_date_time"        => Functions::getStopDateTime($call)
                                );
                            }

                            $departures[$line_id][] = $dep;
                        }
                    }
                    }
                    
                }
            }
        }

        foreach ($lines as $line) {
            if (!isset($departures[$line['id']])) {
                $objs = Functions::getSchedulesByStop($db, $id, $line['id'], date("Y-m-d"));
                // $objs_1 = Functions::getSchedulesByStop($db, $id, $line['id'], date("Y-m-d", strtotime('+1 day')));
                // $objs = array_merge($objs, $objs_1);

                foreach ($objs as $obj) {
                    if (Functions::isInNext12Hours($obj['departure_time'], $obj['arrival_time'])) {
                        $direction = Functions::getLastStopOfTrip($db, $obj['trip_id'])[0];
                        $trip_update = Functions::getTripRealtime($trips_update, $obj['trip_id'], $obj['stop_id']);
                        $real_time = Functions::getTripRealtimeDateTime($trip_update, $obj['stop_id']);

                        // On a modified itinerary the vehicle does not go to
                        // the terminus of its trip anymore.
                        $effective_direction = $direction;
                        if ($trip_update != null && $trip_update['state'] == 'exceptional_terminus') {
                            $analyze = Functions::analyzeTripUpdate($trip_update);
                            $terminus = Functions::getParentStopById($db, $analyze['terminus_id']);

                            if ($terminus != null) {
                                $effective_direction = $terminus;
                            }
                        }

                        $base_departure_date_time = Functions::prepareTime($obj['departure_time'], true);
                        $base_arrival_date_time = Functions::prepareTime($obj['arrival_time'], true);

                        // The vehicle never leaves its terminus : its arrival
                        // is the only time to compare with, so the delay stays
                        // visible on a departure board.
                        if ($real_time['is_terminus']) {
                            $base_departure_date_time = $base_arrival_date_time;
                        }

                        if (Functions::isFuture($real_time['departure_date_time'], $real_time['arrival_date_time'], $obj['departure_time'], $obj['arrival_time'])) {
                            $dep = array(
                                "informations" => array(
                                    // Where the vehicle really ends at.
                                    "direction" => array(
                                        "id"                    => (string) $effective_direction['stop_id'],
                                        "name"                  => (string) $effective_direction['stop_name'],
                                        "direction_id"          => (string) $obj['direction_id'],
                                    ),
                                    "id"                        => (string) $obj['trip_id'],
                                    "name"                      => (string) $obj['trip_short_name'],
                                    "mode"                      => (string) $line['mode'],
                                    "headsign"                  => (string) $obj['trip_headsign'],
                                    "description"               => (string) '',
                                ),
                                "stop_date_time" => array(
                                    "base_departure_date_time"  => (string) $base_departure_date_time,
                                    "departure_date_time"       => (string) ($real_time['departure_date_time'] != null ? Functions::prepareTime($real_time['departure_date_time'], true) : $base_departure_date_time),
                                    "base_arrival_date_time"    => (string) $base_arrival_date_time,
                                    "arrival_date_time"         => (string) ($real_time['arrival_date_time'] != null ? Functions::prepareTime($real_time['arrival_date_time'], true) : $base_arrival_date_time),
                                    "state"                     => (string) ($trip_update != null && $trip_update['state'] != null ? $trip_update['state'] : 'theorical'),
                                    "atStop"                    => (string) 'false',
                                    "platform"                  => (string) '-'
                                )
                            );

                            // The terminus of the trip is only given when the
                            // vehicle does not go there anymore.
                            if ($effective_direction['stop_id'] != $direction['stop_id']) {
                                $dep['informations']['original_direction'] = array(
                                    "id"                        => (string) $direction['stop_id'],
                                    "name"                      => (string) $direction['stop_name'],
                                );
                            }

                            $departures[$line['id']][] = $dep;
                        }
                    }
                }
            }
        }

        $lines = Functions::order_line($lines);

        foreach ($lines as $line) {
            if (isset($departures[$line['id']])) {
                foreach ($departures[$line['id']] as $departure) {
                    $line['departures'][] = $departure;
                }
            } else {
                $line['departures'] = [];
            }
            $line['departures'] = Functions::orderDeparture($line['departures']);
            $json['schedules'][] = $line;
        }

        return new JsonResponse($json);
    }

    #[Route('/schedules/{id}/{route_id}', name: 'get_schedules_one_line', methods: ['GET'])]
    #[OA\Tag(name: 'Schedules')]
    public function getSchedulesForRoute($id, $route_id, Request $request)
    {
        // Ensure route_id is in the same format as stored: if it doesn't start with a provider prefix, try to keep as-is
        // Inject the 'l' query parameter so existing getSchedules logic will limit to this line
        $request->query->set('l', $route_id);

        $res = $this->getSchedules($id, $request);

        // getSchedules returns a JsonResponse; decode its content to an array, add the route and return a new JsonResponse
        if ($res instanceof JsonResponse) {
            $status = $res->getStatusCode();
            $content = $res->getContent();
            $data = json_decode($content, true) ?? [];
        }

        $data['route'] = $data['schedules'][0] ?? null;
        $data['geojson'] = $geoJson = $this->shapesRepository->getLineAsGeoJsonSql($route_id);

        unset($data['schedules']);

        // otherwise return original (return as JsonResponse preserving status if available)
        return new JsonResponse($data, isset($status) ? $status : 200);
    }
}