<?php

$fichier = '../data/cache/near_';

if (isset($_GET['lat']) && isset($_GET['lon']) && isset($_GET['z'])) {
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];
    $zoom = $_GET['z'];

    $fichier .= $lat . '_' . $lon . '.json';
} else {
    ErrorMessage(
        400,
        'Required parameter "z" or "lat" and "lon" is missing or null.'
    );
}

// ------ Request
//
$request = getStopByGeoCoords($lat, $lon, $zoom);

// ------ Arrets
//

$stops = [];
while ($obj = $request->fetch()) {

    if (($zoom >= 15000 && getTransportMode($obj['route_type']) == 'rail') || $zoom < 15000) {

        if (!isset($stops[$obj['stop_id']])) {
            $stops[$obj['stop_id']] = array(
                'id'        =>  (string)    $obj['stop_id'],
                'name'      =>  (string)    $obj['stop_name'],
                'type'      =>  (string)    'stop_area',
                'distance'  =>  (int)       isset($obj['distance']) ? $obj['distance'] < 1000 ? round($obj['distance']) : 0 : 0,
                'town'      =>  (string)    $obj['town_name'],
                'zip_code'  =>  (string)    isset($obj['town_id']) ? substr($obj['town_id'], 0, 2) : '',
                'coord'     => array(
                    'lat'       =>      floatval($obj['stop_lat']),
                    'lon'       =>      floatval($obj['stop_lon']),
                ),
                'lines'     => array(),
                'modes'     => array(),
            );
            $lines[$obj['stop_id']] = [];
            $modes[$obj['stop_id']] = [];
        }

        if (!in_array(getTransportMode($obj['route_type']), $lines[$obj['stop_id']])) {
            $lines[$obj['stop_id']][] = array(
                "id"         =>  (string)    idfm_format($obj['route_id']),
                "code"       =>  (string)    $obj['route_short_name'],
                "name"       =>  (string)    $obj['route_long_name'],
                "mode"       =>  (string)    getTransportMode($obj['route_type']),
                "color"      =>  (string)    strlen($obj['route_color']) < 6 ? "ffffff" : $obj['route_color'],
                "text_color" =>  (string)    strlen($obj['route_text_color']) < 6 ? "000000" : $obj['route_text_color'],
            );
        }

        if (!in_array(getTransportMode($obj['route_type']), $modes[$obj['stop_id']])) {
            $modes[$obj['stop_id']][] = getTransportMode($obj['route_type']);
        }
    }
}

$json = [];
foreach ($stops as $key => $place) {
    usort($lines[$key], "order_line");
    $place['lines'] = $lines[$key];
    $place['modes'] = $modes[$key];
    $json[] = $place;
}

$echo["stops"] = $json;

// ------ Velo
if ($zoom <= 3000) {
    $request = getStationByGeoCoords($lat, $lon, 5000);

    $stations = [];
    while ($obj = $request->fetch()) {

        $stations[] = array(
            'id'        =>  (string)    $obj['station_id'],
            'name'      =>  (string)    $obj['station_name'],
            'capacity'  =>  (string)    $obj['station_capacity'],
            'coord'     => array(
                'lat'       =>      floatval($obj['station_lat']),
                'lon'       =>      floatval($obj['station_lon']),
            ),
        );
    }

    $echo["bike"] = $stations;
}



if (isset($_GET['flag'])) {
    $echo["flag"] = (int) $_GET['flag'];
}


$echo = json_encode($echo);
// file_put_contents($fichier, $echo);
echo $echo;
exit;
