<?php

$dir = '../data/cache/vehicle_';

// ---------------
$parameters = ['id'];
$message = checkRequiredParameter($parameters);

if ($message) {
    ErrorMessage(400, $message);
}
// ---------------

$vehicle_id = $_GET['id'];

// ---------------
if (str_contains($vehicle_id, 'SNCF:')) {
    $provider = 'SNCF';
} else {
    $provider = 'ADMIN:';
}

// ---------------

if ($provider == 'SNCF') {
    if (strpos($vehicle_id, ':RealTime')) {
        $vehicle_id = substr($vehicle_id, 0, strpos($vehicle_id, ':RealTime'));
    }
    
    $sncf_url = 'https://api.sncf.com/v1/coverage/sncf/vehicle_journeys/' . $vehicle_id;
    include('back/vehicle_journey_sncf.php');

} else { // ADMIN - On va chercher dans le GTFS
    $request = getTripStopsByName($vehicle_id, date("Y-m-d"));

    $len = $request->rowCount();
    if ($len == 0) {
        ErrorMessage(422, "Nothing where found for this id");
    }
    
    $stops = [];
    $order = 0;

    while ($obj = $request->fetch()) {
        $stops[] = array(
            "name"              => (string) $obj['stop_name'],
            "id"                => (string) $obj['parent_station'],
            "order"             => (int)    $order,
            "type"              => (int)    $len - 1 == $order ? 'terminus' : ($order == 0 ? 'origin' : ''),
            "coords" => array(
                "lat"           => $obj['stop_lat'],
                "lon"           => $obj['stop_lon'],
            ),
            "stop_time" => array(
                "departure_time"  =>  (string)  prepareTime($obj['departure_time']) ?? "",
                "arrival_time"    =>  (string)  prepareTime($obj['arrival_time']) ?? '',
            ),
            "disruption" => null,  
        );
        $trip_headsign = $obj['trip_headsign'];
        $route_type = $obj['route_type'];
        $order++;
    }

    $vehicle_journey = array(
        "informations" => array(
            "id"            =>  $vehicle_id,
            "name"          =>  $trip_headsign,
            "mode"          =>  getTransportMode($route_type),
            "name"          =>  $vehicle_id,
            "headsign"      =>  $trip_headsign,
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
        // Information de l'info théorique
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
    $json = [];
    $json['vehicle_journey'] = $vehicle_journey;
}




$echo = json_encode($json);
echo $echo;