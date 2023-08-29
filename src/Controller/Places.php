<?php

namespace App\Controller;

use App\Controller\Functions;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Places
{
    private \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
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
        description: ''
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function searchPlaces(Request $request)
    {
        $search = ['-', ' ', "'"];
        $replace =['', '', ''];;
  
        $q = $request->get('q');
        $q = urldecode( trim( $q ) );
        $query = str_replace($search, $replace, $q);

        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if ( is_string($query) && $lat != null && $lon != null ) {
            $query = $q;
            $query = urldecode(trim($query));
            $url = $this->params->get('prim_url') . '/places?q=' . $query . '&from' . $lon . ';' . $lat . '=&depth=2';
            $search_type = 3;
        } else if ( $lat != null && $lon != null ) {
            $url = $this->params->get('prim_url') . '/coord/' . $lon . ';' . $lat . '/places_nearby?depth=2&distance=1000';
            $search_type = 2;
        } else if ( is_string($query) ) {
            $query = $q;
            $query = urldecode(trim($query));
            $url = $this->params->get('prim_url') . '/places?q=' . $query . '&depth=2';
            $search_type = 1;
        } else {
            return new JsonResponse(Functions::ErrorMessage(400, 'One or more parameters are missing or null, have you "q" or "lat" and "lon" ?'), 400);
        }

        // ------------

        if ($search_type == 1 && $query == "") {
            $places = [];
        } else {
            $client = HttpClient::create();        
            $response = $client->request('GET', $url, [
                'headers' => [
                    'apiKey' => $this->params->get('prim_api_key'),
                ],
            ]);
            $status = $response->getStatusCode();

            print_r($status);

            if ($status != 200){
                return new JsonResponse(Functions::ErrorMessage(500, 'Cannot get data from provider'), 500);
            }

            $content = $response->getContent();
            $results = json_decode($content);

            if ($search_type == 3) {
                $results = $results->places;
            } elseif ($search_type == 2) {
                $results = $results->places_nearby;
            } elseif ($search_type === 1) {
                $results = $results->places;
            } else {
                return new JsonResponse(Functions::ErrorMessage(500), 500);
            }

            $places = [];
            foreach ($results as $result) {
                $places[] = array(
                    "id"        =>  (string)    $result->id,
                    "name"      =>  (string)    $result->{$result->embedded_type}->name,
                    "type"      =>  (string)    $result->embedded_type,
                    "distance"  =>              (float) (isset($result->distance) ? $result->distance : 0),
                    "town"      =>  (string)    Functions::getTownByAdministrativeRegions($result->{$result->embedded_type}->administrative_regions),
                    "zip_code"  =>  (string)    Functions::getZipByAdministrativeRegions($result->{$result->embedded_type}->administrative_regions),
                    "coord"     => array(
                        "lat"       =>  (float) $result->{$result->embedded_type}->coord->lat,
                        "lon"       =>  (float) $result->{$result->embedded_type}->coord->lon,
                    ),
                    "lines"     =>              isset($result->{$result->embedded_type}->lines) ? Functions::getAllLines($result->{$result->embedded_type}->lines) : [],
                    "modes"     =>              isset($result->stop_area->physical_modes) ? Functions::getPhysicalModes($result->stop_area->physical_modes) : [],
                );
            }        
        }

        $json["places"] = $places;
        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}
