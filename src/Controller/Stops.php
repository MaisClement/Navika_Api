<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\StopRouteRepository;
use App\Repository\TownRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Elastic\Elasticsearch\ClientBuilder;
use App\Service\Logger;

class Stops
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

    #[OA\Parameter(
        name: "allowed_modes[]",
        in: "query",
        description: "Array of allowed transportation modes",
        schema: new OA\Schema(
            type: "array",
            items: new OA\Items(type: "string")
        )
    )]
    #[OA\Parameter(
        name: "forbidden_modes[]",
        in: "query",
        description: "Array of forbidden transportation modes",
        schema: new OA\Schema(
            type: "array",
            items: new OA\Items(type: "string")
        )
    )]

    #[OA\Parameter(
        name: "allowed_ids[]",
        in: "query",
        description: "An array of allowed stops",
        schema: new OA\Schema(
            type: "array",
            items: new OA\Items(type: "string")
        )
    )]
    #[OA\Parameter(
        name: "forbidden_ids[]",
        in: "query",
        description: "An array of forbidden stops",
        schema: new OA\Schema(
            type: "array",
            items: new OA\Items(type: "string")
        )
    )]

    #[OA\Parameter(
        name: "allowed_lines[]",
        in: "query",
        description: "An array of allowed lines",
        schema: new OA\Schema(
            type: "array",
            items: new OA\Items(type: "string")
        )
    )]
    #[OA\Parameter(
        name: "forbidden_lines[]",
        in: "query",
        description: "An array of forbidden lines",
        schema: new OA\Schema(
            type: "array",
            items: new OA\Items(type: "string")
        )
    )]

    #[OA\Response(
        response: 200,
        description: 'OK'
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function searchStops(Request $request): JsonResponse
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
        ;

        $q = $request->get('q');
        $q = urldecode(trim($q));
        $query = str_replace($search, $replace, $q);

        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if ((is_string($request->get('q')) && $query != "")) {
            if ($lat != null && $lon != null) {
                $search_type = 3;
            } else {
                $search_type = 1;
            }

            $stops = [];
            if (strlen($q) >= 3) {
                $stops = $this->stopRouteRepository->findByQueryName($query);
            }

            // $stops2 = $this->stopRouteRepository->findByTownName( $query );
            // $stops = array_merge($stops1, $stops2);

            try {
                $params = [
                    'index' => 'stops',
                    'size' => 50,
                    'body' => [
                        'query' => [
                            'fuzzy' => [
                                'name' => [
                                    'value' => $query,
                                    'fuzziness' => 'AUTO',
                                ],
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
                // Elastic not working
                $stops = $this->stopRouteRepository->findByQueryName($query);
            }

        } else if ($lat != null && $lon != null) {
            $search_type = 2;
            $stops = $this->stopRouteRepository->findByNearbyLocation($lat, $lon, 5000);

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

        // ------ Places
        //
        $places = [];
        $lines = [];
        $modes = [];

        foreach ($stops as $stop) {
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

                if ($filter) {
                    if (!isset($places[$stop->getStopId()->getStopId()])) {

                        $places[$stop->getStopId()->getStopId()] = $stop->getStop($lat, $lon);

                        $lines[$stop->getStopId()->getStopId()] = [];
                        $modes[$stop->getStopId()->getStopId()] = [];
                    }

                    if (!in_array($stop->getRouteId()->getRoute(), $lines[$stop->getStopId()->getStopId()])) {
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
            $echo = Functions::orderPlaces($echo);
        }

        array_splice($echo, 15);

        // foreach($echo as $key => $e) {
        //     if ($e['distance'] == 0) {
        //         $town = $this->townRepository->findTownByCoordinates($e['coord']['lon'], $e['coord']['lat']);
        //         if ($town != null) {
        //             $echo[$key]['town'] = $town->getTownName();
        //             $echo[$key]['zip_code'] = $town->getZipCode();
        //         }
        //     }
        // }

        $json = [];
        $json["places"] = $echo;

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}
