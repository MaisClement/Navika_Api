<?php

namespace App\Controller;

use App\Repository\ProviderRepository;
use App\Repository\RoutesRepository;
use App\Repository\StopsRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\Functions;
use OpenApi\Attributes as OA;
use App\Service\Logger;

ini_set('memory_limit', '-1');

class VehicleJourney
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private Logger $logger;

    private ProviderRepository $providerRepository;
    private RoutesRepository $routesRepository;
    private StopsRepository $stopsRepository;

    public function __construct(EntityManagerInterface $entityManager, ProviderRepository $providerRepository, Logger $logger, RoutesRepository $routesRepository, StopsRepository $stopsRepository, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->logger = $logger;

        $this->providerRepository = $providerRepository;
        $this->routesRepository = $routesRepository;
        $this->stopsRepository = $stopsRepository;
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
            $idfm_id = substr($id, 0, strpos($id, ":LOC-NAVI:") + 4);
            $id = substr($id, strpos($id, ":LOC-NAVI:") + 10);
        } else {
            $provider = 'ADMIN';
        }

        $json = [];

        // ------------

        $trip = Functions::getTripStopsById($db, $id, date("Y-m-d"));

        $len = count($trip);
        if ($len == 0) {
            $this->logger->logHttpErrorMessage($request, "Nothing where found for this id", 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this id'), 400);
        }
        $trip_update = [];
        $reports = [];
        $alerts = [];

        $provider_id = $trip[0]['provider_id'];
        if ($provider_id != null) {
            $provider = $this->providerRepository->find($provider_id);

            $realtime = Functions::getRealtimeData($provider);

            $trip_update = Functions::getTripRealtime($realtime['trip_updates'], $trip[0]['trip_id'], null);
            $alerts = Functions::getTripRealtimeAlerts($realtime['alerts'], $trip[0]['trip_id']);
        }

        // -----

        if ($provider == 'IDFM') {
            $content = file_get_contents($dir . '/NAVIKA_idfm_departures.json');
            $content = json_decode($content, true);

            if (isset($content[$idfm_id])) {
                $IDFM_vehicle_journey = $content[$idfm_id];

                $idfm_real_time = [];
                foreach($IDFM_vehicle_journey['stop_date_time'] as $stop_time) {
                    if (isset($this->stopsRepository->findStopsByExtensions($stop_time['stop_id'])[0])) {
                        $_stop = $this->stopsRepository->findStopsByExtensions($stop_time['stop_id'])[0];
                        if ($_stop != null) {
                            $idfm_real_time[$_stop->getParentStation()] = $stop_time;
                        }
                    }
                }
            }
        }

        $stops = [];
        $order = 0;

        foreach ($trip as $obj) {
            // Theorical place of the stop inside the trip, the effective one is
            // computed below, once the realtime itinerary is known.
            $base_type = $order == 0 ? 'origin' : ((int) $len - 1 === $order ? 'terminus' : '');

            $_stop = array(
                'id'            => $obj['parent_station'],
                'name'          => (string) $obj['stop_name'],
                // 'type'          => (string) $obj['location_type'] ? 'stop_point' : 'stop_area',
                'type'          => (string) $base_type,
                'base_type'     => (string) $base_type,
                'state'         => (string) 'unchanged',
                'distance'      => (int) 0,
                'town'          => (string) '',
                'zip_code'      => (string) '',
                'coord' => array(
                    "lat"       => $obj['stop_lat'],
                    "lon"       => $obj['stop_lon'],
                ),
                'modes' => array(),
            );

            if ($provider == 'IDFM' && isset( $idfm_real_time[$obj['parent_station']] ) ) {
                $_stop["stop_date_time"]    = $idfm_real_time[$obj['parent_station']]['stop_date_time'];
                $_stop["disruption"]        = $idfm_real_time[$obj['parent_station']]['disruption'];

                if (($_stop["disruption"]['departure_state'] ?? '') == 'deleted' && ($_stop["disruption"]['arrival_state'] ?? '') == 'deleted') {
                    $_stop['state'] = 'deleted';
                }

            } else {
                $real_time = Functions::getTripRealtimeDateTime($trip_update, $obj['stop_id']);

                $base_departure_date_time = Functions::prepareTime($obj['departure_time'], true);
                $base_arrival_date_time = Functions::prepareTime($obj['arrival_time'], true);

                // The vehicle never leaves its terminus : its arrival is the
                // only time to compare with, so the delay stays visible for a
                // client displaying departures.
                if ($real_time['is_terminus'] || $base_type == 'terminus') {
                    $base_departure_date_time = $base_arrival_date_time;
                }
                // Same thing at the origin, the vehicle never arrives there.
                if ($real_time['is_origin'] || $base_type == 'origin') {
                    $base_arrival_date_time = $base_departure_date_time;
                }

                $_stop["stop_date_time"] = array(
                    "base_departure_date_time"  => (string) $base_departure_date_time,
                    "departure_date_time"       => (string) ($real_time['departure_date_time'] != null ? Functions::prepareTime($real_time['departure_date_time'], true) : $base_departure_date_time),
                    "base_arrival_date_time"    => (string) $base_arrival_date_time,
                    "arrival_date_time"         => (string) ($real_time['arrival_date_time'] != null ? Functions::prepareTime($real_time['arrival_date_time'], true) : $base_arrival_date_time),
                    "state"                     => $real_time['departure_state'] == null && $real_time['arrival_state'] == null ? "theorical" : 'ontime',
                    "atStop"                    => "false",
                    "platform"                  => "-"
                );
                $_stop["disruption"] = array(
                    "departure_state"           => (string) ($real_time['departure_state'] != null ? $real_time['departure_state'] : 'unchanged'),
                    "arrival_state"             => (string) ($real_time['arrival_state'] != null ? $real_time['arrival_state'] : 'unchanged'),
                    "message"                   => (string) ($real_time['message'] != null ? $real_time['message'] : ''),
                );

                if ($real_time['stop_state'] != null) {
                    $_stop['state'] = (string) $real_time['stop_state'];
                }
            }

            $stops[] = $_stop;

            $trip_headsign = $obj['trip_headsign'];
            $trip_id = $obj['trip_id'];
            $route_type = $obj['route_type'];
            $route_id = $obj['route_id'];
            $order++;
        }

        // ---- Itinerary really operated.
        // A feed does not always repeat the stops it drops, so everything
        // located after an exceptional terminus - or before an exceptional
        // origin - is dropped too.
        $exceptional_terminus = null;
        $exceptional_origin = null;

        foreach ($stops as $i => $stop) {
            if ($stop['state'] == 'exceptional_terminus') {
                $exceptional_terminus = $i;
            }
            if ($stop['state'] == 'exceptional_origin' && $exceptional_origin === null) {
                $exceptional_origin = $i;
            }
        }

        if ($exceptional_terminus !== null) {
            for ($i = $exceptional_terminus + 1; $i < count($stops); $i++) {
                $stops[$i] = $this->deleteStop($stops[$i]);
            }
        }
        if ($exceptional_origin !== null) {
            for ($i = 0; $i < $exceptional_origin; $i++) {
                $stops[$i] = $this->deleteStop($stops[$i]);
            }
        }

        // Origin and terminus of the itinerary really operated : the first and
        // the last stop still served.
        $effective_origin = null;
        $effective_direction = null;

        foreach ($stops as $i => $stop) {
            if ($stop['state'] != 'deleted') {
                if ($effective_origin === null) {
                    $effective_origin = $i;
                }
                $effective_direction = $i;
            }
        }

        // Nothing is served anymore (fully cancelled trip), the theorical
        // boundaries are kept.
        if ($effective_origin === null) {
            $effective_origin = 0;
            $effective_direction = count($stops) - 1;
        } else {
            foreach ($stops as $i => $stop) {
                $stops[$i]['type'] = (string) ($i === $effective_origin ? 'origin' : ($i === $effective_direction ? 'terminus' : ''));
            }
        }

        // get route details
        $route = $this->routesRepository->findOneBy(['route_id' => $route_id]);
        if ($route != null) {
            $route = $route->getRoute(true);
        }

        $reports = Functions::getTripRealtimeReports(
            $trip_update,
            $exceptional_terminus !== null ? $stops[$exceptional_terminus]['name'] : null,
            $exceptional_origin !== null ? $stops[$exceptional_origin]['name'] : null
        );

        $vehicle_journey = array(
            "informations" => array(
                "id"                => $trip_id ?? '',
                "mode"              => Functions::getTransportMode($route_type ?? ''),
                "name"              => $trip_id != $id ? $id : $trip_headsign,
                "headsign"          => $trip_headsign ?? '',
                "description"       => '',
                "message"           => '',
                "state"             => $trip_update != null && isset($trip_update['state']) ? $trip_update['state'] : 'theorical',
                "vehicle_size"      => isset($IDFM_vehicle_journey) ? $IDFM_vehicle_journey['informations']['vehicle_size'] : null,
                // Where the vehicle really starts from and ends at.
                "origin"            => array(
                    "id"            => $stops[$effective_origin]['id'],
                    "name"          => $stops[$effective_origin]['name'],
                ),
                "direction"         => array(
                    "id"            => $stops[$effective_direction]['id'],
                    "name"          => $stops[$effective_direction]['name'],
                ),
                "line"              => $route,
            ),
            "reports"               => [],
            "stop_date_time"        => $stops,
        );

        // The theorical boundaries are only given when the itinerary has been
        // modified, otherwise they are the ones above.
        if ($effective_origin !== 0) {
            $vehicle_journey["informations"]["original_origin"] = array(
                "id"                => $stops[0]['id'],
                "name"              => $stops[0]['name'],
            );
        }
        if ($effective_direction !== count($stops) - 1) {
            $vehicle_journey["informations"]["original_direction"] = array(
                "id"                => $stops[count($stops) - 1]['id'],
                "name"              => $stops[count($stops) - 1]['name'],
            );
        }

        $vehicle_journey["reports"] = $reports;

        // Info théorique
        if ($trip_update == null && !isset($IDFM_vehicle_journey)) {
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

        // Messages of the service alerts feed about this trip : they explain
        // what the itinerary alone cannot tell, whatever the times are
        // theorical or not.
        $vehicle_journey["reports"] = array_merge($vehicle_journey["reports"], $alerts);

        $json['vehicle_journey'] = $vehicle_journey;

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }

    /**
     * Flags a stop as not served anymore, its theorical times are kept : they
     * are the ones to display, struck through.
     *
     * @param array $stop The stop to drop.
     * @return array The dropped stop.
     */
    private function deleteStop($stop): array
    {
        $stop['state'] = 'deleted';
        $stop['disruption']['departure_state'] = 'deleted';
        $stop['disruption']['arrival_state'] = 'deleted';

        return $stop;
    }
}
