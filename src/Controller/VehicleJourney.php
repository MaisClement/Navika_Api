<?php

namespace App\Controller;

use App\Repository\ProviderRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\Functions;
use OpenApi\Attributes as OA;

class VehicleJourney
{
    private $entityManager;
    private ProviderRepository $providerRepository;
    private $params;

    public function __construct(EntityManagerInterface $entityManager, ProviderRepository $providerRepository, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;

        $this->providerRepository = $providerRepository;

        $this->params = $params;
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
            return new JsonResponse(Functions::ErrorMessage(400, 'One or more parameters are missing or null, have you "id" ?'), 400);
        }

        $provider = str_starts_with($id, 'SNCF:') || str_starts_with($id, 'vehicle_journey:SNCF') ? 'SNCF' : 'ADMIN';

        $json = [];

        // ------------
        
        $trip = Functions::getTripStopsByNameOrId($db, $id, date("Y-m-d"));

        $len = count($trip);
        if ($len == 0) {
            return new JsonResponse(Functions::ErrorMessage(400, 'Nothing where found for this id'), 400);
        }

        $provider_id = $trip[0]['provider_id'];
        $provider = $this->providerRepository->find($provider_id);

        $trips_update = Functions::getRealtimeData($provider);
        
        $file_name = $dir . '/test_gtfsrt.pb';
        file_put_contents($file_name, json_encode($trips_update, JSON_PRETTY_PRINT));
        
        $trip_update = Functions::getTripRealtime($trips_update, $trip[0]['trip_id']);
        // -----

        $stops = [];
        $order = 0;

        foreach ($trip as $obj) {
            $real_time = Functions::getTripRealtimeDateTime($trip_update, $obj['stop_id']);
            
            
            $disruption = 
            
            $stops[] = array(
                "name"          => (string) $obj['stop_name'],
                "id"            => (string) $obj['parent_station'],
                "order"         => (int) $order,
                "type"          => (int) $len - 1 === $order ? 'terminus' : ($order == 0 ? 'origin' : ''),
                "coords" => array(
                    "lat"       => $obj['stop_lat'],
                    "lon"       => $obj['stop_lon'],
                ),
                "stop_time" => array(
                    "departure_time"    => Functions::prepareTime($obj['departure_time'], true),
                    "arrival_time"      => Functions::prepareTime($obj['arrival_time'], true),
                ),
                "disruption"    => null,
            );
            $trip_headsign = $obj['trip_headsign'];
            $trip_id = $obj['trip_id'];
            $route_type = $obj['route_type'];
            $order++;
        }

        $vehicle_journey = array(
            "informations"  => array(
                "id"            => $trip_id ?? '',
                "mode"          => Functions::getTransportMode($route_type ?? ''),
                "name"          => $id,
                "headsign"      => $trip_headsign ?? '',
                "description"   => '',
                "message"       => '',
                "origin"        => array(
                    "id"        => $stops[0]['id'],
                    "name"      => $stops[0]['name'],
                ),
                "direction"     => array(
                    "id"        => $stops[count($stops) - 1]['id'],
                    "name"      => $stops[count($stops) - 1]['name'],
                ),
            ),

            // Info théorique
            "reports" => array(
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
            ),
            "stop_times" => $stops,
        );
        $json['vehicle_journey'] = $vehicle_journey;

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}