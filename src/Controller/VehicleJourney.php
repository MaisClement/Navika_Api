<?php

namespace App\Controller;

use App\Repository\ProviderRepository;
use App\Repository\RoutesRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\Functions;
use OpenApi\Attributes as OA;
use App\Service\Logger;

class VehicleJourney
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private Logger $logger;

    private ProviderRepository $providerRepository;
    private RoutesRepository $routesRepository;

    public function __construct(EntityManagerInterface $entityManager, ProviderRepository $providerRepository, Logger $logger, RoutesRepository $routesRepository, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->logger = $logger;

        $this->providerRepository = $providerRepository;
        $this->routesRepository = $routesRepository;

    }

    /**
     * Get vehicle journey
     * 
     * Get vehicle journey
     */

    #[Route('/vehicle_journey/{id}', name: 'get_vehicle_journeys', methods: ['GET'])]
    #[OA\Tag(name: 'Vehicle')]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "vehicle journey id",
        schema: new OA\Schema(type: "string")
    )]

    #[OA\Response(
        response: 200,
        description: 'OK'
    )]

    public function getVehicleJourneys($id, Request $request)
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();

        if (!isset($id)) {
            $this->logger->logHttpErrorMessage($request, 'At least one required parameter is missing or null, have you "id" ?', 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'At least one required parameter is missing or null, have you "id" ?'), 400);
        }

        if (str_contains($id, 'IDFM:')) {
            $provider = 'IDFM';
        } else {
            $provider = 'ADMIN';
        }

        $json = [];

        // ------------
        // if ($provider == 'IDFM') {   
        //     $content = file_get_contents($dir . '/NAVIKA_idfm_departures.json');
        //     $content = json_decode($content, true);
        // 
        //     if (isset($content[$id])) {
        //         $vehicle_journey = $content[$id];


        //         // get route details
        //         $route_id = $vehicle_journey['informations']['line'];
        //         $route = $this->routesRepository->findOneBy( ['route_id' => $route_id] );
        //         if ( $route != null ) {
        //             $route = $route->getRoute(true);
        //         }
        //         $vehicle_journey['informations']['line'] = $route;
        //         $vehicle_journey["reports"] = [];
        //         
        //     } else {
        //         $provider = 'ADMIN';
        //     }
        // }
        // if ($provider == 'ADMIN') {
        $trip = Functions::getTripStopsById($db, $id, date("Y-m-d"));

        $len = count($trip);
        if ($len == 0) {
            $this->logger->logHttpErrorMessage($request, "Nothing where found for this id", 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this id'), 400);
        }
        $trip_update = [];
        $reports = [];

        $provider_id = $trip[0]['provider_id'];
        if ($provider_id != null) {
            $provider = $this->providerRepository->find($provider_id);

            $trips_update = Functions::getRealtimeData($provider);

            $file_name = $dir . '/test_gtfsrt.pb';
            file_put_contents($file_name, json_encode($trips_update, JSON_PRETTY_PRINT));

            $trip_update = Functions::getTripRealtime($trips_update, $trip[0]['trip_id'], null);
            $reports = Functions::getTripRealtimeReports($trip_update);
        }

        // -----

        $stops = [];
        $order = 0;

        foreach ($trip as $obj) {
            $disruption = Functions::getDisruptionForStop($trip_update, $obj);

            $stops[] = array(
                "name" => (string) $obj['stop_name'],
                "id" => (string) $obj['parent_station'],
                "order" => (int) $order,
                "type" => (int) $len - 1 === $order ? 'terminus' : ($order == 0 ? 'origin' : ''),
                "coords" => array(
                    "lat" => $obj['stop_lat'],
                    "lon" => $obj['stop_lon'],
                ),
                "stop_time" => array(
                    "departure_date_time" => Functions::prepareTime($obj['departure_time'], true),
                    "arrival_date_time" => Functions::prepareTime($obj['arrival_time'], true),
                ),
                "disruption" => $disruption,
            );
            $trip_headsign = $obj['trip_headsign'];
            $trip_id = $obj['trip_id'];
            $route_type = $obj['route_type'];
            $route_id = $obj['route_id'];
            $order++;
        }

        // get route details
        $route = $this->routesRepository->findOneBy(['route_id' => $route_id]);
        if ($route != null) {
            $route = $route->getRoute(true);
        }

        $vehicle_journey = array(
            "informations" => array(
                "id" => $trip_id ?? '',
                "mode" => Functions::getTransportMode($route_type ?? ''),
                "name" => $trip_id != $id ? $id : $trip_headsign,
                "headsign" => $trip_headsign ?? '',
                "description" => '',
                "message" => '',
                "origin" => array(
                    "id" => $stops[0]['id'],
                    "name" => $stops[0]['name'],
                ),
                "direction" => array(
                    "id" => $stops[count($stops) - 1]['id'],
                    "name" => $stops[count($stops) - 1]['name'],
                ),
                "line" => $route,
            ),
            "reports" => [],
            "stop_times" => $stops,
        );

        $vehicle_journey["reports"] = $reports;

        // Info théorique
        if ($trip_update == null) {
            $vehicle_journey["reports"] = array(
                array(
                    "id" => 'ADMIN:theorical',
                    "status" => 'active',
                    "cause" => 'theorical',
                    "severity" => 1,
                    "effect" => 'OTHER',
                    "message" => array(
                        "title" => "Horaires théorique",
                        "name" => "",
                    ),
                ),
            );
        }
        // }

        $json['vehicle_journey'] = $vehicle_journey;

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}