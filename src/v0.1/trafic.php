<?php

$fichier = '../data/cache/trafic/';

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 30)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------

$url = 'https://prim.iledefrance-mobilites.fr/marketplace/navitia/coverage/fr-idf/line_reports?count=10000';
$results = curl_PRIM($url);
$results = json_decode($results);

$reports = [];
foreach($results->disruptions as $disruption) {
    if ($disruption->status == 'past'){
        // On en veut pas on est raciste :D
    } else {
        $reports[$disruption->id] = array(
            "id"            =>  (String)    $disruption->id,
            "status"        =>  (String)    $disruption->status,
            "cause"         =>  (String)    $disruption->cause,
            "category"      =>  (String)    $disruption->category,
            "severity"      =>  (String)    getSeverity( $disruption->severity->effect, $disruption->cause, $disruption->status ),
            "effect"        =>  (String)    $disruption->severity->effect,
            "updated_at"    =>  (String)    $disruption->updated_at,
            "message"       =>  array(
                "title"     =>      floatval( $result->stop_area->coord->lat ),
                "text"      =>      floatval( $result->stop_area->coord->lon ),
            ),
        );
    }
}

$lines = [];
foreach($results->line_reports as $line) {
    $lines[] = array(
        "id"            =>  (String)    $disruption->id,
        "status"        =>  (String)    $disruption->status,
        "cause"         =>  (String)    $disruption->cause,
        "category"      =>  (String)    $disruption->category,
        "severity"      =>  (String)    getSeverity( $disruption->severity->effect, $disruption->cause, $disruption->status ),
        "effect"        =>  (String)    $disruption->severity->effect,
        "updated_at"    =>  (String)    $disruption->updated_at,
        "message"       =>  array(
            "title"     =>      floatval( $result->stop_area->coord->lat ),
            "text"      =>      floatval( $result->stop_area->coord->lon ),
        ),
    );
}


$echo["places"] = $places;

$echo = json_encode($echo);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>