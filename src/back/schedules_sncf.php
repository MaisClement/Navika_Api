<?php

function getApiDetails($api_results, $name)
{
    $departures = [];

    foreach ($api_results->departures as $api_result) {
        if ($api_result->display_informations->headsign == $name) {
            $stop_date_time = $api_result->stop_date_time;
            $direction = $api_result->display_informations->direction;

            $links = $api_result->display_informations->links;
            $disruptions = [];

            foreach ($links as $link) {
                if ($link->type == "disruption") {
                    $disruptions[] = getDisruption($link->id, $api_results->disruptions);
                }
            }

            $departures = array(
                "id"                =>  (string)    getSNCFid($api_result->links),
                "name"              =>  (string)    $api_result->display_informations->headsign,
                "network"           =>  (string)    $api_result->display_informations->network,
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

foreach ($results as $result) {

    $details = getApiDetails($api_results, $result->trainNumber);
    // print_r($details);

    $departures[] = array(
        "informations" => array(
            "direction" => array(
                "id"         =>  (string)    "",
                "name"       =>  (string)    $result->traffic->destination,
            ),
            "origin" => array(
                "id"         =>  (string)    "",
                "name"       =>  (string)    $result->traffic->origin,
            ),
            "id"            =>  (string)    $details['id'],
            "name"          =>  (string)    $result->trainType . ' ' . $result->trainNumber,
            "mode"          =>  (string)    "nationalrail",
            "trip_name"     =>  (string)    $result->trainNumber,
            "headsign"      =>  (string)    str_ireplace('train ', '', $result->trainType),
            "description"   =>  (string)    "",
            "message"       =>  (string)    "",
        ),
        "disruptions"       => $details['disruptions'],
        "stop_date_time" => array(
            // Si l'horaire est present          On affiche l'horaire est             Sinon, si l'autre est present            On affiche l'autre            Ou rien  
            "base_departure_date_time"  =>  (string)  $result->scheduledTime,
            "departure_date_time"       =>  (string)  $result->actualTime,
            "base_arrival_date_time"    =>  (string)  "",
            "arrival_date_time"         =>  (string)  "",
            "state"                     =>  (string)  getSNCFState($result->traffic->eventStatus, $result->traffic->eventLevel, $result->traffic),
            "atStop"                    =>  (string)  $result->platform->isTrackactive,
            "platform"                  =>  (string)  $result->platform->track != null ? $result->platform->track : ($result->trainMode == "CAR" ? "GR" : "-"),
        )
    );
}

$json = [];

$json['departures'][] = array(
    "id"         =>  (string)    "SNCF",
    "code"       =>  (string)    "SNCF",
    "name"       =>  (string)    "Trains SNCF",
    "mode"       =>  (string)    "nationalrail",
    "color"      =>  (string)    "aaaaaa",
    "text_color" =>  (string)    "000000",
    "departures" =>  $departures
);
