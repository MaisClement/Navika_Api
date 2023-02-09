<?php

function getApiDetails($api_results, $name) {
    $departures = [];

    foreach($api_results->departures as $api_result){
        if ($api_result->display_informations->headsign == $name){
            $stop_date_time = $api_result->stop_date_time;
            $direction = $api_result->display_informations->direction;
        
            $links = $api_result->display_informations->links;
            $disruptions = [];
        
            foreach($links as $link) {
                if ($link->type == "disruption") {
                    $disruptions[] = getDisruption($link->id, $api_results->disruptions);
                }
            }
            
            $departures = array(
                "id"                =>  (String)    getSNCFid( $api_result->links ),
                "name"              =>  (String)    $api_result->display_informations->headsign,
                "network"           =>  (String)    $api_result->display_informations->network,
                "disruptions"       => $disruptions,
            );
        }
    }
    return $departures;
}

$results = curl_GARE($sncf_url);
$results = json_decode($results);

$api_results = curl_SNCF($sncf_url_api);
$api_results = json_decode($api_results);

file_put_contents($dossier . 'SNCF_departure_' . $id . '.json', json_encode($results));

$departures = [];

foreach($results as $result){

    $details = getApiDetails($api_results, $result->trainNumber);
    
    $departures[] = array(
        "informations" => array(
            "direction" => array(
                "id"         =>  (String)    "",
                "name"       =>  (String)    $result->traffic->destination,
            ),
            "origin" => array(
                "id"         =>  (String)    "",
                "name"       =>  (String)    $result->traffic->origin,
            ),
            "id"            =>  (String)    $details['id'],
            "name"          =>  (String)    $result->trainType . ' ' . $result->trainNumber,
            "mode"          =>  (String)    "nationalrail",
            "trip_name"     =>  (String)    $result->trainNumber,
            "headsign"      =>  (String)    str_ireplace('train ', '', $result->trainType),
            "description"   =>  (String)    "",
            "message"       =>  (String)    "",
        ), 
        "stop_date_time" => array(
                                                      // Si l'horaire est present          On affiche l'horaire est             Sinon, si l'autre est present            On affiche l'autre            Ou rien  
            "base_departure_date_time"  =>  (String)  $result->scheduledTime,
            "departure_date_time"       =>  (String)  $result->actualTime,
            "base_arrival_date_time"    =>  (String)  "",
            "arrival_date_time"         =>  (String)  "",
            "state"                     =>  (String)  getSNCFState($result->traffic->eventStatus, $result->traffic->eventLevel, $result->traffic),
            "atStop"                    =>  (String)  $result->platform->isTrackactive,
            "platform"                  =>  (String)  $result->platform->track != null ? $result->platform->track : ($result->trainMode == "CAR" ? "GR" : "-"),
        ),
        "disruptions"       => $details['disruptions'],
    );
}

$json = [];

$json['mode'] = "nationalrail";

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