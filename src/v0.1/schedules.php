<?php

$dossier = '../data/cache/schedules/';

if (!isset($_GET['s']) || $_GET['s'] == null){
    ErrorMessage( 400, 'Required parameter "s" is missing or null.' );
} else {
    $stop_id = $_GET['s'];

    // ------------
    if (str_contains($stop_id, 'SNCF:')) {
        $provider = 'SNCF';
    } else if (str_contains($stop_id, 'IDFM:')) {
        $provider = 'IDFM';
    } else {
        ErrorMessage( 400, 'Invalid data, provider not recognized' );
    }

    $type = 'StopPoint';
    $id = idfm_format($stop_id);

    // ------------

    $prim_url = 'https://prim.iledefrance-mobilites.fr/marketplace/stop-monitoring?MonitoringRef=STIF:' . $type . ':Q:' . $id . ':';
    $sncf_url = 'https://garesetconnexions-online.azure-api.net/API/PIV/Departures/00' . $id;
    $sncf_url_api = 'https://api.sncf.com/v1/coverage/sncf/stop_areas/stop_area:SNCF:' . $id . '/departures?count=30&data_freshness=realtime';
    $fichier = $dossier . $id . '.json';
}

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 20)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------
// On récupère toutes les lignes a l'arrets

$request = getAllLinesAtStop ($provider . ':' . $id);
$departures_lines = [];

while($obj = $request->fetch()) {
    $line_id = $obj['id_line'];

    if ($obj['shortname_line'] == 'TER') {
        $line_id = 'TER';
        $lines_data['TER'] = array(
            "id"         =>  (String)    $line_id,
            "code"       =>  (String)    'TER',
            "name"       =>  (String)    'TER',
            "mode"       =>  (String)    'rail',
            "color"      =>  (String)    "000000",
            "text_color" =>  (String)    "aaaaaa",
        );
    } else {
        $lines_data[$line_id] = array(
            "id"         =>  (String)    $line_id,
            "code"       =>  (String)    $obj['shortname_line'],
            "name"       =>  (String)    $obj['name_line'],
            "mode"       =>  (String)    $obj['transportmode'],
            "color"      =>  (String)    strlen($obj['colourweb_hexa']) < 6 ? "ffffff" : $obj['colourweb_hexa'],
            "text_color" =>  (String)    strlen($obj['textcolourweb_hexa']) < 6 ? "000000" : $obj['textcolourweb_hexa'],
        );
    }
    
    if ($lines_data[$line_id]['mode'] == "rail" || $lines_data[$line_id]['mode'] == "nationalrail"){
        // Si c'est du ferré, l'affichage est different
        $lines_data[$line_id]['departures'] = [];
        if (!in_array($line_id, $departures_lines)) {
            $departures_lines[] = $line_id;
        }

    } else {
        // Affichage normal
        $lines_data[$line_id]['terminus_schedules'] = [];

    }
}

// ------------

if ($provider == 'SNCF') {
    include('back/schedules_sncf.php');

} else if ($provider == 'IDFM') {
    include('back/schedules_idfm.php');

} 

$echo = json_encode($json);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>