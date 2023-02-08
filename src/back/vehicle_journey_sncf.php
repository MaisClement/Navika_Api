
<?php

$results = curl_SNCF($sncf_url);
$results = json_decode($results);
$el = $results->vehicle_journeys[0];

$stops = [];

foreach($el->stop_times as $result){
    $stops[] = array(
        "name"              => (String) $result->stop_point->name,
        "id"                => (String) $result->stop_point->id,
        "coords" => array(
            "lat"           => $result->stop_point->coord->lat,
            "lon"           => $result->stop_point->coord->lon,
        ),
        "stop_time" => array(
            "departure_time"=>  (String)  isset($result->departure_time) ? prepareTime($result->departure_time) : "",
            "arrival_time"  =>  (String)  isset($result->arrival_time)   ? prepareTime($result->arrival_time)   : "",
        )
    );
}

$vehicle_journey = array(
    "informations" => array(
        "id"            =>  $vehicle_id,
        "name"          =>  $el->name,
        "origin" => array(
            "id"        =>  $el->stop_times[0]->stop_point->name,
            "name"      =>  $el->stop_times[0]->stop_point->id,
        ),
        "direction" => array(
            "id"        =>  $el->stop_times[ count($el->stop_times)-1 ]->stop_point->name,
            "name"      =>  $el->stop_times[ count($el->stop_times)-1 ]->stop_point->id,
        ),   
    ),
    "stop_times" => $stops,
);

$json = [];
$json['vehicle_journey'] = $vehicle_journey;

?>