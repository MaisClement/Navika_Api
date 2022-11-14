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
echo json_encode($results);
exit;

$lines[] = array(
    "id"         =>  (String)    $line->id,
    "code"       =>  (String)    $line->code,
    "name"       =>  (String)    $line->name,
    "mode"       =>  (String)    $line->commercial_mode->id,
    "color"      =>  (String)    $line->color,
    "text_color" =>  (String)    $line->text_color,
    "terminus_schedules" => [],
);

$terminus_schedules[] = array(
    "id"         =>  (String)    $stop->id,
    "name"       =>  (String)    $stop->name,
    "mode"       =>  (String)    $line->commercial_mode->id,
    "color"      =>  (String)    $line->color,
    "text_color" =>  (String)    $line->text_color,
    "terminus_schedules" => [],
);

$schedules[$line_ref][$destination_ref][] = array(

    "stop_date_time" => array(
        "base_departure_date_time"  =>  (String)  "2022-10-18 14:05:00",
        "departure_date_time"       =>  (String)  "2022-10-18 14:05:00",
        "base_arrival_date_time"    =>  (String)  "2022-10-18 14:06:00",
        "arrival_date_time"         =>  (String)  "2022-10-18 14:06:00",
        "platform"                  =>  (String)  "B"
    )

);


$echo = json_encode($echo);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>