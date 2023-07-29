<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\StationsRepository;
use App\Repository\StopRouteRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Near
{
    private StopRouteRepository $stopRouteRepository;
    private StationsRepository $stationsRepository;
    
    public function __construct(StopRouteRepository $stopRouteRepository, StationsRepository $stationsRepository)
    {
        $this->stopRouteRepository = $stopRouteRepository;
        $this->stationsRepository = $stationsRepository;
    }
 
    /**
     * Get near objects
     * 
     * Get nears objects depending on localisation, including stops and bike stations. 
     * 
     * **"lat" "lon" and "z" must be defined.**
     * 
     * 
     * Z is the zoom (the visible distance around the point). Depending on the distance, elements may appear (bike stations, bus stops, etc.).
     */
    #[Route('/near', name: 'get_near', methods: ['GET'])]
    #[OA\Tag(name: 'Near')]
    #[OA\Parameter(
        name:"z",
        in:"query",
        description:"Zoom",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"lat",
        in:"query",
        description:"Latitude for location-based search",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"lon",
        in:"query",
        description:"Longitude for location-based search",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]

    #[OA\Response(
        response: 200,
        description: 'Return near objects'
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function getNear(Request $request)
    {
        $zoom = $request->get('z');
        $lat = $request->get('lat');    
        $lon = $request->get('lon');
  
        $stops = $this->stopRouteRepository->findByNearbyLocation($lat, $lon, $zoom);

        $json = [];        
        $json["stops"] = [];
        $json["bike"] = [];
        
        // ------ Places
        //
        $places = [];
        foreach($stops as $stop) {
            if (($zoom >= 15000 && ($stop->getTransportMode() == 'rail' || $stop->getTransportMode() == 'nationalrail')) || $zoom < 15000) {
            
                if (!isset($places[$stop->getStopId()->getStopId()])) {

                    $places[$stop->getStopId()->getStopId()] = array(
                        'id'        =>              $stop->getStopId()->getStopId(),
                        'name'      =>  (string)    $stop->getStopName(),
                        'type'      =>  (string)    'stop_area',
                        'distance'  =>  (int)       0,
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

                if (!in_array(Functions::getTransportMode($stop->getRouteType()), $lines[$stop->getStopId()->getStopId()])) {
                    $lines[$stop->getStopId()->getStopId()][] = array(
                        "id"         =>  (string)    $stop->getRouteId()->getRouteId(),
                        "code"       =>  (string)    $stop->getRouteShortName(),
                        "name"       =>  (string)    $stop->getRouteLongName(),
                        "mode"       =>  (string)    Functions::getTransportMode($stop->getRouteType()),
                        "color"      =>  (string)    strlen($stop->getRouteColor()) < 6 ? "ffffff" : $stop->getRouteColor(),
                        "text_color" =>  (string)    strlen($stop->getRouteTextColor()) < 6 ? "000000" : $stop->getRouteTextColor(),
                    );
                }

                if (!in_array(Functions::getTransportMode($stop->getRouteType()), $modes[$stop->getStopId()->getStopId()])) {
                    $modes[$stop->getStopId()->getStopId()][] = Functions::getTransportMode($stop->getRouteType());
                }
            }
        }

        foreach ($places as $key => $place) {
            $lines[$key] = Functions::order_line($lines[$key]);
            $place['lines'] = $lines[$key];
            $place['modes'] = $modes[$key];
            $json["stops"][] = $place;
        }

        // --- Velo

        if ($zoom <= 3000) {
            $bikes = $this->stationsRepository->findByNearbyLocation($lat, $lon, $zoom);
            
            foreach($bikes as $bike) {
                $json["bike"][] = array(
                    'id'        =>  (string)    $bike->getStationId(),
                    'name'      =>  (string)    $bike->getStationName(),
                    'capacity'  =>  (int)       $bike->getStationCapacity(),
                    'coord'     => array(
                        'lat'       => floatval( $bike->getStationLat() ),
                        'lon'       => floatval( $bike->getStationLon() ),
                    ),
                );
            }
        }

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}
