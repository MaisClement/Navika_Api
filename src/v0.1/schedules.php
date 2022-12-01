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
    $fichier .= $id . '.json';
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
    );
    
    if ($obj['transportmode'] == "rail"){
        // Si c'est du ferré, l'affichage est different
        $lines_data[$obj['id_line']]['departures'] = [];

    } else {
        // Affichage normal
        $lines_data[$obj['id_line']]['terminus_schedules'] = [];

    }
}

// ------------

$results = curl_PRIM($url);
$results = json_decode($results);

$responseTimestamp = date_create($results->Siri->ServiceDelivery->ResponseTimestamp);
$results = $results->Siri->ServiceDelivery->StopMonitoringDelivery[0]->MonitoredStopVisit;

$schedules = [];
$departures = [];
$departures_lines = [];

foreach($results as $result){
    $call = $result->MonitoredVehicleJourney->MonitoredCall;

    $line_id = idfm_format( $result->MonitoredVehicleJourney->LineRef->value );
    $destination_ref = $result->MonitoredVehicleJourney->DestinationRef->value;

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
        );
    }
    if ($lines_data[$line_id]['mode'] == "rail" && date_create($call->ExpectedDepartureTime == "" ? $call->AimedDepartureTime ?? "" : $call->ExpectedDepartureTime) >= $responseTimestamp){
        // Si c'est du ferré, l'affichage est different

        if (!in_array($line_id, $departures_lines)){
            $departures_lines[] = $line_id;
        }
        $departures[$line_id][] = array(
            "informations" => array(
                "direction" => array(
                  "id"         =>  (String)   $destination_ref,
                  "name"       =>  (String)   gare_format( $call->DestinationDisplay[0]->value),
                ),
                "id"            =>  (String)  $result->ItemIdentifier,
                "name"          =>  (String)  $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value,
                "mode"          =>  (String)  $lines_data[$line_id]['mode'],
                "trip_name"     =>  (String)  $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value,
                "headsign"      =>  (String)  $result->MonitoredVehicleJourney->JourneyNote[0]->value,
                "description"   =>  (String)  "",
                "message"       =>  (String)  getMessage($call),
            ),
            "stop_date_time" => array(
                "base_departure_date_time"  =>  (String)  $call->AimedDepartureTime == "" ? $call->ExpectedDepartureTime ?? "" : $call->AimedDepartureTime,
                "departure_date_time"       =>  (String)  $call->ExpectedDepartureTime == "" ? $call->AimedDepartureTime ?? "" : $call->ExpectedDepartureTime,
                "base_arrival_date_time"    =>  (String)  $call->AimedArrivalTime == "" ? $call->ExpectedArrivalTime ?? "" : $call->AimedArrivalTime,
                "arrival_date_time"         =>  (String)  $call->ExpectedArrivalTime == "" ? $call->AimedArrivalTime ?? "" : $call->ExpectedArrivalTime,
                "state"                     =>  (String)  getState($call),
                "atStop"                    =>  (String)  $call->VehicleAtStop ? "true" : "false",
                "platform"                  =>  (String)  $call->ArrivalPlatformName->value == "" ? "-" : $call->ArrivalPlatformName->value
            )
        );
    } else {
        // Affichage normal

        if (!isset( $terminus_data[$line_id][$destination_ref] )) { 
            $terminus_data[$line_id][$destination_ref] = array(
                "id"         =>  (String)    $destination_ref,
                "name"       =>  (String)    $call->DestinationDisplay[0]->value,
                "schedules"  =>  array()
            );
        }
        
        if ($call->ExpectedDepartureTime !== null && $call->ExpectedDepartureTime !== ""){
            $schedules[$line_id][$destination_ref][] = array(
                "base_departure_date_time"  =>  (String)  $call->AimedDepartureTime == "" ? $call->ExpectedDepartureTime ?? "" : $call->AimedDepartureTime,
                "departure_date_time"       =>  (String)  $call->ExpectedDepartureTime == "" ? $call->AimedDepartureTime ?? "" : $call->ExpectedDepartureTime,
                "base_arrival_date_time"    =>  (String)  $call->AimedArrivalTime == "" ? $call->ExpectedArrivalTime ?? "" : $call->AimedArrivalTime,
                "arrival_date_time"         =>  (String)  $call->ExpectedArrivalTime == "" ? $call->AimedArrivalTime ?? "" : $call->ExpectedArrivalTime,
                "state"                     =>  (String)  getState($call),
                "atStop"                    =>  (String)  $call->VehicleAtStop ? "true" : "false",
                "platform"                  =>  (String)  $call->ArrivalPlatformName->value == "" ? "-" : $call->ArrivalPlatformName->value
            );
        }
    }
}

$json = [];

// usort($departures, "order_departure");

foreach($departures_lines as $line){
    $l = $lines_data[$line];
    foreach($departures[$line] as $departure){
        $l['departures'][] = $departure;
    }
    $json['departures'][] = $l;
}

usort($lines_data, "order_line");

foreach($lines_data as $line){

    if ($line['mode'] != 'rail'){
        foreach($terminus_data[$line['id']] as $term){
            foreach($schedules[$line['id']][$term['id']] as $schedule){
                $term['schedules'][] = $schedule;
            }
            $line['terminus_schedules'][] = $term;
        }
        $json['schedules'][] = $line;
    }
}

$echo = json_encode($json);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>