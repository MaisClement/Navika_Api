<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\StationsRepository;
use App\Repository\StopRouteRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

        $limit = 1000;
        
        $json = [];        
        $json["stops"] = [];
        $json["area"] = [];
        $json["bike"] = [];
        
        // ------ Places
        //
        
        if ($zoom > $limit) { // Large
            $stops = $this->stopRouteRepository->findByNearbyLocation($lat, $lon, $zoom);
        } else {
            $stops = $this->stopRouteRepository->findAllByNearbyLocation($lat, $lon, $zoom);
        }
    
        $places = [];
        $lines = [];
        $modes = [];

        foreach($stops as $stop) {
            // si le zoom > 500 OU 
            if ($zoom > $limit || $stop->getLocationType() == '0') {
                if (($zoom >= 15000 && ($stop->getTransportMode() == 'rail' || $stop->getTransportMode() == 'nationalrail')) || $zoom < 15000) {
                
                    if (!isset($places[$stop->getStopId()->getStopId()])) {
                        $places[$stop->getStopId()->getStopId()] = array(
                            'id'        =>              $stop->getStopId()->getStopId(),
                            'name'      =>  (string)    $stop->getStopName(),
                            'type'      =>  (string)    $stop->getLocationType() == 0 ? 'stop_point' : 'stop_area',
                            'distance'  =>  (int)       0,
                            'town'      =>  (string)    $stop->getTownName(),
                            'zip_code'  =>  (string)    '',
                            'coord'     => array(
                                'lat'       =>      (float) $stop->getStopLat(),
                                'lon'       =>      (float) $stop->getStopLon(),
                            ),
                            'location_type' => $stop->getLocationType(),
                            'parent' => $stop->getStopId()->getParentStation(),
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
        }

        if ($zoom > $limit) { // Large
            foreach ($places as $key => $place) {
                $lines[$key] = Functions::order_line($lines[$key]);
                $place['lines'] = $lines[$key];
                $place['modes'] = $modes[$key];
                $json["stops"][] = $place;
            }
        } else {
            $p = [];
            foreach ($places as $key => $place) {
                $lines[$key] = Functions::order_line($lines[$key]);
                $place['lines'] = $lines[$key];
                $place['modes'] = $modes[$key];
                $p[] = $place;
            }

            $area_place = [];
            foreach($p as $place) {
                if ( !isset( $area_place[$place['parent']] ) ){                    
                    foreach($stops as $stop) {
                        if ($stop->getStopId()->getStopId() == $place['parent']) {
                            $area_place[$place['parent']] = array(
                                'id'        =>              $stop->getStopId()->getStopId(),
                                'name'      =>  (string)    $stop->getStopName(),
                                'type'      =>  (string)    $stop->getLocationType() == 0 ? 'stop_point' : 'stop_area',
                                'distance'  =>  (int)       0,
                                'radius'    =>  (int)       0,
                                // 'coord'     => array(
                                //     'lat'       =>      (float) $stop->getStopLat(),
                                //     'lon'       =>      (float) $stop->getStopLon(),
                                // ),
                            );
                            break;
                        }
                    }
                }
                $area_place[$place['parent']]['stops'][] = $place;
            }

            foreach ($area_place as $key => $area) {
                $coord = Functions::getCentroidOfStops($area['stops']);
                $radius = 0;

                foreach($area['stops'] as $stop) {
                    $r = Functions::getDistanceBeetwenPoints($coord['lat'], $coord['lon'], $stop['coord']['lat'], $stop['coord']['lon']);
                    if ($r > $radius) {
                        $radius = $r;
                    }
                }

                $area['coord'] = $coord;
                $area['radius'] = ceil( $radius + ($radius * 0.2));
                $json["area"][] = $area;
            }
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
                        'lat'       => (float) $bike->getStationLat(),
                        'lon'       => (float) $bike->getStationLon(),
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
