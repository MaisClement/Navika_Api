<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\StopRouteRepository;
use App\Repository\TownRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Logger;

class Address
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private Logger $logger;
    private StopRouteRepository $stopRouteRepository;
    private TownRepository $townRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, Logger $logger, StopRouteRepository $stopRouteRepository, TownRepository $townRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->logger = $logger;

        $this->stopRouteRepository = $stopRouteRepository;
        $this->townRepository = $townRepository;
    }

    /**
     * Get address
     * 
     * Address is provided by Pelias and OSM data. It includes stops, POIs and address.
     * 
     * **At least "q" or "lat" and "lon" must be defined.**
     * 
     * 
     * Unlike `/stops`, results can't be filtered
     * 
     * 
     * For better performance, use `/stops`
     */
    #[Route('/address', name: 'search_address', methods: ['GET'])]
    #[OA\Tag(name: 'Address')]
    #[OA\Parameter(
        name: "lat",
        in: "query",
        description: "Latitude of point",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: "lon",
        in: "query",
        description: "Longitude for point",
        required: true,
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

    public function searchAddress(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if ($lat != null && $lon != null) {
            $url = $this->params->get('geosearch_url') . 'reverse?layers=address&lang=fr&point.lon=' . $lon . '&point.lat=' . $lat;

        } else {
            $this->logger->logHttpErrorMessage($request, 'At least one required parameter is missing or null, have you "lat" and "lon" ?', 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'At least one required parameter is missing or null, have you "lat" and "lon" ?'), 400);
        }

        // ------------
        // Places

        $stops = $this->stopRouteRepository->findByNearbyLocation($lat, $lon, 1000);

        $places = [];
        $lines = [];
        $modes = [];

        foreach ($stops as $stop) {
            if (!isset($places[$stop->getStopId()->getStopId()])) {

                $places[$stop->getStopId()->getStopId()] = $stop->getStop($lat, $lon);

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

        $echo = [];
        foreach ($places as $key => $place) {
            $lines[$key] = Functions::order_line($lines[$key]);
            $place['lines'] = $lines[$key];
            $place['modes'] = $modes[$key];
            $echo[] = $place;
        }

        $echo = Functions::orderByDistance($echo, $lat, $lon);
        array_splice($echo, 10);

        // ------------
        // GEOSEARCH

        $this->logger->log(['message' => "GeoSearch query: $url"], 'INFO');

        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200) {
            $this->logger->logHttpErrorMessage($request, "GeoSearch query: Unable to fetch data. HTTP error code $status", 'ERROR');
            return new JsonResponse(Functions::httpErrorMessage(500, "Failed to get data from GeoSearch"), 500);
        }

        $content = $response->getContent();
        $results = json_decode($content);

        $results = $results->features;

        $json = array(
            "place" => array(
                "id" => (string) $results[0]->geometry->coordinates[0] . ';' . $results[0]->geometry->coordinates[1],
                "name" => (string) $results[0]->properties->name,
                "type" => (string) Functions::getTypeFromPelias($results[0]->properties->layer),
                "distance" => (float) (isset($results[0]->distance) ? $results[0]->distance : 0),
                "town" => (string) (isset($results[0]->properties->locality) ? $results[0]->properties->locality : ''),
                "zip_code" => (string) (isset($results[0]->properties->postalcode) ? $results[0]->properties->postalcode : ''),
                "department" => (string) (isset($results[0]->properties->region) ? $results[0]->properties->region : ''),
                "region" => (string) (isset($results[0]->properties->macroregion) ? $results[0]->properties->macroregion : ''),
                "coord" => array(
                    "lat" => (float) $results[0]->geometry->coordinates[1],
                    "lon" => (float) $results[0]->geometry->coordinates[0],
                ),
            ),
            "near_stops" => $echo,
        );

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}