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
use Elastic\Elasticsearch\ClientBuilder;
use App\Service\Logger;

class Places
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
     * Get places
     * 
     * Places is provided by Pelias and OSM data. It includes stops, POIs and address.
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
        name: "q",
        in: "query",
        description: "Query (Stop name or town)",
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: "lat",
        in: "query",
        description: "Latitude for location-based search",
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: "lon",
        in: "query",
        description: "Longitude for location-based search",
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
        try {
            $client = ClientBuilder::create()
                ->setHosts($this->params->get('elastic_hosts'))
                ->setBasicAuthentication($this->params->get('elastic_user'), $this->params->get('elastic_pswd'))
                ->setCABundle($this->params->get('elastic_cert'))
                ->build();
        } catch (\Exception $e) {
            // Elastic not working
            // Pas content
        }

        $search = ['-', ' ', "'"];
        $replace = ['', '', ''];

        $q = $request->get('q');
        $q = urldecode(trim($q));
        $query = str_replace($search, $replace, $q);

        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if ((is_string($request->get('q')) && $query != "")) {
            if ($lat != null && $lon != null) {
                $search_type = 3;
                $url = $this->params->get('geosearch_url') . 'autocomplete?lang=fr&text=' . $q . '&focus.point.lon=' . $lon . '&focus.point.lat=' . $lat;
            } else {
                $search_type = 1;
                $url = $this->params->get('geosearch_url') . 'autocomplete?lang=fr&text=' . $q;

            }

            $stops1 = [];
            if (strlen($q) >= 3) {
                $stops1 = $this->stopRouteRepository->findByQueryName($query);
            }

            $stops2 = $this->stopRouteRepository->findByTownName( $query );
            $stops = array_merge($stops1, $stops2);

            try {
                $params = [
                    'index' => 'stops',
                    'size' => 500,
                    'body' => [
                        'query' => [
                            "match" => [
                                "name" => $q
                            ],
                        ],
                    ],
                ];
                $results = $client->search($params);

                foreach ($results['hits']['hits'] as $result) {
                    $s = $this->stopRouteRepository->findById($result['_id']);
                    $stops = array_merge($stops, $s);
                }
            } catch (\Exception $e) {
                $this->logger->error($e);
                $stops = $this->stopRouteRepository->findByQueryName($query);
            }

        } else if ($lat != null && $lon != null) {
            $search_type = 2;
            $stops = $this->stopRouteRepository->findByNearbyLocation($lat, $lon, 5000);
            $url = $this->params->get('geosearch_url') . 'reverse?lang=fr&point.lat=' . $lat . '&point.lon=' . $lon;

        } else if (is_string($request->get('q'))) {
            $json["places"] = [];
            if ($request->get('flag') != null) {
                $json["flag"] = (int) $request->get('flag');
            }
            return new JsonResponse($json);

        } else {
            $this->logger->logHttpErrorMessage($request, 'At least one required parameter is missing or null, have you "q" or "lat" and "lon" ?', 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'At least one required parameter is missing or null, have you "q" or "lat" and "lon" ?'), 400);
        }

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

        $places = [];
        foreach ($results as $result) {
            if (!(isset($result->properties->addendum->osm->operator) && ($result->properties->addendum->osm->operator == "SNCF" || $result->properties->addendum->osm->operator == 'RATP' || $result->properties->addendum->osm->operator == 'RATP/SNCF'))) {
                $places[] = array(
                    "id" => (string) $result->geometry->coordinates[0] . ';' . $result->geometry->coordinates[1],
                    "name" => (string) $result->properties->name,
                    "type" => (string) Functions::getTypeFromPelias($result->properties->layer),
                    "distance" => (float) (isset($result->distance) ? $result->distance : 0),
                    "town" => (string) (isset($result->properties->locality) ? $result->properties->locality : ''),
                    "zip_code" => (string) (isset($result->properties->postalcode) ? $result->properties->postalcode : ''),
                    "department" => (string) (isset($result->properties->region) ? $result->properties->region : ''),
                    "region" => (string) (isset($result->properties->macroregion) ? $result->properties->macroregion : ''),
                    "coord" => array(
                        "lat" => (float) $result->geometry->coordinates[1],
                        "lon" => (float) $result->geometry->coordinates[0],
                    ),
                    "lines" => [],
                    "modes" => [],
                );
            }
        }

        // ------------
        // STOPS

        $stop_places = [];
        $lines[] = [];
        $lines_id = [];
        $modes[] = [];

        foreach ($stops as $stop) {
            try {
                if (!isset($stop_places[$stop->getStopId()->getStopId()])) {

                    $stop_places[$stop->getStopId()->getStopId()] = $stop->getStop($lat, $lon);

                    $lines[$stop->getStopId()->getStopId()] = [];
                    $modes[$stop->getStopId()->getStopId()] = [];
                }

                if (!in_array($stop->getRouteId()->getRouteId(), $lines_id)) {
                    $lines[$stop->getStopId()->getStopId()][] = $stop->getRouteId()->getRoute();
                    $lines_id[] = $stop->getRouteId()->getRouteId();
                }

                if (!in_array($stop->getRouteId()->getTransportMode(), $modes[$stop->getStopId()->getStopId()])) {
                    $modes[$stop->getStopId()->getStopId()][] = $stop->getRouteId()->getTransportMode();
                }
            } catch (\Exception $e) {
                $this->logger->error($e, 'WARN');
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

        // array_splice($stop_echo, 15);

        // foreach($echo as $key => $e) {
        //     if ($e['distance'] == 0) {
        //         $town = $this->townRepository->findTownByCoordinates($e['coord']['lon'], $e['coord']['lat']);
        //         if ($town != null) {
        //             $echo[$key]['town'] = $town->getTownName();
        //             $echo[$key]['zip_code'] = $town->getZipCode();
        //         }
        //     }
        // }

        $places = array_merge($stop_echo, $places);
        $json["places"] = Functions::orderWithLevenshtein($places, $q);

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}
