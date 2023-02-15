<?php

$dossier = '../data/cache/vehicle_';

if (!isset($_GET['v']) || $_GET['v'] == null) {
    ErrorMessage(400, 'Required parameter "v" is missing or null.');
} else {
    $vehicle_id = $_GET['v'];

    // ------------
    if (str_contains($vehicle_id, 'SNCF:')) {
        $provider = 'SNCF';
    } else if (str_contains($vehicle_id, 'IDFM:')) {
        $provider = 'IDFM';
        ErrorMessage(501, 'Provider not implented');
    } else {
        ErrorMessage(400, 'Invalid data, provider not recognized');
    }

    if (strpos($vehicle_id, ':RealTime')) {
        $vehicle_id = substr($vehicle_id, 0, strpos($vehicle_id, ':RealTime'));
    }

    // $prim_url = 'https://prim.iledefrance-mobilites.fr/marketplace/stop-monitoring?MonitoringRef=STIF:' . $type . ':Q:' . $vehicle_id . ':';
    $sncf_url = 'https://api.sncf.com/v1/coverage/sncf/vehicle_journeys/' . $vehicle_id;
    $fichier = $dossier . $vehicle_id . '.json';
}

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 20)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------

if ($provider == 'SNCF') {
    include('back/vehicle_journey_sncf.php');
} else if ($provider == 'IDFM') {
    include('back/schedules_idfm.php');
}

$echo = json_encode($json);
file_put_contents($fichier, $echo);
echo $echo;
exit;
