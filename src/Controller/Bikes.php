<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\StationsRepository;
use App\Repository\TownRepository;
use OpenApi\Attributes as OA;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Logger;

class Bikes
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private Logger $logger;
    private StationsRepository $stationsRepository;
    private TownRepository $townRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, Logger $logger, TownRepository $townRepository, StationsRepository $stationsRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->logger = $logger;

        $this->stationsRepository = $stationsRepository;
        $this->townRepository = $townRepository;
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
        name: "id",
        in: "path",
        description: "Longitude for location-based search",
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

    public function getBikes($id, Request $request)
    {
        // --- On recupere les infos de la base de données
        if (!($station = $this->stationsRepository->findOneBy(['station_id' => $id])) instanceof \App\Entity\Stations) {

            $this->logger->logHttpErrorMessage($request, "Nothing where found for this station", 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this station'), 400);
        }

        $lat = $station->getStationLat();
        $lon = $station->getStationLon();

        // --- Adresse, ville et position
        $town = $this->townRepository->findTownByCoordinates($lat, $lon);

        $json = array(
            "bike_station" => array(
                "id" => (string) $station->getStationId(),
                "name" => (string) $station->getStationName(),
                "type" => (string) 'bike_station',
                'distance' => (int) 0,
                'town' => (string) isset($town) ? $town->getTownName() : '',
                'zip_code' => (string) isset($town) ? $town->getZipCode() : '',
                "coord" => array(
                    'lat' => (double) $station->getStationLat(),
                    'lon' => (double) $station->getStationLon(),
                ),
            ),
            'capacity' => array(
                'total' => (int) $station->getStationCapacity()
            ),
        );

        // --- Infos en temps réel
        $url = $station->getProviderId()->getGbfsUrl() . 'station_status.json';

        $this->logger->log(['message' => "GFBS query: $url"], 'INFO');

        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        $sid = substr($id, strpos($id, ':') + 1);

        if ($status == 200) {
            $content = $response->getContent();
            $results = json_decode($content);
            foreach ($results->data->stations as $station) {
                if ($station->station_id == $sid) {
                    if (isset($station->num_bikes_available_types)) {
                        foreach ($station->num_bikes_available_types as $types) {
                            foreach ($types as $key => $nb) {
                                $json['capacity'][$key] = $nb;
                            }
                        }
                    } elseif (isset($station->num_bikes_available)) {
                        $json['capacity']['bike'] = $station->num_bikes_available;
                    }
                    break;
                }
            }
        } else {
            $this->logger->logHttpErrorMessage($request, "GFBS query: Unable to fetch data. HTTP error code $status", 'ERROR');
        }

        return new JsonResponse($json);
    }
}
