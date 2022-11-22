<?php

$fichier = '../data/cache/schedules/';

if (!isset($_GET['s']) || $_GET['s'] == null){
    ErrorMessage( 
        400,
        'Required parameter "s" is missing or null.'
    );
} else {
    $stop_id = $_GET['s'];
    $stop_id = trim($stop_id);

    // ------------
    if (strpos('stop_area', $stop_id) >= 0) {
        $type = 'stop_areas';
    } else if (strpos('stop_point', $stop_id) >= 0) {
        $type = 'stop_points';
    } else {
        ErrorMessage( 400, 'Invalid data, type of parameter not recognized' );
    }

    // ------------

    $url = 'https://prim.iledefrance-mobilites.fr/marketplace/navitia/coverage/fr-idf/' . $type . '/' . $stop_id . '/terminus_schedules?count=100';
    $fichier .= 'ddd_' . $id . '.json';
}

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 30)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------

$results = curl_PRIM($url);
$results = json_decode($results);

$results = $results->terminus_schedules;

$schedules = [];

foreach($results as $result){
    $line_ref = $result->route->line->id;
    $destination_ref = $result->route->direction->id;
    
    if (!isset( $lines_data[$line_ref] )) {         
        $lines_data[$line_ref] = array(
            "id"         =>  (String)    $line_ref,
            "code"       =>  (String)    $result->route->line->code,
            "name"       =>  (String)    $result->route->line->name,
            "mode"       =>  (String)    $result->route->line->physical_modes[0]->id,
            "color"      =>  (String)    $result->route->line->color,
            "text_color" =>  (String)    $result->route->line->text_color,
        );
    }
    if (!isset( $terminus_data[$line_ref][$destination_ref] )) { 
        $terminus_data[$line_ref][$destination_ref] = array(
            "id"         =>  (String)    $destination_ref,
            "name"       =>  (String)    $result->route->direction->stop_area->name,
        );
    }
    $schedules[$line_ref][$destination_ref][] = array(
        "informations" => array(
            "id"                =>  (String)  $result->ItemIdentifier ?? "",
            "name"              =>  (String)  "165417",
            "mode"              =>  (String)  $lines_data[$line_ref]['mode'], // "rail",
            "trip_name"         =>  (String)  $call->TrainNumbers->TrainNumberRef[0]->value,
            "code"              =>  (String)  $lines_data[$line_ref]['code'], // "Transilien N",
            "network"           =>  (String)  $lines_data[$line_ref]['name'], // "Transilien N",
            "headsign"          =>  (String)  $call->JourneyNote[0]->value,
            "description"       =>  (String)  "",
            "message"           =>  (String)  "",
            "state"             =>  (String)  "ontime"
        ),
        "stop_date_time" => array(
            "base_departure_date_time"  =>  (String)  ($call->MonitoredCall->AimedDepartureTime) ?? "",
            "departure_date_time"       =>  (String)  ($call->MonitoredCall->ExpectedDepartureTime) ?? "",
            "base_arrival_date_time"    =>  (String)  ($call->MonitoredCall->AimedArrivalTime) ?? "",
            "arrival_date_time"         =>  (String)  ($call->MonitoredCall->ExpectedArrivalTime) ?? "",
            "platform"                  =>  (String)  ""
        )
    );
}

$json = [];
foreach($lines_data as $line){
    foreach($terminus_data[$line['id']] as $term){
        foreach($schedules[$line['id']][$term['id']] as $schedule){
            
            $term['schedules'][] = $schedule;
        }
        
        $line['terminus_schedules'][] = $term;
    }

    $json['schedules'][] = $line;
}

$echo = json_encode($json);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>