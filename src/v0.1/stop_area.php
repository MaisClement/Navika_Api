<?php

$file = '../data/cache/stop_area_';

if (isset($_GET['q']) && isset($_GET['lat']) && isset($_GET['lon'])) {
    $query = $_GET['q'];
    $query = urlencode(trim($query));
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];

    $file .= $query . '_' . $lat . '_' . $lon . '.json';

    $search_type = 3;
} else if (isset($_GET['lat']) && isset($_GET['lon'])) {
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];

    $file .= $lat . '_' . $lon . '.json';

    $search_type = 2;
} else if (isset($_GET['q'])) {
    $query = $_GET['q'];
    $query = urlencode(trim($query));

    $file .= $query . '.json';

    $search_type = 1;
} else {
    ErrorMessage(
        400,
        'Required parameter "q" or "lat" and "lon" is missing or null.'
    );
}

if (is_file($file) && filesize($file) > 5 && (time() - filemtime($file) < 60 * 60)) {
    echo file_get_contents($file);
    exit;
}

// ------ Request
//
$type = 1; // Area

if ($search_type == 3) {
    $request = getStopByQueryAndGeoCoords($query, $lat, $lon);
} else if ($search_type == 2) {
    $request = getStopByGeoCoords($lat, $lon, 5000);
} else if ($search_type == 1) {
    $request = getStopByQuery($query);
} else {
    ErrorMessage(500);
}

// ------ Ville et code postal
//

$places = [];
while ($obj = $request->fetch()) {

    // mode filter
    $filter = true;
    if (isset($_GET['mode'])) {
        $filter = false;
        if (is_array($_GET['mode']) && in_array(getTransportMode($obj['route_type']), $_GET['mode']))
            $filter = true;
        if (getTransportMode($obj['route_type']) == $_GET['mode'])
            $filter = true;
    }

    if ($filter) {
        if (!isset($places[$obj['stop_id']])) {
            $places[$obj['stop_id']] = array(
                'id'        =>  (string)    $obj['stop_id'],
                'name'      =>  (string)    $obj['stop_name'],
                'type'      =>  (string)    'stop_area',
                'distance'  =>  (int)       isset($obj['distance']) ? $obj['distance'] < 1000 ? round($obj['distance']) : 0 : 0,
                'town'      =>  (string)    $obj['town_name'],
                'zip_code'  =>  (string)    substr($obj['town_id'], 0, 2),
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
                "id"         =>  (string)    $obj['route_id'],
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
foreach ($places as $key => $place) {
    usort($lines[$key], "order_line");
    $place['lines'] = $lines[$key];
    $place['modes'] = $modes[$key];
    $json[] = $place;
}

if ($search_type != 2) {
    usort($json, "order_places");
}

$echo["places"] = array_slice($json, 0, 15);

if (isset($_GET['flag'])) {
    $echo["flag"] = (int) $_GET['flag'];
}


$echo = json_encode($echo);
file_put_contents($file, $echo);
echo $echo;
exit;
