<?php

$dir = '../data/cache/vehicle_';

// ---------------
$parameters = ['id'];
$message = checkRequiredParameter($parameters);

if ($message) {
    ErrorMessage(400, $message);
}
// ---------------

$vehicle_id = $_GET['id'];

// ---------------
if (str_contains($vehicle_id, 'SNCF:')) {
    $provider = 'SNCF';
} else {
    ErrorMessage(501, 'Provider not implented');
}

if (strpos($vehicle_id, ':RealTime')) {
    $vehicle_id = substr($vehicle_id, 0, strpos($vehicle_id, ':RealTime'));
}

$sncf_url = 'https://api.sncf.com/v1/coverage/sncf/vehicle_journeys/' . $vehicle_id;

// ---------------

if ($provider == 'SNCF') {
    include('back/vehicle_journey_sncf.php');
}

$echo = json_encode($json);
echo $echo;