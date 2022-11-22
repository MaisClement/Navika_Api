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

    $id = idfm_format($stop_id);

    // ------------

    $url = 'https://prim.iledefrance-mobilites.fr/marketplace/stop-monitoring?MonitoringRef=STIF:' . $type . ':Q:' . $id . ':';
    $fichier .= 'ddd_' . $id . '.json';
}

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 20)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------
// On récupère toutes les lignes a l'arrets

$request = getAllLinesAtStop ('IDFM:' . $id);

while($obj = $request->fetch()) {
    $lines_data[$obj['id_line']] = array(
        "id"         =>  (String)    idfm_format( $obj['id_line'] ),
        "code"       =>  (String)    $obj['shortname_line'],
        "name"       =>  (String)    $obj['name_line'],
        "mode"       =>  (String)    $obj['transportmode'],
        "color"      =>  (String)    strlen($obj['colourweb_hexa']) < 6 ? "000000" : $obj['colourweb_hexa'],
        "text_color" =>  (String)    strlen($obj['textcolourweb_hexa']) < 6 ? "000000" : $obj['textcolourweb_hexa'],
        "terminus_schedules"  =>  array()
    );
}

// ------------

$results = curl_PRIM($url);
$results = json_decode($results);

$results = $results->Siri->ServiceDelivery->StopMonitoringDelivery[0]->MonitoredStopVisit;
$schedules = [];

foreach($results as $result){
    $call = $result->MonitoredVehicleJourney;

    $line_ref = $call->LineRef->value;
    $destination_ref = $call->DestinationRef->value;

    $line_id = idfm_format( $line_ref );

    if (1 == 2){
        // no
    } else {
        if (!isset( $lines_data[$line_id] )) { 
            $request = getLinesById($line_id);
            $obj = $request->fetch();
            
            $lines_data[$line_id] = array(
                "id"         =>  (String)    $line_id,
                "code"       =>  (String)    $obj['shortname_line'],
                "name"       =>  (String)    $obj['name_line'],
                "mode"       =>  (String)    $obj['transportmode'],
                "color"      =>  (String)    strlen($obj['colourweb_hexa']) < 6 ? "000000" : $obj['colourweb_hexa'],
                "text_color" =>  (String)    strlen($obj['textcolourweb_hexa']) < 6 ? "000000" : $obj['textcolourweb_hexa'],
                "terminus_schedules"  =>  array()
            );
        }
        if (!isset( $terminus_data[$line_id][$destination_ref] )) { 
            $terminus_data[$line_id][$destination_ref] = array(
                "id"         =>  (String)    $destination_ref,
                "name"       =>  (String)    $call->MonitoredCall->DestinationDisplay[0]->value,
                "schedules"  =>  array()
            );
        }
        
        if ($call->MonitoredCall->ExpectedDepartureTime !== null && $call->MonitoredCall->ExpectedDepartureTime !== ""){
            $schedules[$line_id][$destination_ref][] = array(
                "base_departure_date_time"  =>  (String)  $call->MonitoredCall->AimedDepartureTime ?? "",
                "departure_date_time"       =>  (String)  $call->MonitoredCall->ExpectedDepartureTime ?? $call->MonitoredCall->AimedDepartureTime ?? "",
                "base_arrival_date_time"    =>  (String)  $call->MonitoredCall->AimedArrivalTime ?? "",
                "arrival_date_time"         =>  (String)  $call->MonitoredCall->ExpectedArrivalTime ?? "",

    //          noReport, onTime, delayed
                "state"                     =>  (String)  $call->MonitoredCall->DepartureStatus ?? $call->MonitoredCall->ArrivalStatus ?? "noReport",
                "platform"                  =>  (String)  "" // $call->MonitoredCall->ArrivalPlatformName ?? ""
            );
        }
    }
}



// usort($schedules, "order_departure");
usort($lines_data, "order_line");

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