<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Controller\Functions;
use App\Repository\RoutesRepository;
use App\Repository\StopRouteRepository;
use App\Repository\AgencyRepository;
use OpenApi\Attributes as OA;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Lines
{
    private $entityManager;
    private $params;

    private RoutesRepository $routesRepository;
    private StopRouteRepository $stopRouteRepository;
    private AgencyRepository $agencyRepository;
    
    public function __construct(EntityManagerInterface $entityManager, RoutesRepository $routesRepository, StopRouteRepository $stopRouteRepository, AgencyRepository $agencyRepository, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->routesRepository = $routesRepository;
        $this->stopRouteRepository = $stopRouteRepository;
        $this->agencyRepository = $agencyRepository;
    }
    
    /**
     * Get routes
     * 
     * Get routes based on query parameters. 
     *  
     * 
     * Result can be filtered using more filter parameters like "allowed_modes[]" or "forbidden_lines[]"
     */
    #[Route('/lines', name: 'search_lines', methods: ['GET'])]
    #[OA\Tag(name: 'Lines')]
    #[OA\Parameter(
        name:"q",
        in:"query",
        description:"Query (Short or Long Name)",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]

    #[OA\Parameter(
        name:"allowed_modes[]",
        in:"query",
        description:"Array of allowed transportation modes",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "string")
        )
    )]
    #[OA\Parameter(
        name:"forbidden_modes[]",
        in:"query",
        description:"Array of forbidden transportation modes",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "string")
        )
    )]

    #[OA\Parameter(
        name:"allowed_lines[]",
        in:"query",
        description:"An array of allowed lines",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "string")
        )
    )]
    #[OA\Parameter(
        name:"forbidden_lines[]",
        in:"query",
        description:"An array of forbidden lines",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "string")
        )
    )]

    #[OA\Response(
        response: 200,
        description: ''
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function searchLines(Request $request): JsonResponse 
    {
        $q = $request->get('q');
        $q = urldecode( trim( $q ) );
        $query = $q;

        if ( is_string($query) && $query == "" ) {
            $json = [];
            $json['lines'] = [];
            if ($request->get('flag') != null) {
                $json["flag"] = (int) $request->get('flag');
            }
            return new JsonResponse($json);

        } else if ( !is_string($query) ){
            return new JsonResponse(Functions::ErrorMessage(400, 'One or more parameters are missing or null, have you "q" ?'), 400);
        }

        // ------ Request
        //        
        $routes1 = $this->routesRepository->findByShortName( $query );
        $routes2 = $this->routesRepository->findByLongName( $query );
        $agencies = $this->agencyRepository->findByName( $query );

        //Pre process for agency 
        $routes3 = [];

        foreach($agencies as $agency) {
            $routes_agency = $agency->getRoutes();
            foreach( $routes_agency as $r ) {
                $routes3[] = $r;
            }
        }
        $routes = array_merge($routes1, $routes2, $routes3);

        // ------ Places
        //
        $lines = [];

        foreach($routes as $route) {
            $filter = true;

            $id = $route->getRouteId();

            if ($request->get('allowed_modes')) {
                $filter = in_array(Functions::getTransportMode($route->getRouteType()), $request->get('allowed_modes'));
            }
            if ($request->get('forbidden_modes')) {
                $filter = !in_array(Functions::getTransportMode($route->getRouteType()), $request->get('forbidden_modes'));
            }

            if ($request->get('allowed_lines')) {
                $filter = in_array($route->getRouteId()->getRouteId(), $request->get('allowed_lines'));
            }
            if ($request->get('forbidden_lines')) {
                $filter = !in_array($route->getRouteId()->getRouteId(), $request->get('forbidden_lines'));
            }

            if ( $filter ) {
                $filter = !in_array($route->getRouteShortName(), $this->params->get('lines.hidden'));
            }
            

            // allowed_modes[]  -   forbidden_modes[]
            // allowed_lines[]    -   forbidden_lines[]

            if ($filter && !isset( $lines[$id] )) {
                $lines[$id] = $route->getRoute();
            }
        }

        $json = [];
        $json['lines'] = [];
        
        foreach ($lines as $key => $line) {
            $json['lines'][] = $line;
        }

        $json['lines'] = Functions::order_routes( $json['lines'], $query );
        
        array_splice($json['lines'], 30);

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }

    /**
     * Get routes
     * 
     * Get routes informations 
     *  
     */
    #[Route('/lines/{id}', name: 'get_line_details', methods: ['GET'])]
    #[OA\Tag(name: 'Lines')]
    #[OA\Parameter(
        name:"id",
        in:"path",
        description:"Line ID",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]

    #[OA\Response(
        response: 200,
        description: ''
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function getLineDetails($id, Request $request): JsonResponse 
    {
        $db = $this->entityManager->getConnection();

        //--- On regarde si l'arrêt existe bien et on recuppere toutes les lignes
        $route = $this->routesRepository->findOneBy( ['route_id' => $id] );

        if ( $route == null ) {
            return new JsonResponse(Functions::ErrorMessage(400, 'Nothing where found for this route'), 400);
        }

        $_terminus = Functions::getTerminusForLine($db, $route);

        $terminus = [];
        foreach($_terminus as $terminu) {
            $terminus[] = array(
                "id"      =>  (String)    $terminu['stop_id'],
                "name"    =>  (String)    $terminu['stop_name'],
            );
        }

        // ---
        $timetables = [];
        $timetables['map'] = [];
        $timetables['timetables'] = [];

        $_timetables = $route->getTimetables();
        foreach( $_timetables as $timetable) {
            if ( $timetable->getType() == 'map') {
                $timetables['map'][] = array(
                    "name"      => (String)     $timetable->getName(),
                    "url"       => (String)     $timetable->getUrl(),
                );
            }
            if ( $timetable->getType() == 'timetables' && str_ends_with($timetable->getUrl(), '.pdf')) {
                $timetables['timetables'][] = array(
                    "name"      => (String)     $timetable->getName(),
                    "url"       => (String)     $timetable->getUrl(),
                );
            }
        }             

        // ----
        $json = [];
        $json['line'] = $route->getRouteAndTrafic();

        // ----
        $stops = Functions::getStopsOfRoutes($db, $id);

        $json['line']['stops'] = [];

        foreach($stops as $stop) {
            $json['line']['stops'][] = array(
                'id'        =>              $stop['stop_id'],
                'name'      =>  (string)    $stop['stop_name'],
                'type'      =>  (string)    'stop_area',
                'distance'  =>  (float)     0,
                'town'      =>  (string)    isset($stop['town_name']) ? $stop['town_name'] : '',
                'zip_code'  =>  (string)    isset($stop['zip_code'])  ? $stop['zip_code']  : '',
                'coord'     => array(
                    'lat'       =>      (float) $stop['stop_lat'],
                    'lon'       =>      (float) $stop['stop_lon'],
                ),
            );
        }

        // ---        
        $json['line']['terminus'] = $terminus;
        $json['line']['timetables'] = $timetables;

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }

    /**
     * Get routes schedules
     * 
     * Get routes schedules
     */
    #[Route('/lines/{line_id}/schedules/{stop_id}', name: 'get_line_schedules', methods: ['GET'])]
    #[OA\Tag(name: 'Lines')]
    #[OA\Parameter(
        name:"line_id",
        in:"path",
        description:"Line Id",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"stop_id",
        in:"path",
        description:"Stop Id",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"date",
        in:"query",
        description:"Date",
        schema: new OA\Schema(type: 'string')
    )]

    #[OA\Response(
        response: 200,
        description: ''
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function getLineSchedules($line_id, $stop_id, Request $request): JsonResponse 
    {
        $db = $this->entityManager->getConnection();

        //--- On regarde si la requette est cohérente
        $routes = $this->stopRouteRepository->findOneBy( ['stop_id' => $stop_id, 'route_id' => $line_id] );
        if ( $routes == null ) {
            return new JsonResponse(Functions::ErrorMessage(400, 'Nothing where found for this route and stop'), 400);
        }
        
        $objs = Functions::getSchedulesByStop($db, $stop_id, $line_id, date("Y-m-d"), "00:00:00");

        $json = [];
        $json['line'] = $routes->getRouteId()->getRoute();
        $json['line']['schedules'] = [];
        $schedules = [];

        foreach($objs as $obj) {
            if ( !isset( $schedules[$obj['direction_id']] ) ) {
                $schedules[$obj['direction_id']] = [];
            }

            $schedules[$obj['direction_id']][] = array(
                "departure_date_time" => (string) Functions::prepareTime($obj['departure_time'], true),
                "direction"           => (string) Functions::gareFormat($obj['trip_headsign']),
            );
        }

        foreach($schedules as $key => $s) {
            $json['line']['schedules'][] = $schedules[$key];
        }

        return new JsonResponse($json);
    }
}
