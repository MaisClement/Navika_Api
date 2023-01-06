<?php

$fichier = '../data/cache/stop_area/';

if ( isset($_GET['q']) && isset($_GET['lat']) && isset($_GET['lon']) ){
    $query = $_GET['q'];
    $query = urlencode( trim($query) );
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];
    
    $fichier .= 'PRIM_' . $query . '_' . $lat . '_' . $lon . '.json';

    $search_type = 3;

} else if ( isset($_GET['lat']) && isset($_GET['lon']) ) {
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];
    
    $fichier .= 'PRIM_' . $lat . '_' . $lon . '.json';

    $search_type = 2;

} else if (isset($_GET['q'])){
    $query = $_GET['q'];
    $query = urlencode( trim($query) );

    $fichier .= 'PRIM_' . $query . '.json';

    $search_type = 1;

} else {
    ErrorMessage(
        400,
        'Required parameter "q" or "lat" and "lon" is missing or null.'
    );
}

// if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 60 * 60)) {
//     echo file_get_contents($fichier);
//     exit;
// }

// ------ Request
//
$type = 1; // Area

if ($search_type == 3){
    $request = getStopByQueryAndGeoCoords($query, $lat, $lon);

} else if ($search_type == 2){
    $request = getStopByGeoCoords($lat, $lon, 5000);

} else if ($search_type == 1){
    $request = getStopByQuery($query);

} else {
    ErrorMessage(500);
}

// ------ Ville et code postal
//

$places = [];
while ($obj = $request->fetch()) {

    if (!isset( $places[$obj['stop_id']] )) {
        $places[$obj['stop_id']] = array(
            'id'        =>  (String)    $obj['stop_id'],
            'name'      =>  (String)    $obj['stop_name'],
            'type'      =>  (String)    'stop_area',
            'distance'  =>  (int)       isset($obj['distance']) ? $obj['distance'] < 1000 ? round($obj['distance']) : 0 : 0,
            // 'zone'      =>  (int)       $obj['zone_id'] ?? 0,
            'town'      =>  (String)    $obj['town'],
            'zip_code'  =>  (String)    substr($obj['zip_code'], 0, 2),
            'coord'     => array(
                'lat'       =>      floatval( $obj['stop_lat'] ),
                'lon'       =>      floatval( $obj['stop_lon'] ),
            ),
            'lines'     => array(),
            'modes'     => array(),
        );
        $lines[$obj['stop_id']] = [];
        $modes[$obj['stop_id']] = [];
    }

    if (!in_array($obj['transportmode'], $lines[$obj['stop_id']] )) {
        $lines[$obj['stop_id']][] = array(
            "id"         =>  (String)    idfm_format( $obj['id_line'] ),
            "code"       =>  (String)    $obj['shortname_line'],
            "name"       =>  (String)    $obj['name_line'],
            "mode"       =>  (String)    $obj['transportmode'],
            "color"      =>  (String)    strlen($obj['colourweb_hexa']) < 6 ? "000000" : $obj['colourweb_hexa'],
            "text_color" =>  (String)    strlen($obj['textcolourweb_hexa']) < 6 ? "000000" : $obj['textcolourweb_hexa'],
        );
    }
    
    if (!in_array($obj['transportmode'], $modes[$obj['stop_id']] )) {
        $modes[$obj['stop_id']][] = $obj['transportmode'];
    }    
    
}

$json = [];
foreach($places as $key => $place) {
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
// file_put_contents($fichier, $echo);
echo $echo;
exit;

?>