<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use App\Controller\Functions;
use OpenApi\Attributes as OA;
use App\Repository\RoutesRepository;
use App\Repository\TripsRepository;
use App\Service\Logger;
use App\Service\Motis;
use App\Repository\StopsRepository;
use App\Service\PolylineDecoder;

class Journeys
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;

    private Logger $logger;

    private RoutesRepository $routesRepository;
    private TripsRepository $tripsRepository;
    private StopsRepository $stopsRepository;

    private Motis $motis;


    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, Logger $logger, RoutesRepository $routesRepository, TripsRepository $tripsRepository, StopsRepository $stopsRepository, Motis $motis)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->logger = $logger;

        $this->routesRepository = $routesRepository;
        $this->tripsRepository = $tripsRepository;
        $this->stopsRepository = $stopsRepository;

        $this->motis = $motis;
    }

    /**
     * Get journeys
     * 
     * Get journeys between two points
     * 
     * You can specify the traveler type with `traveler_type`
     * 
     * Note that you can only have `arrival` OR `departure`. If both parameters are set, `parture` will be taken into account.
     * If neither is set, `departure` will automatically be set for the current time.
     * 
     * Result can be filtered using more filter parameters like "forbidden_id[]" or "forbidden_lines[]"
     */
    #[Route('/journeys', name: 'get_journeys_search', methods: ['GET'])]
    #[OA\Tag(name: 'Journeys')]
    #[OA\Parameter(
        name:"from",
        in:"query",
        description:"From",
        required: true,
        schema: new OA\Schema(type: 'string', default: 'IDFM:71556')
    )]
    #[OA\Parameter(
        name:"to",
        in:"query",
        description:"To",
        required: true,
        schema: new OA\Schema(type: 'string', default: '2.3099569;48.8958478')
    )]
    #[OA\Parameter(
        name:"departure",
        in:"query",
        description:"Departure date time",
        schema: new OA\Schema(type: 'date-time')
    )]
    #[OA\Parameter(
        name:"arrival",
        in:"query",
        description:"Desired arrival date time",
        schema: new OA\Schema(type: 'date-time')
    )]

    
    #[OA\Parameter(
        name:"traveler_type",
        in:"query",
        description:"Type of traveler ",
        schema: new OA\Schema(type: 'string', enum: ['standard', 'luggage', 'wheelchair'])
    )]
    #[OA\Parameter(
        name:"forbidden_id",
        in:"query",
        description:"Forbidden lines or stops id",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "string")
        )
    )]
    #[OA\Parameter(
        name:"modes",
        in:"query",
        description:"Allowed transportation mode",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "string", enum: ['rail', 'metro', 'tram', 'bus', 'cable', 'funicular', 'boat'])
        )
    )]

    #[OA\Response(
        response: 200,
        description: 'OK'
    )] 
    
    public function getJourneysSearch(Request $request)
    {
        $from   = $request->get('from');
        $to     = $request->get('to');
        
        if (!isset($from) || !isset($to)) {
            return new JsonResponse(Functions::httpErrorMessage(400, 'One or more parameters are missing or null, have you "from" and "to" ?'), 400);
        }
        

        if (substr($from, 0, 5) === "IDFM:") {
            $from = 'stop_area:' . $from;
        } else {
            $from = explode(";", $from);
            if ((float)$from[1] < (float)$from[0]) {
                $from = $from[1] . ';' . $from[0];
            } else {
                 $from = $from[0] . ';' . $from[1];
            }
        }
        
        if (substr($to, 0, 5) === "IDFM:") {
            $to = 'stop_area:' . $to;
        } else {
            $to = explode(";", $to);
            if ((float)$to[1] < (float)$to[0]) {
                $to = $to[1] . ';' . $to[0];
            } else {
                $to = $to[0] . ';' . $to[1];
            }
        }

        // ------------
        
        $departure = $request->get('departure');
        $arrival = $request->get('arrival');

        if ($arrival != null) {
            $datetime = $arrival;
            $datetime_represents = 'arrival';
        } elseif ($departure != null) {
            $datetime = $departure;
            $datetime_represents = 'departure';
        } else {
            $datetime = date(DATE_ATOM);
            $datetime_represents = 'departure';
        }

        $traveler_type = $request->get('traveler_type') ?? 'standard';

        $datetime = explode(".", $datetime)[0];

        // forbidden_mode
        $params = array(
            'from' => $from,
            'to' => $to,
            'datetime' => $datetime,
            'datetime_represents' => $datetime_represents,
            'traveler_type' => $traveler_type,
            'depth' => '3',
            'data_freshness' => 'realtime',
            'forbidden_uris' => array_merge(Functions::getLinesList($request->get('forbidden_id')), Functions::getModesURIList($request->get('forbidden_mode'))),
        );

        $url = Functions::buildUrl($this->params->get('prim_url') . '/journeys', $params);

        $json = $this->getPrimJourneys($url, $request->get('flag'));
        if (isset($json['error']['code'])) {
            return new JsonResponse($json, $json['error']['code']); 
        }
        return new JsonResponse($json);
    }
    
    /**
     * Get journey
     * 
     * Get journey based on a unique_id
     * 
     * This endpoint is used to get a journey already searched
     */
    #[Route('/journey/{id}', name: 'get_journeys_id', methods: ['GET'])]
    #[OA\Tag(name: 'Journeys')]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "Journey id",
        required: true,
    )]

    #[OA\Response(
        response: 200,
        description: 'OK'
    )]

    public function getPrimJourneysId($id, Request $request)
    {
        if (!isset($id) || $id == null) {
            $this->logger->logHttpErrorMessage($request, "Nothing where found for this journeys id", 'WARN');
            return Functions::httpErrorMessage(400, 'Nothing where found for this journeys id');
        }

        $url = Functions::base64url_decode($id);

        if ($url == false) {
            $this->logger->logHttpErrorMessage($request, "Nothing where found for this journeys id", 'WARN');
            return Functions::httpErrorMessage(400, 'Nothing where found for this journeys id');
        }


        // ------------

        $url = $this->params->get('prim_url') . '/' . $url;

        $json = $this->getPrimJourneys($url, $request->get('flag'), $id);
        return new JsonResponse(['journey' => $json['journeys'][0]]);
    }

    public function getPrimJourneys($url, $flag, $uid = null)
    {
        $client = HttpClient::create();        
        $response = $client->request('GET', $url, [
            'headers' => [
                'apiKey' => $this->params->get('prim_api_key'),
            ],
        ]);
        $status = $response->getStatusCode();

        if ($status != 200){
            print($url); // DEBUG
            print($response->getContent()); // DEBUG
            return Functions::httpErrorMessage(500, 'Cannot get data from provider');
        }

        $content = $response->getContent();
        $results = json_decode($content);

        $journeys = [];
        foreach ($results->journeys as $result) {
            $public_transport_distance = 0;

            $ignore = false;

            $sections = [];
            foreach ($result->sections as $section) {

                if (isset($section->mode) && ($section->mode == 'bike' || $section->mode == 'bss' || $section->mode == 'car')){
                    $ignore = true;
                }

                $informations = [];
                
                if (isset($section->display_informations)) {
                    
                    $route = $this->routesRepository->findOneBy( ['route_id' => "IDFM:" . Functions::idfmFormat( Functions::getLineId($section->links) ) ] );

                    $informations = array(
                        "direction" => array(
                            "id"        =>  (string)    $section->display_informations->direction,
                            "name"      =>  (string)    $section->display_informations->direction,
                        ),
                        "trip_id"            =>  (string)    isset($result->ItemIdentifier) !== '' && (string)    isset($result->ItemIdentifier) !== '0' ? $result->ItemIdentifier : "",
                        "name"     =>  (string)    $section->display_informations->trip_short_name,
                        "headsign"      =>  (string)    $section->display_informations->headsign,
                        "description"   =>  (string)    $section->display_informations->description,
                        "message"       =>  (string)    "",
                        "line"     => $route != null ? $route->getRouteAndTrafic() : [],
                    );
                }
                $sections[] = array(
                    "type"          =>  (string)    $section->type,
                    "mode"          =>  (string)    isset($section->mode) !== '' && (string) isset($section->mode) ? $section->mode : $section->type,
                    "arrival_date_time"     =>  (string)    $section->arrival_date_time,
                    "departure_date_time"   =>  (string)    $section->departure_date_time,
                    "duration"      =>  (int)       $section->duration,
                    "informations"  => isset($section->display_informations) ? $informations : null,
                    "from" => isset($section->from)
                        ? array(
                            "id"        =>  (string)    $section->from->id,
                            "name"      =>  (string)    $section->from->{$section->from->embedded_type}->name,
                            "type"      =>  (string)    $section->from->embedded_type,
                            "distance"  =>  (int)       isset($section->from->distance) !== 0 ? $section->from->distance : 0,
                            "town"      =>  (string)    !isset($section->from->{$section->from->embedded_type}->administrative_regions) ? '' : Functions::getTownByAdministrativeRegions($section->from->{$section->from->embedded_type}->administrative_regions),
                            "zip_code"  =>  (string)    !isset($section->from->{$section->from->embedded_type}->administrative_regions) ? '' : substr(Functions::getZipByAdministrativeRegions($section->from->{$section->from->embedded_type}->administrative_regions), 0, 2),
                            "coord"     => array(
                                "lat"           =>  (float) $section->from->{$section->from->embedded_type}->coord->lat,
                                "lon"           =>  (float) $section->from->{$section->from->embedded_type}->coord->lon,
                            ),
                        )
                        : array()
                    ,
                    "to" => isset($section->to)
                        ? array(
                            "id"        =>  (string)    $section->to->id,
                            "name"      =>  (string)    $section->to->{$section->to->embedded_type}->name,
                            "type"      =>  (string)    $section->to->embedded_type,
                            "town"      =>  (string)    !isset($section->to->{$section->to->embedded_type}->administrative_regions) ? '' : Functions::getTownByAdministrativeRegions($section->to->{$section->to->embedded_type}->administrative_regions),
                            "zip_code"  =>  (string)    !isset($section->to->{$section->to->embedded_type}->administrative_regions) ? '' : substr(Functions::getZipByAdministrativeRegions($section->to->{$section->to->embedded_type}->administrative_regions), 0, 2),
                            "coord"     => array(
                                "lat"       =>  (float) $section->to->{$section->to->embedded_type}->coord->lat,
                                "lon"       =>  (float) $section->to->{$section->to->embedded_type}->coord->lon,
                            ),
                        )
                        : array()
                    ,
                    "stop_date_times"           => $this->getPrimStopDateTimes($section),
                    "geojson"                   => isset($section->geojson)                 ? $section->geojson : null,
                    "boarding_positions"        => isset($section->best_boarding_positions) ? $section->best_boarding_positions : null,
                    "access_point"              => isset($section->vias[0]->access_point)   ? $section->vias[0]->access_point : null,
                );
                if ($section->type == "public_transport") {
                    $public_transport_distance += (int) $section->geojson->properties[0]->length;
                }
            }

            if (!$ignore) {
                $journeys[] = array(
                    "type"                  =>  (string) $result->type,
                    "duration"              =>  (int) $result->duration,
                    "unique_id"             =>  (string) $uid != null ? $uid : ( isset($result->links) ? Functions::getJourneyId($result->links) : '' ),
    
                    "requested_date_time"   => $result->requested_date_time,
                    "departure_date_time"   => $result->departure_date_time,
                    "arrival_date_time"     => $result->arrival_date_time,
    
                    "co2_emission"          => $result->co2_emission->value,
                    "car_co2_emission"      => $results->context->car_direct_path->co2_emission->value,
                    "fare"                  => isset($result->fare->total->value) ? $result->fare->total->value / 100 : 0 ,
                    "distances"             => array(
                        "walking"                  => $result->distances->walking,
                        "public_transport"         => $public_transport_distance
                    ),
                    "sections"              => $sections
                );
            }            
        }

        $json = [
            "journeys" => $journeys,
            "provider" => [
                "id"    => 'IDFM',
                "name"  => 'Île-de-France Mobilités',
                "img"  => 'https://app.navika.fr/img/idfm.png',
                "url"  => 'https://www.iledefrance-mobilites.fr/'
            ]
        ];

        if ($flag != null) {
            $json["flag"] = (int) $flag;
        }

        return $json;
    }

    /**
     * Get journeys using MOTIS
     * 
     * Alternative to /journeys but powered by MOTIS instead of Navitia.
     * Accepts the same parameters and returns the same JSON shape.
     */
    #[Route('/motis/journeys', name: 'get_motis_journeys_search', methods: ['GET'])]
    #[OA\Tag(name: 'Journeys')]
    #[OA\Parameter(
        name:"from",
        in:"query",
        description:"From (IDFM:xxxxx or lon;lat)",
        required: true,
        schema: new OA\Schema(type: 'string', default: 'IDFM:71556')
    )]
    #[OA\Parameter(
        name:"to",
        in:"query",
        description:"To (IDFM:xxxxx or lon;lat)",
        required: true,
        schema: new OA\Schema(type: 'string', default: '2.3099569;48.8958478')
    )]
    #[OA\Parameter(
        name:"departure",
        in:"query",
        description:"Departure date time",
        schema: new OA\Schema(type: 'date-time')
    )]
    #[OA\Parameter(
        name:"arrival",
        in:"query",
        description:"Desired arrival date time",
        schema: new OA\Schema(type: 'date-time')
    )]
    #[OA\Parameter(
        name:"traveler_type",
        in:"query",
        description:"Type of traveler ",
        schema: new OA\Schema(type: 'string', enum: ['standard', 'luggage', 'wheelchair'])
    )]
    #[OA\Parameter(
        name:"modes",
        in:"query",
        description:"Allowed transportation mode",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "string", enum: ['rail', 'metro', 'tram', 'bus', 'cable', 'funicular', 'boat'])
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'OK'
    )] 
    public function getMotisJourneysSearch(Request $request)
    {
        $from   = $request->get('from');
        $to     = $request->get('to');

        if (!isset($from) || !isset($to)) {
            return Functions::httpErrorMessage(400, 'One or more parameters are missing or null, have you "from" and "to" ?');
        }

        // Resolve input into coordinates (lat,lon) for MOTIS fromPlace/toPlace
        $fromPlace = $this->resolveToLatLon($from);
        $toPlace = $this->resolveToLatLon($to);

        if ($fromPlace === null || $toPlace === null) {
            return new JsonResponse(Functions::httpErrorMessage(400, 'Unable to resolve "from" or "to" to coordinates (expected IDFM:xxxxx or lon;lat)'), 400);
        }

        $departure = $request->get('departure');
        $arrival = $request->get('arrival');

        if ($arrival != null) {
            $time = $arrival;
            $arriveBy = 'true';
        } elseif ($departure != null) {
            $time = $departure;
            $arriveBy = 'false';
        } else {
            $time = date(DATE_ATOM);
            $arriveBy = 'false';
        }

        $traveler_type = $request->get('traveler_type') ?? 'standard';

        // Map traveler_type -> pedestrianProfile
        $pedestrianProfile = match ($traveler_type) {
            'wheelchair' => 'WHEELCHAIR',
            default => 'FOOT',
        };

        // Map modes -> transitModes for MOTIS
        $transitModes = $this->mapTransitModes($request->get('modes'));

        $time = $this->normalizeTime($time);
        $query = [
            'fromPlace' => $fromPlace, // "lat,lon"
            'toPlace' => $toPlace,
            'time' => $time,
            'arriveBy' => $arriveBy,
            'pedestrianProfile' => $pedestrianProfile,
        ];
        if ($transitModes !== null) {
            $query['transitModes'] = implode(',', $transitModes);
        }

        $json = $this->getMotisJourneys(Motis::PLAN_PATH, $query, $request->get('flag'));

        if (isset($json['error']['code'])) {
            return new JsonResponse($json, $json['error']['code']); 
        }
        return new JsonResponse($json);
    }

    /**
     * Get MOTIS journey from unique id
     */
    #[Route('/motis/journey/{id}', name: 'get_motis_journey_id', methods: ['GET'])]
    #[OA\Tag(name: 'Journeys')]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "MOTIS journey id",
        required: true,
    )]
    #[OA\Response(
        response: 200,
        description: 'OK'
    )]
    public function getMotisJourneysId($id, Request $request)
    {
        if (!isset($id) || $id == null) {
            $this->logger->logHttpErrorMessage($request, "Nothing where found for this journeys id", 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this journeys id'), 400);
        }

        $path = Functions::base64url_decode($id);
        if ($path == false) {
            $this->logger->logHttpErrorMessage($request, "Nothing where found for this journeys id", 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this journeys id'), 400);
        }

        // $path porte le chemin complet de l'appel MOTIS d'origine, requête
        // comprise (cf. unique_id plus bas). L'instance à interroger, elle, est
        // résolue au moment de l'appel : un identifiant reste valable même si la
        // production a basculé sur l'autre instance entre-temps.
        $parsed = parse_url($path);
        $query = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        $json = $this->getMotisJourneys('/' . ltrim($parsed['path'] ?? $path, '/'), $query, $request->get('flag'), $id);
        if (isset($json['error']['code'])) {
            return new JsonResponse($json, $json['error']['code']);
        }

        return new JsonResponse(['journey' => $json['journeys'][0] ?? null]);
    }

    /**
     * Interroge l'instance MOTIS en service et met la réponse au format de
     * l'API Navika, identique à celui de /journeys.
     *
     * @param array<string, mixed> $query
     */
    private function getMotisJourneys(string $path, array $query, $flag, $uid = null)
    {
        $result = $this->motis->request($path, $query);

        if (!$result['ok']) {
            $this->logger->log([
                'message' => sprintf('[motis] requête %s en échec : %s', $path, $result['error']),
            ], 'WARN');

            // 503 et non 500 : MOTIS est un moteur secondaire, son
            // indisponibilité n'est pas une erreur de l'API. Le client peut se
            // rabattre sur /journeys, qui reste servi par l'API IDFM.
            return Functions::httpErrorMessage(503, 'Moteur MOTIS indisponible : ' . $result['error']);
        }

        $content = (string) $result['content'];
        $results = json_decode($content);

        $json = $results;
        file_put_contents('/tmp/motis_journeys.json', json_encode($json));

        if ($results === null) {
            return Functions::httpErrorMessage(502, 'Réponse MOTIS illisible');
        }

        // Expect either top-level itineraries or nested under plan
        $itineraries = [];
        if (isset($results->itineraries)) {
            $itineraries = $results->itineraries;
        } elseif (isset($results->plan) && isset($results->plan->itineraries)) {
            $itineraries = $results->plan->itineraries;
        }

        // Contexte temporel de la requête, pour compléter les itinéraires dont
        // MOTIS ne renvoie pas toutes les dates.
        $requestTimeParam = ($query['time'] ?? null) ?: null;
        $arriveByParam = ($query['arriveBy'] ?? null) === 'true';

        $journeys = [];
        foreach ($itineraries as $it) {
            $sections = [];
            $public_transport_distance = 0;
            $walking_distance = 0;

            $legs = $it->legs ?? [];
            $firstDeparture = null;
            $lastArrival = null;

            $itStart = isset($it->startTime) ? (string)$it->startTime : null;
            $itEnd = isset($it->endTime) ? (string)$it->endTime : null;

            foreach ($legs as $leg) {
                $mode = isset($leg->mode) ? (string)$leg->mode : '';
                $isTransit = in_array($mode, ['TRANSIT','TRAM','SUBWAY','FERRY','AIRPLANE','SUBURBAN','BUS','COACH','RAIL','HIGHSPEED_RAIL','LONG_DISTANCE','NIGHT_RAIL','REGIONAL_FAST_RAIL','REGIONAL_RAIL','CABLE_CAR','FUNICULAR','AERIAL_LIFT','OTHER','AREAL_LIFT','METRO']);

                // Extract times with fallbacks (leg.* then from/to place scheduled times)
                $dep = isset($leg->departure) && $leg->departure !== '' ? (string)$leg->departure : (
                    (isset($leg->from->departure) && $leg->from->departure !== '' ? (string)$leg->from->departure : (
                        isset($leg->from->scheduledDeparture) ? (string)$leg->from->scheduledDeparture : ''
                    ))
                );
                $arr = isset($leg->arrival) && $leg->arrival !== '' ? (string)$leg->arrival : (
                    (isset($leg->to->arrival) && $leg->to->arrival !== '' ? (string)$leg->to->arrival : (
                        isset($leg->to->scheduledArrival) ? (string)$leg->to->scheduledArrival : ''
                    ))
                );

                if ($firstDeparture === null) { $firstDeparture = $dep; }
                if ($arr !== '') { $lastArrival = $arr; }

                $distance = isset($leg->distance) ? (int)$leg->distance : 0;
                if ($isTransit) { $public_transport_distance += $distance; }
                if (!$isTransit && $mode === 'WALK') { $walking_distance += $distance; }

                // Build informations for transit legs
                $informations = null;
                if ($isTransit) {
                    $direction = isset($leg->tripTo) ? $leg->tripTo : null;

                    $direction_id  = isset($direction->stopId) ? explode('_', $direction->stopId)[1] : null; 
                    $direction_name  = isset($direction->name) ? $direction->name : '';

                    $headsign = isset($leg->headsign) ? (string)$leg->headsign : '';
                    $tripId = isset($leg->tripId) ? (string)$leg->tripId : '';
                    $tripShortName = isset($leg->tripShortName) ? (string)$leg->tripShortName : '';
                    $displayName = isset($leg->displayName) ? (string)$leg->displayName : '';

                    // Build line object from MOTIS leg fields
                    $route_id = explode('_', $leg->routeId)[1];
                    $route = $this->routesRepository->findOneBy( ['route_id' => $route_id ] );

                    $informations = [
                        'direction' => [
                            'id' => $direction_id,
                            'name' => $direction_name,
                        ],
                        'trip_id' => $tripId,
                        'name' => $tripShortName ?: $displayName,
                        'headsign' => $headsign,
                        'description' => '',
                        'message' => '',
                        'line'     => $route != null ? $route->getRouteAndTrafic() : $this->buildMotisLine($leg),
                    ];
                }

                // Places
                $from = isset($leg->from) ? $leg->from : null;
                $to = isset($leg->to) ? $leg->to : null;

                $points = isset($leg->legGeometry) && isset($leg->legGeometry->points) ? (string)$leg->legGeometry->points : '';
                $coordinates = PolylineDecoder::decode($points, $leg->legGeometry->precision ?? 6, $leg->distance ?? 0);

                $sections[] = [
                    'type' => $isTransit ? 'public_transport' : ($mode === 'WALK' ? 'street_network' : strtolower($mode)),
                    'mode' => $isTransit ? strtolower($mode) : ($mode === 'WALK' ? 'walking' : strtolower($mode)),
                    'arrival_date_time' => $arr,
                    'departure_date_time' => $dep,
                    'duration' => $this->durationBetween($dep, $arr),
                    'informations' => $informations,
                    'from' => $from ? [
                        'id' => isset($from->stopId) ? (string)$from->stopId : (isset($from->id) ? (string)$from->id : ''),
                        'name' => isset($from->name) ? (string)$from->name : '',
                        'type' => isset($from->vertexType) ? strtolower((string)$from->vertexType) : 'place',
                        'distance' => 0,
                        'town' => '',
                        'zip_code' => '',
                        'coord' => [
                            'lat' => isset($from->lat) ? (float)$from->lat : 0,
                            'lon' => isset($from->lon) ? (float)$from->lon : 0,
                        ],
                    ] : [],
                    'to' => $to ? [
                        'id' => isset($to->stopId) ? (string)$to->stopId : (isset($to->id) ? (string)$to->id : ''),
                        'name' => isset($to->name) ? (string)$to->name : '',
                        'type' => isset($to->vertexType) ? strtolower((string)$to->vertexType) : 'place',
                        'town' => '',
                        'zip_code' => '',
                        'coord' => [
                            'lat' => isset($to->lat) ? (float)$to->lat : 0,
                            'lon' => isset($to->lon) ? (float)$to->lon : 0,
                        ],
                    ] : [],
                    'stop_date_times' => $this->getMotisStopDateTimes($leg),
                    'geojson' => $coordinates,
                    'boarding_positions' => null,
                    'access_point' => null,
                ];
            }

            // Determine journey-level departure/arrival with fallbacks
            $journeyDeparture = $firstDeparture ?: $itStart ?: ($arriveByParam ? null : $requestTimeParam);
            $journeyArrival = $lastArrival ?: $itEnd ?: ($arriveByParam ? $requestTimeParam : null);
            $requestedDateTime = $requestTimeParam;

            $journeys[] = [
                'type' => '',
                'duration' => isset($it->duration) ? (int)$it->duration : $this->durationBetween($firstDeparture, $lastArrival),
                'unique_id' => $uid ?? Functions::base64url_encode($path . ($query === [] ? '' : '?' . http_build_query($query))),
                'requested_date_time' => $requestedDateTime,
                'departure_date_time' => $journeyDeparture,
                'arrival_date_time' => $journeyArrival,
                'co2_emission' => 0,
                'car_co2_emission' => 0,
                'fare' => 0,
                'distances' => [
                    'walking' => $walking_distance,
                    'public_transport' => $public_transport_distance,
                ],
                'sections' => $sections,
            ];
        }

        $json = [
            'journeys' => $journeys,
            'provider' => [
                'id' => 'MOTIS',
                'name' => 'MOTIS',
                'img' => 'https://app.navika.fr/img/motis.png',
                'url' => 'https://github.com/motis-project/motis'
            ]
        ];

        if ($flag != null) {
            $json['flag'] = (int)$flag;
        }

        return $json;
    }

    private function resolveToLatLon(string $val): ?string
    {
        // Returns string "lat,lon" or null
        // 1) Couple de coordonnées "a;b".
        //    Les clients envoient indifféremment lon;lat (format documenté et
        //    utilisé par /journeys) ou lat;lon. /journeys tranche par magnitude ;
        //    on applique la même règle ici, sinon une requête en coordonnées part
        //    avec latitude et longitude inversées et MOTIS, qui ne trouve rien à
        //    ces coordonnées, répond sans erreur avec zéro itinéraire.
        //    En métropole la longitude (-5..9) est toujours inférieure à la
        //    latitude (41..51) : la plus grande des deux valeurs est la latitude.
        //    MOTIS attend "lat,lon".
        if (str_contains($val, ';')) {
            $parts = explode(';', $val);
            if (count($parts) === 2) {
                $a = trim($parts[0]);
                $b = trim($parts[1]);
                if (is_numeric($a) && is_numeric($b)) {
                    $lat = max((float) $a, (float) $b);
                    $lon = min((float) $a, (float) $b);

                    return $lat . ',' . $lon;
                }
            }
        }

        // 2) Try direct stop_id match (any provider prefix possible)
        $byId = $this->stopsRepository->findStopById($val);
        if ($byId) {
            return $byId->getStopLat() . ',' . $byId->getStopLon();
        }

        // 3) Try extensions with given value (can be IDFM:*, STIF:*, SNCF:*, ...)
        $byExt = $this->stopsRepository->findStopsByExtensions($val);
        if ($byExt && isset($byExt[0])) {
            $s = $byExt[0];
            return $s->getStopLat() . ',' . $s->getStopLon();
        }

        // 4) Try with normalized code (remove known prefixes)
        $normalized = Functions::idfmFormat($val);
        if ($normalized !== $val) {
            $byId2 = $this->stopsRepository->findStopById($normalized);
            if ($byId2) {
                return $byId2->getStopLat() . ',' . $byId2->getStopLon();
            }
            $byExt2 = $this->stopsRepository->findStopsByExtensions($normalized);
            if ($byExt2 && isset($byExt2[0])) {
                $s = $byExt2[0];
                return $s->getStopLat() . ',' . $s->getStopLon();
            }
        }

        return null;
    }

    private function normalizeTime(?string $time): string
    {
        // Ensure RFC3339 without fractional seconds, in UTC, e.g. 2025-11-08T18:45:22Z
        if (!$time || trim($time) === '') {
            return gmdate('Y-m-d\TH:i:s\Z');
        }
        $t = trim($time);
        // Strip fractional seconds if present
        if (preg_match('/^(.*T\d{2}:\d{2}:\d{2})\.[0-9]+(.*)$/', $t, $m)) {
            $t = $m[1] . $m[2];
        }
        try {
            // Detect timezone info
            $hasTz = preg_match('/[zZ]|[\+\-]\d{2}:?\d{2}$/', $t) === 1;
            if ($hasTz) {
                $dt = new \DateTimeImmutable($t);
            } else {
                // Assume UTC if no timezone given
                $dt = new \DateTimeImmutable($t, new \DateTimeZone('UTC'));
            }
            $dt = $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d\TH:i:s\Z');
        } catch (\Throwable $e) {
            return gmdate('Y-m-d\TH:i:s\Z');
        }
    }

    private function mapTransitModes($modes)
    {
        if ($modes === null) { return null; }
        if (is_string($modes)) {
            // handle comma-separated
            $modes = array_map('trim', explode(',', $modes));
        }
        if (!is_array($modes)) { return null; }

        $map = [
            'rail' => 'RAIL',
            'metro' => 'SUBWAY',
            'tram' => 'TRAM',
            'bus' => 'BUS',
            'cable' => 'CABLE_CAR',
            'funicular' => 'FUNICULAR',
            'boat' => 'FERRY',
        ];
        $res = [];
        foreach ($modes as $m) {
            $m = strtolower($m);
            if (isset($map[$m])) { $res[] = $map[$m]; }
        }
        return $res ?: null;
    }

    private function getPrimStopDateTimes($section): ?array
    {
        if (!isset($section->stop_date_times) || !is_array($section->stop_date_times)) {
            return null;
        }

        $stop_date_times = [];
        foreach (array_slice($section->stop_date_times, 1, -1) as $stop_date_time) {
            $stop_point = $stop_date_time->stop_point ?? null;

            $stop_date_times[] = array(
                "stop_point" => array(
                    "id"        => (string) ($stop_point->id ?? ''),
                    "parent_id" => (string) ($stop_point->stop_area->id ?? ''),
                    "name"      => (string) ($stop_point->name ?? ''),
                    "coord"     => array(
                        "lat"   => (float) ($stop_point->coord->lat ?? 0),
                        "lon"   => (float) ($stop_point->coord->lon ?? 0),
                    ),
                ),
                "base_departure_date_time"  => self::formatStopTime($stop_date_time->base_departure_date_time ?? null),
                "departure_date_time"       => self::formatStopTime($stop_date_time->departure_date_time ?? null),
                "base_arrival_date_time"    => self::formatStopTime($stop_date_time->base_arrival_date_time ?? null),
                "arrival_date_time"         => self::formatStopTime($stop_date_time->arrival_date_time ?? null),
            );
        }

        return $stop_date_times;
    }

    private function getMotisStopDateTimes($leg): ?array
    {
        if (!isset($leg->intermediateStops) || !is_array($leg->intermediateStops)) {
            return null;
        }

        $stop_date_times = [];
        foreach ($leg->intermediateStops as $stop) {
            $stop_date_times[] = array(
                "stop_point" => array(
                    "id"        => self::motisStopId($stop->stopId ?? null),
                    "parent_id" => self::motisStopId($stop->parentId ?? null),
                    "name"      => (string) ($stop->name ?? ''),
                    "coord"     => array(
                        "lat"   => (float) ($stop->lat ?? 0),
                        "lon"   => (float) ($stop->lon ?? 0),
                    ),
                ),
                "base_departure_date_time"  => self::formatStopTime($stop->scheduledDeparture ?? null),
                "departure_date_time"       => self::formatStopTime($stop->departure ?? null),
                "base_arrival_date_time"    => self::formatStopTime($stop->scheduledArrival ?? null),
                "arrival_date_time"         => self::formatStopTime($stop->arrival ?? null),
            );
        }

        return $stop_date_times;
    }

    private static function formatStopTime(?string $date_time): string
    {
        if ($date_time === null || trim($date_time) === '') {
            return '';
        }

        $timezone = new \DateTimeZone('Europe/Paris');

        try {
            // Le fuseau donné au constructeur ne s'applique qu'aux dates qui
            // n'en portent pas, soit exactement le cas de Navitia.
            $date = new \DateTimeImmutable($date_time, $timezone);
        } catch (\Throwable $e) {
            return '';
        }

        return $date->setTimezone($timezone)->format(DATE_ATOM);
    }

    private function buildMotisLine($leg): array
    {
        // Extract MOTIS leg route/display related fields
        $routeShortName   = isset($leg->routeShortName) ? (string)$leg->routeShortName : '';
        $routeLongName    = isset($leg->routeLongName) ? (string)$leg->routeLongName : ($routeShortName ?: '');
        $routeType        = isset($leg->routeType) ? (string)$leg->routeType : null; // numeric or string
        $routeColor       = isset($leg->routeColor) ? (string)$leg->routeColor : '888888';
        $routeTextColor   = isset($leg->routeTextColor) ? (string)$leg->routeTextColor : '888888';
        $agencyId         = isset($leg->agencyId) ? (string)$leg->agencyId : '';

        // Normalize colors (strip leading '#', truncate to 6 chars)
        $normalizeColor = function($c) {
            if ($c === null || $c === '') { return '888888'; }
            $c = ltrim($c, '#');
            return substr($c, 0, 6) ?: '888888';
        };
        $routeColor     = $normalizeColor($routeColor);
        $routeTextColor = $normalizeColor($routeTextColor);

        // Convert routeType to navika mode if numeric (GTFS route_type) else fallback to lowercased mode
        $mode = 'other';
        if ($routeType !== null && is_numeric($routeType)) {
            $mode = Functions::getTransportMode((int)$routeType);
        } elseif (isset($leg->mode)) {
            $mode = strtolower((string)$leg->mode);
        }

        return [
            'id' => null,
            'code' => $routeShortName,
            'name' => $routeLongName,
            'mode' => $mode,
            'color' => $routeColor,
            'text_color' => $routeTextColor,
            'agency' => [
                'id' => $agencyId,
                'name' => $agencyId,
                'area' => $agencyId,
            ],
            'severity' => 0,
            'reports' => [
                'future_work' => [],
                'current_work' => [],
                'current_trafic' => [],
            ],
        ];
    }

    private static function motisStopId(?string $id): string
    {
        if ($id === null || $id === '') {
            return '';
        }

        $separator = strpos($id, '_');

        return $separator === false ? $id : substr($id, $separator + 1);
    }

    private function durationBetween(?string $from, ?string $to): int
    {
        if (!$from || !$to) { return 0; }
        try {
            $df = new \DateTime($from);
            $dt = new \DateTime($to);
            return max(0, $dt->getTimestamp() - $df->getTimestamp());
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
