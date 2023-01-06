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
    $sncf_url = 'https://api.sncf.com/v1/coverage/sncf/stop_areas/stop_area:SNCF:' . $id . '/departures?data_freshness=realtime';
    $fichier = $dossier . $id . '.json';
}

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 20)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------
// On récupère toutes les lignes a l'arrets

$request = getAllLinesAtStop ($provider . ':' . $id);

while($obj = $request->fetch()) {

    $lines_data[$obj['id_line']] = array(
        "id"         =>  (String)    idfm_format( $obj['id_line'] ),
        "code"       =>  (String)    $obj['shortname_line'],
        "name"       =>  (String)    $obj['name_line'],
        "mode"       =>  (String)    $obj['transportmode'],
        "color"      =>  (String)    strlen($obj['colourweb_hexa']) < 6 ? "000000" : $obj['colourweb_hexa'],
        "text_color" =>  (String)    strlen($obj['textcolourweb_hexa']) < 6 ? "000000" : $obj['textcolourweb_hexa'],
    );
    
    if ($lines_data[$obj['id_line']]['mode'] == "rail" || $lines_data[$obj['id_line']]['mode'] == "nationalrail"){
        // Si c'est du ferré, l'affichage est different
        $lines_data[$obj['id_line']]['departures'] = [];
        $departures_lines[] = $obj['id_line'];

    } else {
        // Affichage normal
        $lines_data[$obj['id_line']]['terminus_schedules'] = [];

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