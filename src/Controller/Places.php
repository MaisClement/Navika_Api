<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\StopRouteRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Places
{
    private StopRouteRepository $stopRouteRepository;
    private $entityManager;
    private $params;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, StopRouteRepository $stopRouteRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
    
        $this->stopRouteRepository = $stopRouteRepository;
    }
    
    /**
     * Get places
     * 
     * Places is provided by PRIM API. It includes stops, POIs and address.
     * 
     * **At least "q" or "lat" and "lon" must be defined.**
     * 
     * 
     * Unlike `/stops`, results can't be filtered
     * 
     * 
     * For better performance, use `/stops`
     */
    #[Route('/places', name: 'search_places', methods: ['GET'])]
    #[OA\Tag(name: 'Places')]
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

    #[OA\Response(
        response: 200,
        description: 'OK'
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function searchPlaces(Request $request)
    {
        $search = ['-', ' ', "'"];
        $replace =['', '', ''];

        $q = $request->get('q');
        $q = urldecode( trim( $q ) );
        $query = str_replace($search, $replace, $q);

        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if ( ( is_string($request->get('q')) && $q != "" ) && $lat != null && $lon != null ) {
            $search_type = 3;
            $stops1 = $this->stopRouteRepository->findByQueryName( $query );
            $stops2 = $this->stopRouteRepository->findByTownName( $query );
            $stops = array_merge($stops1, $stops2);
            $url = $this->params->get('geosearch_url') . 'autocomplete?text=' . $q . '&focus.point.lon=' . $lon . '&focus.point.lat=' . $lat;
        
        } else if ( $lat != null && $lon != null ) {
            $search_type = 2;
            $stops = $this->stopRouteRepository->findByNearbyLocation($lat, $lon, 5000);
            $url = $this->params->get('geosearch_url') . 'reverse?point.lat=' . $lat . '&point.lon=' . $lon;
        
        } else if ( is_string($request->get('q')) && $q != "" ) {
            $search_type = 1;
            $stops1 = $this->stopRouteRepository->findByQueryName( $query );
            $stops2 = $this->stopRouteRepository->findByTownName( $query );
            $stops = array_merge($stops1, $stops2);
            $url = $this->params->get('geosearch_url') . 'autocomplete?text=' . $q;
        
        } else if ( is_string($request->get('q')) ) {
            $json["places"] = [];
            if ($request->get('flag') != null) {
                $json["flag"] = (int) $request->get('flag');
            }
            return new JsonResponse($json);
        
        } else {
            return new JsonResponse(Functions::ErrorMessage(400, 'One or more parameters are missing or null, have you "q" or "lat" and "lon" ?'), 400);
        }

        // ------------
        // GEOSEARCH
        $client = HttpClient::create();        
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200){
            return new JsonResponse(Functions::ErrorMessage(500, 'Can\'t get data from GeoSearch'), 500);
        }

        $content = $response->getContent();
        $results = json_decode($content);

        $results = $results->features;

        $places = [];
        foreach ($results as $result) {
            // if ( !(isset($result->properties->addendum->osm->operator))  ) {
            if ( !(isset($result->properties->addendum->osm->operator) && ($result->properties->addendum->osm->operator == "SNCF" || $result->properties->addendum->osm->operator == 'RATP' || $result->properties->addendum->osm->operator == 'RATP/SNCF'))  ) {
                $places[] = array(
                    "id"         =>  (string)    $result->geometry->coordinates[0] . ';' . $result->geometry->coordinates[1],
                    "name"       =>  (string)    $result->properties->name,
                    "type"       =>  (string)    Functions::getTypeFromPelias($result->properties->layer),
                    "distance"   =>  (float)     (isset($result->distance) ? $result->distance : 0),
                    "town"       =>  (string)    (isset($result->properties->locality) ? $result->properties->locality : ''),
                    "zip_code"   =>  (string)    (isset($result->properties->postalcode) ? $result->properties->postalcode : ''),
                    "department" =>  (string)    (isset($result->properties->region) ? $result->properties->region : ''),
                    "region"     =>  (string)    (isset($result->properties->macroregion) ? $result->properties->macroregion : ''),
                    "coord"      => array(
                        "lat"       =>  (float) $result->geometry->coordinates[0],
                        "lon"       =>  (float) $result->geometry->coordinates[1],
                    ),
                    "lines"     =>              [],
                    "modes"     =>              [],
                );
            }
            
        } 

        // ------------
        // STOPS

        $stop_places = [];
        $lines[] = [];
        $modes[] = [];

        foreach($stops as $stop) {
            try {
                if (!isset($stop_places[$stop->getStopId()->getStopId()])) {

                    $stop_places[$stop->getStopId()->getStopId()] = $stop->getStop($lat, $lon, true);
                    
                    $lines[$stop->getStopId()->getStopId()] = [];
                    $modes[$stop->getStopId()->getStopId()] = [];
                }

                if (!in_array($stop->getRouteId()->getTransportMode(), $lines[$stop->getStopId()->getStopId()])) {
                    $lines[$stop->getStopId()->getStopId()][] = $stop->getRouteId()->getRoute();
                }

                if (!in_array($stop->getRouteId()->getTransportMode(), $modes[$stop->getStopId()->getStopId()])) {
                    $modes[$stop->getStopId()->getStopId()][] = $stop->getRouteId()->getTransportMode();
                }
            } catch (\Exception $e) {
                // Pas content
            }
        }

        $stop_echo = [];
        foreach ($stop_places as $key => $place) {
            $lines[$key] = Functions::order_line($lines[$key]);
            $place['lines'] = $lines[$key];
            $place['modes'] = $modes[$key];
            $stop_echo[] = $place;
        }

        if ($search_type == 2) {
            $stop_echo = Functions::orderByDistance($stop_echo, $lat, $lon);
        } else {
            $stop_echo = Functions::orderPlaces($stop_echo);
        }

        array_splice($stop_echo, 15);

        $places = array_merge($stop_echo, $places);
        $json["places"] = Functions::orderWithLevenshtein($places, $q);

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}
