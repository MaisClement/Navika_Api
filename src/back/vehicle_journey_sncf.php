
<?php

$results = curl_SNCF($sncf_url);
$results = json_decode($results);

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
            "departure_time" =>  (string)  isset($result->departure_time) ? prepareTime($result->departure_time) : "",
            "arrival_time"  =>  (string)  isset($result->arrival_time)   ? prepareTime($result->arrival_time)   : "",
        ),
        "disruption" => null ?? null,
    );
    $order++;
}

if (isset($results->disruptions) && count($results->disruptions) > 0) {
    $stops = getDisruptionForStop( $results->disruptions );
    $reports = getReports( $results->disruptions );
}

$vehicle_journey = array(
    "informations" => array(
        "id"            =>  $vehicle_id,
        "name"          =>  $el->name,
        "mode"          =>  "rail",
        "name"          =>  $el->name,
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

$json = [];
$json['vehicle_journey'] = $vehicle_journey;

?>