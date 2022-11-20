<?php

$fichier = '../data/cache/schedules/';

if (!isset($_GET['s']) || $_GET['s'] == null){
    ErrorMessage( 400, 'Required parameter "s" is missing or null.' );
} else {
    $stop_id = $_GET['s'];

    // Transformer
    // stop_area:IDFM:63880
    // TO
    // STIF:StopPoint:Q:63880:

    // ------------
    if (strpos('stop_area', $stop_id) >= 0) {
        $type = 'StopPoint';
    } else if (strpos('stop_point', $stop_id) >= 0) {
        $type = 'StopPoint';
    } else {
        ErrorMessage( 400, 'Invalid data, type of parameter not recognized' );
    }

    $search = ['stop_area', 'stop_point', 'IDFM', ':'];
    $replace = ['', '', '', ''];
    $id = str_replace($search, $replace, $stop_id);

    // ------------

    $url = 'https://prim.iledefrance-mobilites.fr/marketplace/stop-monitoring?MonitoringRef=STIF:' . $type . ':Q:' . $id . ':';
    $fichier .= 'ddd_' . $id . '.json';
}

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 60 * 60)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------

$results = curl_PRIM($url);
$results = json_decode($results);

$results = $results->Siri->ServiceDelivery->StopMonitoringDelivery[0]->MonitoredStopVisit;
// echo json_encode($results);
// exit;

$schedules = [];

foreach($results as $result){
    $call = $result->MonitoredVehicleJourney;

    $line_ref = $call->LineRef->value;
    $destination_ref = $call->DestinationRef->value;

    
    if (!isset( $lines_data[$line_ref] )) { 
        $search = ['Line', 'STIF', 'IDFM', ':'];
        $replace = ['', '', '', ''];
        $line_id = str_replace($search, $replace, $line_ref);

        $request = getLinesById($line_id);
        $obj = $request->fetch();
        
        $lines_data[$line_ref] = array(
            "id"         =>  (String)    $line_ref,
            "code"       =>  (String)    $obj['shortname_line'],
            "name"       =>  (String)    $obj['name_line'],
            "mode"       =>  (String)    $obj['transportmode'],
            "color"      =>  (String)    $obj['colourweb_hexa'] ?? "000000",
            "text_color" =>  (String)    $obj['textcolourweb_hexa'] ?? "000000",
        );
    }
    if (!isset( $terminus_data[$line_ref][$destination_ref] )) { 
        $terminus_data[$line_ref][$destination_ref] = array(
            "id"         =>  (String)    $destination_ref,
            "name"       =>  (String)    $destination_ref,
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
            "platform"                  =>  (String)  "" // $call->MonitoredCall->ArrivalPlatformName ?? ""
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