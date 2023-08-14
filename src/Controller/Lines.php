<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Controller\Functions;
use App\Repository\RoutesRepository;
use App\Repository\AgencyRepository;
use App\Repository\StopsRepository;
use App\Repository\TraficRepository;
use OpenApi\Attributes as OA;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Lines
{
    private \Doctrine\ORM\EntityManagerInterface $entityManager;
    private RoutesRepository $routesRepository;
    private AgencyRepository $agencyRepository;
    private StopsRepository $stopsRepository;
    private TraficRepository $traficRepository;
    private ParameterBagInterface $params;
    
    public function __construct(EntityManagerInterface $entityManager, TraficRepository $traficRepository, RoutesRepository $routesRepository, AgencyRepository $agencyRepository, StopsRepository $stopsRepository, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->routesRepository = $routesRepository;
        $this->agencyRepository = $agencyRepository;
        $this->stopsRepository = $stopsRepository;
        $this->traficRepository = $traficRepository;
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
        $q   = $request->get('q');
  
        if (isset($q)) {
            $query = $q;
            $query = urldecode(trim($query));
            
        } else {
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
        $id;

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
        foreach( $_timetables as $timetables) {
            if ( $timetables->getType() == 'map') {
                $timetables['map'][] = array(
                    "name"      => (String)     $timetables->getName(),
                    "url"       => (String)     $timetables->getUrl(),
                );
            }
            if ( $timetables->getType() == 'timetables' && str_ends_with($timetables->getUrl(), '.pdf')) {
                $timetables['timetables'][] = array(
                    "name"      => (String)     $timetables->getName(),
                    "url"       => (String)     $timetables->getUrl(),
                );
            }
        }        

        // ----
        $json = [];
        $json['line'] = $route->getRoute();

        // ---
        $json['line']['reports'] = [];
        $json['line']['reports']['future_work'] = [];
        $json['line']['reports']['current_work'] = [];
        $json['line']['reports']['current_trafic'] = [];
        $json['line']['severity'] = 0;

        $reports = $this->traficRepository->findAll();
        foreach ($reports as $report) {
            $route_id = $report->getRouteId()->getRouteId();
            if ($route_id == $id){
                if (!isset($trafic)) {
                    $trafic = array(
                        "id"         =>  (string)    $route_id,
                        "code"       =>  (string)    $report->getRouteId()->getRouteShortName(),
                        "name"       =>  (string)    $report->getRouteId()->getRouteLongName(),
                        "mode"       =>  (string)    $report->getRouteId()->getRouteType(),
                        "color"      =>  (string)    $report->getRouteId()->getRouteColor(),
                        "text_color" =>  (string)    $report->getRouteId()->getRouteTextColor(),
                        "severity"   =>  (int)       1,
                        "reports"    =>  array(
                            "current_trafic"    => [], // $current_trafic,
                            "current_work"      => [], // $current_work,
                            "future_work"       => [], // $future_work
                        )
                    );
                }
                
                $r = array(
                    "id"            =>  (string)    $report->getId(),
                    "status"        =>  (string)    $report->getStatus(),
                    "cause"         =>  (string)    $report->getCause(),
                    "category"      =>  (string)    $report->getCategory(),
                    "severity"      =>  (int)       $report->getSeverity(),
                    "effect"        =>  (string)    $report->getEffect(),
                    "updated_at"    =>  (string)    $report->getUpdatedAt()->format("Y-m-d\TH:i:sP"),
                    "message"       =>  array(
                        "title"     =>      $report->getTitle(),
                        "text"      =>      $report->getText(),
                    ),
                );

                $severity = $json['line']['severity'] > $report->getSeverity() ? $json['line']['severity'] : $report->getSeverity();
                
                $json['line']['severity'] = $severity;
                
                if ($report->getCause() == 'future') {
                    $json['line']['reports']['future_work'][] = $r;
                } elseif ($report->getSeverity() == 2) {
                    $json['line']['reports']['future_work'][] = $r;
                } elseif ($report->getSeverity() == 3) {
                    $json['line']['reports']['current_work'][] = $r;
                } else {
                    $json['line']['reports']['current_trafic'][] = $r;
                }
            }
        }

        // ---        
        $json['line']['terminus'] = $terminus;
        $json['line']['timetables'] = $timetables;

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}
