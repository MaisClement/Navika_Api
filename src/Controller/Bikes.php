<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\StationsRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class Bikes
{
    private StationsRepository $stationsRepository;
    
    public function __construct(StationsRepository $stationsRepository)
    {
        $this->stationsRepository = $stationsRepository;
    }

    /**
     * Get bikes available in a station
     * 
     * Get bikes available in a station. 
     * 
     * **"id" must be defined.**
     * 
     * 
     * Station id can be get using `/near`
     */
    #[Route('/bikes/{id}', name: 'get_bikes', methods: ['GET'])]
    #[OA\Tag(name: 'Bikes')]
    #[OA\Parameter(
        name:"id",
        in:"path",
        description:"Longitude for location-based search",
        required: true,
        schema: new OA\Schema(type: 'string', default: 'VELIB:85123644')
    )]

    #[OA\Response(
        response: 200,
        description: 'Return available bikes'
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function getBikes($id)
    {
        //--- On recupere les infos de la base de données
        if (!$station = $this->stationsRepository->findOneBy( ['station_id' => $id] )) {
            return new JsonResponse(Functions::ErrorMessage(400, 'Nothing where found for this station'), 400);
        }
        
        $json = array(
            'name'     => $station->getStationName(),
            'url'      => $station->getProviderId()->getUrl(),
            'coord' => array(
                'lat'      => $station->getStationLat(),
                'lon'      => $station->getStationLon(),
            ),
            'capacity' => (int) $station->getStationCapacity(),
        );

        $url = $station->getProviderId()->getUrl() . 'station_status.json';

        //--- Infos en temps réel
        $client = HttpClient::create();
        
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status == 200){
            $content = $response->getContent();
            $results = json_decode($content);

            $sid = substr($id, strpos($id, ':') + 1);

            foreach ($results->data->stations as $station) {
                if ($station->station_id == $sid) {
            
                    if (isset($station->num_bikes_available_types)) {
                        foreach ($station->num_bikes_available_types as $types) {
                            foreach ($types as $key => $nb) {
                                $json[$key] = $nb;
                            }
                        }
                    } else if (isset($station->num_bikes_available)) {
                        $json['bike'] = $station->num_bikes_available;
                    }
            
                    break;
                }
            }
        }

        return new JsonResponse($json);
    }
}
