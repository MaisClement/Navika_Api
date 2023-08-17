<?php

namespace App\Controller;

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
    private \Doctrine\ORM\EntityManagerInterface $entityManager;
    private \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $params;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
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
         name:"id",
         in:"path",
         description:"vehicle journey id",
         schema: new OA\Schema(type: "string")
     )]
 
     #[OA\Response(
         response: 200,
         description: ''
     )]
 
     public function getVehicleJourneys($id, Request $request)
    {
        $db = $this->entityManager->getConnection();

        if (!isset($id)) {
            return new JsonResponse(Functions::ErrorMessage(400, 'One or more parameters are missing or null, have you "id" ?'), 400);
        }

        $provider = str_starts_with($id, 'SNCF:') || str_starts_with($id, 'vehicle_journey:SNCF') ? 'SNCF' : 'ADMIN';
        
        $json = [];

        // ------------
        
        if ($provider == 'SNCF') {
            if (strpos($id, ':RealTime')) {
                $id = substr($id, 0, strpos($id, ':RealTime'));
            }
            
            $url = 'https://api.sncf.com/v1/coverage/sncf/vehicle_journeys/' . $id;
            
            $client = HttpClient::create();
            $response = $client->request('GET', $url, [
                'auth_basic' => [$this->params->get('sncf_api_key'), ''],
            ]);
            $status = $response->getStatusCode();

            if ($status != 200){
                return new JsonResponse(Functions::ErrorMessage(520, 'Invalid fetched data'), 520);
            }

            $content = $response->getContent();
            $results = json_decode($content);

            $el = $results->vehicle_journeys[0];

            $stops = [];
            $reports = [];
            $order = 0;

            foreach ($el->stop_times as $result) {
                $stops[] = array(
                    "name"              => (string) $result->stop_point->name,
                    "id"                => (string) $result->stop_point->id,
                    "order"             => (int)    $order,
                    "type"              => (int)    count($el->stop_times) - 1 == $order ? 'terminus' : ($order == 0 ? 'origin' : ''),
                    "coords" => array(
                        "lat"           => $result->stop_point->coord->lat,
                        "lon"           => $result->stop_point->coord->lon,
                    ),
                    "stop_time" => array(
                        "departure_time" =>  (string)  isset($result->departure_time) !== '' && (string)  isset($result->departure_time) !== '0' ? Functions::prepareTime($result->departure_time, true) : "",
                        "arrival_time"  =>  (string)  isset($result->arrival_time) !== '' && (string)  isset($result->arrival_time) !== '0'   ? Functions::prepareTime($result->arrival_time, true)   : "",
                    ),
                    "disruption" => null ?? null,
                );
                $order++;
            }

            if (isset($results->disruptions) && count($results->disruptions) > 0) {
                $stops = Functions::getDisruptionForStop( $results->disruptions );
                $reports = Functions::getReports( $results->disruptions );
            }

            $vehicle_journey = array(
                "informations" => array(
                    "id"            =>  $id,
                    "name"          =>  $el->name,
                    "mode"          =>  "rail",
                    "headsign"      =>  $el->stop_times[count($el->stop_times) - 1]->stop_point->name,
                    "description"   =>  "",
                    "message"       =>  "",
                    "origin" => array(
                        "id"        =>  $stops[0]['id'],
                        "name"      =>  $stops[0]['name'],
                    ),
                    "direction" => array(
                        "id"        =>  $stops[count($stops) - 1]['id'],
                        "name"      =>  $stops[count($stops) - 1]['name'],
                    ),
                ),
                "reports" => $reports,
                "disruptions" => $results->disruptions,
                "stop_times" => $stops,
            );
            
            $json['vehicle_journey'] = $vehicle_journey;
        
        } else { // ADMIN - On va chercher dans le GTFS
            $trip = Functions::getTripStopsByNameOrId($db, $id, date("Y-m-d"));

            $len = count( $trip );
            if ( $len == 0) {
                return new JsonResponse(Functions::ErrorMessage(400, 'Nothing where found for this id'), 400);
            }
            
            $stops = [];
            $order = 0;
        
            foreach( $trip as $obj ) {
                $stops[] = array(
                    "name"              => (string) $obj['stop_name'],
                    "id"                => (string) $obj['parent_station'],
                    "order"             => (int)    $order,
                    "type"              => (int)    $len - 1 === $order ? 'terminus' : ($order == 0 ? 'origin' : ''),
                    "coords" => array(
                        "lat"           => $obj['stop_lat'],
                        "lon"           => $obj['stop_lon'],
                    ),
                    "stop_time" => array(
                        "departure_time"  =>  (string)  Functions::prepareTime($obj['departure_time'], true) ?? "",
                        "arrival_time"    =>  (string)  Functions::prepareTime($obj['arrival_time'], true) ?? '',
                    ),
                    "disruption" => null,  
                );
                $trip_headsign = $obj['trip_headsign'];
                $trip_id = $obj['trip_id'];
                $route_type = $obj['route_type'];
                $order++;
            }
        
            $vehicle_journey = array(
                "informations" => array(
                    "id"            =>  $trip_id ?? '',
                    "mode"          =>  Functions::getTransportMode($route_type ?? ''),
                    "name"          =>  $id,
                    "headsign"      =>  $trip_headsign ?? '',
                    "description"   =>  '',
                    "message"       =>  '',
                    "origin" => array(
                        "id"        =>  $stops[0]['id'],
                        "name"      =>  $stops[0]['name'],
                    ),
                    "direction" => array(
                        "id"        =>  $stops[count($stops) - 1]['id'],
                        "name"      =>  $stops[count($stops) - 1]['name'],
                    ),
                ),
        
                // Info théorique
                "reports" => array(
                    array(
                        "id"        =>  'ADMIN:theorical',
                        "status"    =>  'active',
                        "cause"     =>  'theorical',
                        "category"  =>  'theorical',
                        "severity"  =>  1,
                        "effect"    =>  'OTHER',
                        "message"   =>  array(
                            "title"     =>  "Horaires théorique",
                            "name"      =>  "",
                        ),
                    ),
                ),
                "stop_times" => $stops,
            );
            $json['vehicle_journey'] = $vehicle_journey;
        }

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}
