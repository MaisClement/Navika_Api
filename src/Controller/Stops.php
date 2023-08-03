<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\StopRouteRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Stops
{
    private StopRouteRepository $stopRouteRepository;
    
    public function __construct(StopRouteRepository $stopRouteRepository)
    {
        $this->stopRouteRepository = $stopRouteRepository;
    }
    
    /**
     * Get stops
     * 
     * Get stops based on query parameters. 
     * 
     * **At least "q" or "lat" and "lon" must be defined.**
     * 
     * 
     * Result can be filtered using more filter parameters like "allowed_modes[]" or "forbidden_lines[]"
     * 
     * 
     * If you want results include POIs and address, use `/places`
     */
    #[Route('/stops', name: 'search_stops', methods: ['GET'])]
    #[OA\Tag(name: 'Stops')]
    #[OA\Parameter(
        name:"q",
        in:"query",
        description:"Query (Stop name or town)",
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"lat",
        in:"query",
        description:"Latitude for location-based search",
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"lon",
        in:"query",
        description:"Longitude for location-based search",
        schema: new OA\Schema(type: 'string')
    )]


    #[OA\Parameter(
        name:"allowed_modes[]",
        in:"query",
        description:"Array of allowed transportation modes",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "String")
        )
    )]
    #[OA\Parameter(
        name:"forbidden_modes[]",
        in:"query",
        description:"Array of forbidden transportation modes",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "String")
        )
    )]

    #[OA\Parameter(
        name:"allowed_ids[]",
        in:"query",
        description:"An array of allowed stops",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "String")
        )
    )]
    #[OA\Parameter(
        name:"forbidden_ids[]",
        in:"query",
        description:"An array of forbidden stops",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "String")
        )
    )]

    #[OA\Parameter(
        name:"allowed_lines[]",
        in:"query",
        description:"An array of allowed lines",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "String")
        )
    )]
    #[OA\Parameter(
        name:"forbidden_lines[]",
        in:"query",
        description:"An array of forbidden lines",
        schema: new OA\Schema( 
            type: "array", 
            items: new OA\Items(type: "String")
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

    public function searchStops(Request $request): JsonResponse 
    {
        $search = ['-', ' ', "'"];
        $replace =['', '', ''];;
  
        $q = $request->get('q');
        $q = urldecode( trim( $q ) );
        $q = str_replace($search, $replace, $q);

        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if (isset($q) && isset($lat) && isset($lon)) {
            $query = $q;
                
            $search_type = 3;

        } else if (isset($lat) && isset($lon)) {
            $search_type = 2;

        } else if (isset($q)) {
            $query = $q;           

            $search_type = 1;
            
        } else {
            return new JsonResponse(Functions::ErrorMessage(400, 'One or more parameters are missing or null, have you "q" or "lat" and "lon" ?'), 400);
        }

        // ------ Request
        //
        if ($search_type == 3) {
            $stops1 = $this->stopRouteRepository->findByQueryName( $query );
            $stops2 = $this->stopRouteRepository->findByTownName( $query );

            $stops = array_merge($stops1, $stops2);

        } else if ($search_type == 2) {
            $stops = $this->stopRouteRepository->findByNearbyLocation($lat, $lon, 5000);

        } else if ($search_type == 1) {
            $stops1 = $this->stopRouteRepository->findByQueryName( $query );
            $stops2 = $this->stopRouteRepository->findByTownName( $query );

            $stops = array_merge($stops1, $stops2);

        } else {
            return new JsonResponse(Functions::ErrorMessage(500, 'Impossible value, something really strange happened, please report it.'), 500);
        }

        // ------ Places
        //
        $places = [];
        $lines[] = [];
        $modes[] = [];

        foreach($stops as $stop) {
            try {
                $filter = true;

                if ($request->get('allowed_modes')) {
                    $filter = in_array($stop->getRouteId()->getTransportMode(), $request->get('allowed_modes'));
                }
                if ($request->get('forbidden_modes')) {
                    $filter = !in_array($stop->getRouteId()->getTransportMode(), $request->get('forbidden_modes'));
                }
    
                if ($request->get('allowed_ids')) {
                    $filter = in_array($stop->getStopId()->getStopId(), $request->get('allowed_ids'));
                }
                if ($request->get('forbidden_ids')) {
                    $filter = !in_array($stop->getStopId()->getStopId(), $request->get('forbidden_ids'));
                }
    
                if ($request->get('allowed_lines')) {
                    $filter = in_array($stop->getRouteId()->getRouteId(), $request->get('allowed_lines'));
                }
                if ($request->get('forbidden_lines')) {
                    $filter = !in_array($stop->getRouteId()->getRouteId(), $request->get('forbidden_lines'));
                }
    
                // allowed_modes[]  -   forbidden_modes[]
                // allowed_ids[]    -   forbidden_ids[]
                // allowed_lines[]    -   forbidden_lines[]
    
                if ($filter) {
                    if (!isset($places[$stop->getStopId()->getStopId()])) {
    
                        $places[$stop->getStopId()->getStopId()] = array(
                            'id'        =>              $stop->getStopId()->getStopId(),
                            'name'      =>  (string)    $stop->getStopName(),
                            'type'      =>  (string)    'stop_area',
                            'distance'  =>  (int)       $search_type != 1 ? Functions::calculateDistance($stop->getStopLat(), $stop->getStopLon(), $lat, $lon) : 0,
                            'town'      =>  (string)    $stop->getTownName(),
                            'zip_code'  =>  (string)    '',
                            'coord'     => array(
                                'lat'       =>      floatval($stop->getStopLat()),
                                'lon'       =>      floatval($stop->getStopLon()),
                            ),
                            'lines'     => array(),
                            'modes'     => array(),
                        );
    
                        $lines[$stop->getStopId()->getStopId()] = [];
                        $modes[$stop->getStopId()->getStopId()] = [];
                    }
    
                    if (!in_array($stop->getRouteId()->getTransportMode(), $lines[$stop->getStopId()->getStopId()])) {
                        $lines[$stop->getStopId()->getStopId()][] = $stop->getRouteId()->getRoute();
                    }
    
                    if (!in_array($stop->getRouteId()->getTransportMode(), $modes[$stop->getStopId()->getStopId()])) {
                        $modes[$stop->getStopId()->getStopId()][] = $stop->getRouteId()->getTransportMode();
                    }
                }
            } catch (\Exception $e) {
                // Pas content
            }
        }

        $echo = [];
        foreach ($places as $key => $place) {
            $lines[$key] = Functions::order_line($lines[$key]);
            $place['lines'] = $lines[$key];
            $place['modes'] = $modes[$key];
            $echo[] = $place;
        }

        if ($search_type == 2) {
            $echo = Functions::orderByDistance($echo, $lat, $lon);
        } else {
            $echo = Functions::order_places($echo);
        }

        array_splice($echo, 15);

        $json = [];
        $json["places"] = $echo;

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}