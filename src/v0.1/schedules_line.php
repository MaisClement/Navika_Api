<?php

$dossier = '../data/cache/schedules_line/';

if (!isset($_GET['s']) || $_GET['s'] == null || !isset($_GET['l']) || $_GET['l'] == null) {
    ErrorMessage(400, 'Required parameter "s" or "l" is missing or null.');
}

$stop_id = $_GET['s'];

// ------------
if (str_contains($stop_id, 'SNCF:')) {
    $provider = 'SNCF';
} else if (str_contains($stop_id, 'IDFM:')) {
    $provider = 'IDFM';
} else {
    ErrorMessage(400, 'Invalid data, provider not recognized');
}

$type = 'StopPoint';
$id = idfm_format($stop_id);
$id_line = idfm_format($_GET['l']);

// ------------
$prim_url = 'https://prim.iledefrance-mobilites.fr/marketplace/stop-monitoring?MonitoringRef=STIF:' . $type . ':Q:' . $id . ':';
$sncf_url = 'https://garesetconnexions-online.azure-api.net/API/PIV/Departures/00' . $id;
$sncf_url_api = 'https://api.sncf.com/v1/coverage/sncf/stop_areas/stop_area:SNCF:' . $id . '/departures?count=30&data_freshness=realtime';
$fichier = $dossier . $id . '.json';

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 20)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------
// On récupère toutes les lignes a l'arrets


$request = getLinesById($id_line);

if ($request->rowCount() <= 0) ErrorMessage(202, 'Nothing found.');

$obj = $request->fetch();
$lines_data[$obj['id_line']] = array(
    "id"         =>  (string)    idfm_format($obj['id_line']),
    "code"       =>  (string)    $obj['shortname_line'],
    "name"       =>  (string)    $obj['name_line'],
    "mode"       =>  (string)    $obj['transportmode'],
    "color"      =>  (string)    strlen($obj['colourweb_hexa']) != 6 ? "ffffff" : $obj['colourweb_hexa'],
    "text_color" =>  (string)    strlen($obj['textcolourweb_hexa']) != 6 ? "000000" : $obj['textcolourweb_hexa'],
);
if ($lines_data[$obj['id_line']]['mode'] == "rail" || $lines_data[$obj['id_line']]['mode'] == "nationalrail") {
    // Si c'est du ferré, l'affichage est different
    $lines_data[$obj['id_line']]['departures'] = [];
    $departures_lines[] = $obj['id_line'];
} else {
    // Affichage normal
    $lines_data[$obj['id_line']]['terminus_schedules'] = [];
}

// ------------

if ($provider == 'SNCF') {
    include('back/schedules_line_sncf.php');
} else if ($provider == 'IDFM') {
    include('back/schedules_line_idfm.php');
}

$echo = json_encode($json);
file_put_contents($fichier, $echo);
echo $echo;
exit;
