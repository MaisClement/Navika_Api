
<?php

$results = curl_SNCF($sncf_url);
$results = json_decode($results);
$el = $results->vehicle_journeys[0];

$stops = [];
$order = 0;
foreach($el->stop_times as $result){
    $stops[] = array(
        "name"              => (String) $result->stop_point->name,
        "id"                => (String) $result->stop_point->id,
        "order"             => (int)    $order,
        "type"              => (int)    count($el->stop_times) -1 == $order ? 'terminus' : ($order == 0 ? 'origin' : ''),
        "coords" => array(
            "lat"           => $result->stop_point->coord->lat,
            "lon"           => $result->stop_point->coord->lon,
        ),
        "stop_time" => array(
            "departure_time"=>  (String)  isset($result->departure_time) ? prepareTime($result->departure_time) : "",
            "arrival_time"  =>  (String)  isset($result->arrival_time)   ? prepareTime($result->arrival_time)   : "",
        )
    );
    $order++;
}

$disruptions = [];
if (isset($results->disruptions)){
    $disruptions = listDisruption($results->disruptions);
}

$vehicle_journey = array(
    "informations" => array(
        "id"            =>  $vehicle_id,
        "name"          =>  $el->name,
        "origin" => array(
            "id"        =>  $el->stop_times[0]->stop_point->id,
            "name"      =>  $el->stop_times[0]->stop_point->name,
        ),
        "direction" => array(
            "id"        =>  $el->stop_times[ count($el->stop_times)-1 ]->stop_point->id,
            "name"      =>  $el->stop_times[ count($el->stop_times)-1 ]->stop_point->name,
        ),   
    ),
    "disruptions" => $disruptions,
    "stop_times" => $stops,
);

$json = [];
$json['vehicle_journey'] = $vehicle_journey;

?>