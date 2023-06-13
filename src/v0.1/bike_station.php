<?php

$dir = '../data/cache/bike_';

// ---------------
$parameters = ['id'];
$message = checkRequiredParameter($parameters);

if ($message) {
    ErrorMessage(400, $message);
}
// ---------------

$id = $_GET['id'];
$file = $dir . $id . '.json';

if (is_file($file) && filesize($file) > 5 && (time() - filemtime($file) < 20)) {
    echo file_get_contents($file);
    exit;
}

// ------------
// On récupère toutes les lignes a l'arrets

$request = getStationById($id);

if ($request->rowCount() == 0) {
    ErrorMessage(200, "Nothing where found for this stop");
}

$obj = $request->fetch();

$json = array(
    'name'     => $obj['station_name'],
    'coord' => array(
        'lat'      => $obj['station_lat'],
        'lon'      => $obj['station_lon'],
    ),
    'capacity' => (int) $obj['station_capacity'],
);

$content = file_get_contents($obj['provider_id'] . 'station_status.json');
$content = json_decode($content);

$sid = substr($id, strpos($id, ':') + 1);

foreach ($content->data->stations as $station) {
    if ($station->station_id == $sid) {

        if (isset($station->num_bikes_available_types)) {
            foreach ($station->num_bikes_available_types as $types) {
                foreach ($types as $key => $nb) {
                    $json[$key] = $nb;
                }
            }
        } else if (isset($station->num_bikes_available)) {
            $json['bike'] = $station->num_bikes_available;
        }

        break;
    }
}

$echo['station'] = $json;

$echo = json_encode($echo);
file_put_contents($file, $echo);
echo $echo;
exit;
