<?php

$dossier = '../data/cache/schedules/';

if (!isset($_GET['s']) || $_GET['s'] == null) {
    ErrorMessage(400, 'Required parameter "s" is missing or null.');
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

$id = idfm_format($stop_id);

// ------------

$prim_url = 'https://prim.iledefrance-mobilites.fr/marketplace/stop-monitoring?MonitoringRef=STIF:StopPoint:Q:' . $id . ':';
$sncf_url = 'https://garesetconnexions-online.azure-api.net/API/PIV/Departures/00' . $id;
$sncf_url_api = 'https://api.sncf.com/v1/coverage/sncf/stop_areas/stop_area:SNCF:' . $id . '/departures?count=30&data_freshness=realtime';
$fichier = $dossier . $id . '.json';


// ------------
// Si un fichier cache existe
if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 20)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------
// On récupère toutes les lignes a l'arrets
$request = getAllLinesAtStop($provider . ':' . $id);
$departures_lines = [];

while ($obj = $request->fetch()) {
    $line_id = $obj['id_line'];

    if ($obj['shortname_line'] == 'TER') {
        $line_id = 'TER';
        $lines_data['TER'] = array(
            "id"         =>  (string)    $line_id,
            "code"       =>  (string)    'TER',
            "name"       =>  (string)    'TER',
            "mode"       =>  (string)    'rail',
            "color"      =>  (string)    "000000",
            "text_color" =>  (string)    "aaaaaa",
        );
    } else {
        $lines_data[$line_id] = array(
            "id"         =>  (string)    $line_id,
            "code"       =>  (string)    $obj['shortname_line'],
            "name"       =>  (string)    $obj['name_line'],
            "mode"       =>  (string)    $obj['transportmode'],
            "color"      =>  (string)    strlen($obj['colourweb_hexa']) != 6 ? "ffffff" : $obj['colourweb_hexa'],
            "text_color" =>  (string)    strlen($obj['textcolourweb_hexa']) != 6 ? "000000" : $obj['textcolourweb_hexa'],
        );
    }

    if ($lines_data[$line_id]['mode'] == "rail" || $lines_data[$line_id]['mode'] == "nationalrail") {
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
// On utilise l'api (differe selon le provider)

if ($provider == 'SNCF') {
    include('back/schedules_sncf.php');
} else if ($provider == 'IDFM') {
    include('back/schedules_idfm.php');
}

// ------------
// Après récupération des données via l'api, on rajoute les departs inexistant via le gtfs
for ($i = 0; $i < count($json['schedules']); $i++) { 
    $line = $json['schedules'][$i];

    $y = 0;
    $terminus = [];
    if (count($line['terminus_schedules']) == 0){
        $line = $provider .':'. $line['id'];
        
        $request = getScheduleByStop($stop_id, $line, date("Y-m-d"), date("G:i:s"));

        if ($request->rowCount() > 0) {
            while ($obj = $request->fetch()) {
                
                if (!isset($json['schedules'][$i]['terminus_schedules'][$terminus[$obj['trip_headsign']]])) {
                    $json['schedules'][$i]['terminus_schedules'][count($terminus)] = array(
                        "id"         =>  (string)    '',
                        "name"       =>  (string)    gare_format($obj['trip_headsign']),
                        "schedules"  =>  array()
                    );
                    $terminus[$obj['trip_headsign']] = count($terminus);
                }              

                $json['schedules'][$i]['terminus_schedules'][$terminus[$obj['trip_headsign']]]['schedules'][] = array(
                    "base_departure_date_time"  =>  (string)  prepareTime($obj['departure_time']),
                    "departure_date_time"       =>  (string)  prepareTime($obj['departure_time']),
                    "base_arrival_date_time"    =>  (string)  prepareTime($obj['arrival_time']),
                    "arrival_date_time"         =>  (string)  prepareTime($obj['arrival_time']),
                    "state"                     =>  (string)  "theorical",
                    "atStop"                    =>  (string)  "false",
                    "platform"                  =>  (string)  "-"
                );
            }
        }
    }
}

$echo = json_encode($json);
file_put_contents($fichier, $echo);
echo $echo;
exit;
