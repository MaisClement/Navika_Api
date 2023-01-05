<?php

$results = curl_SNCF($sncf_url);
$results = json_decode($results);

file_put_contents($dossier . 'api_' . $id . '.json', json_encode($results));

$departures = [];

foreach($results->departures as $result){
    
    $stop_date_time = $result->stop_date_time;
    $direction = $result->display_informations->direction;

    $departures[] = array(
        "informations" => array(
            "direction" => array(
                "id"         =>  (String)    "",
                "name"       =>  (String)    trim(substr($direction, 0, strpos($direction, '(')))
            ),
            "id"            =>  (String)    getSNCFid( $result->links ),
            "name"          =>  (String)    $result->display_informations->headsign,
            "mode"          =>  (String)    "nationalrail",
            "trip_name"     =>  (String)    $result->display_informations->headsign,
            "headsign"      =>  (String)    $result->display_informations->network,
            "description"   =>  (String)    "",
            "message"       =>  (String)    "",
        ),
        "stop_date_time" => array(
                                                      // Si l'horaire est present          On affiche l'horaire est             Sinon, si l'autre est present            On affiche l'autre            Ou rien  
            "base_departure_date_time"  =>  (String)  isset($stop_date_time->base_departure_date_time)  ? prepareTime($stop_date_time->base_departure_date_time) : (isset($stop_date_time->departure_date_time)         ? prepareTime($call->departure_date_time)       : ""),
            "departure_date_time"       =>  (String)  isset($stop_date_time->departure_date_time)       ? prepareTime($stop_date_time->departure_date_time)      : (isset($stop_date_time->base_departure_date_time)    ? prepareTime($call->base_departure_date_time)  : ""),
            "base_arrival_date_time"    =>  (String)  isset($stop_date_time->base_arrival_date_time)    ? prepareTime($stop_date_time->base_arrival_date_time)   : (isset($stop_date_time->arrival_date_time)           ? prepareTime($call->arrival_date_time)         : ""),
            "arrival_date_time"         =>  (String)  isset($stop_date_time->arrival_date_time)         ? prepareTime($stop_date_time->arrival_date_time)        : (isset($stop_date_time->base_arrival_date_time)      ? prepareTime($call->base_arrival_date_time)    : ""),
            "state"                     =>  (String)  "ontime",
            "atStop"                    =>  (String)  "false",
            "platform"                  =>  (String)  "-"
        )
    );
}

usort($departures, "timeSort");

$json = [];

$json['departures'][] = array(
    "id"         =>  (String)    "SNCF",
    "code"       =>  (String)    "SNCF",
    "name"       =>  (String)    "Trains SNCF",
    "mode"       =>  (String)    "nationalrail",
    "color"      =>  (String)    "aaaaaa",
    "text_color" =>  (String)    "000000",
    "departures" =>  $departures
);

?>