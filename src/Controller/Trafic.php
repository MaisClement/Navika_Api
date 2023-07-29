<?php

namespace App\Controller;

use App\Repository\TraficRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Trafic
{
    private TraficRepository $traficRepository;
    
    public function __construct(TraficRepository $traficRepository, ParameterBagInterface $params)
    {        
        $this->params = $params;
        $this->traficRepository = $traficRepository;
    }
 
    /**
     * Get trafic
     * 
     * Get trafic reports
     * 
     * You can defined wich lines do you want by using "lines[]" parameter. If not provided, default lines will be returned
     */
    
    #[Route('/trafic', name: 'get_trafic', methods: ['GET'])]
    #[OA\Tag(name: 'Trafic')]
    #[OA\Parameter(
        name:"lines[]",
        in:"query",
        description:"An array of line_id",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "String")
        )
    )]

    #[OA\Response(
        response: 200,
        description: ''
    )]

    public function getTrafic(Request $request): JsonResponse 
    {
        if ($request->get('lines') != null) {
            $lines = $request->get('lines');
        } else {
            $lines = $this->params->get('lines');
        }
  
        $reports = $this->traficRepository->findAll();

        // ---

        $trafic = [];

        foreach ($reports as $report) {

            $route_id = $report->getRouteId()->getRouteId();

            if (in_array($route_id, $lines)){
                if (!isset($trafic[$route_id])) {
                    $trafic[$route_id] = $report->getRouteId()->getRoute();

                    $trafic[$route_id]['severity'] = 0;
                    $trafic[$route_id]['reports']['future_work'] = [];
                    $trafic[$route_id]['reports']['current_work'] = [];
                    $trafic[$route_id]['reports']['current_trafic'] = [];
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

                $severity = $trafic[$route_id]['severity'] > $report->getSeverity() ? $trafic[$route_id]['severity'] : $report->getSeverity();
                
                $trafic[$route_id]['severity'] = $severity;
                
                if ( $report->getCause() == 'future' ) {
                    $trafic[$route_id]['reports']['future_work'][] = $r;

                } else if ( $report->getSeverity() == 2 ) {
                    $trafic[$route_id]['reports']['future_work'][] = $r;

                } else if ( $report->getSeverity() == 3 ) {
                    $trafic[$route_id]['reports']['current_work'][] = $r;
                    
                } else {
                    $trafic[$route_id]['reports']['current_trafic'][] = $r;
                }
            }
        }

        $json = [];
        $json['trafic'] = [];

        foreach ($trafic as $el) {
            $json['trafic'][] = $el;
        }
        
        return new JsonResponse($json);
    }
}
