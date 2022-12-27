<?php

$fichier = '../data/cache/places/';

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

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 60 * 60)) {
    echo file_get_contents($fichier);
    exit;
}

// ------ Request
//
$type = 1; // Area

if ($search_type == 3){
    $request = getStopByQueryAndGeoCoords($type, $query, $lat, $lon);

} else if ($search_type == 2){
    $request = getStopByGeoCoords($type, $lat, $lon);

} else if ($search_type == 1){
    $request = getStopByQuery($query);

} else {
    ErrorMessage(500);
}

// ------ Ville et code postal
//

$places = [];
while ($obj = $request->fetch()) {
    
    $lines = [];
    $modes = [];

    // if ($search_type != 2){ // On récupere les lignes a l'arret
    //     $request_line = getAllLinesAtStop ($obj['stop_id']);

    //     while($obj_line = $request_line->fetch()) {
    //         $lines[] = array(
    //             "id"         =>  (String)    idfm_format( $obj_line['id_line'] ),
    //             "code"       =>  (String)    $obj_line['shortname_line'],
    //             "name"       =>  (String)    $obj_line['name_line'],
    //             "mode"       =>  (String)    $obj_line['transportmode'],
    //             "color"      =>  (String)    strlen($obj_line['colourweb_hexa']) < 6 ? "000000" : $obj_line['colourweb_hexa'],
    //             "text_color" =>  (String)    strlen($obj_line['textcolourweb_hexa']) < 6 ? "000000" : $obj_line['textcolourweb_hexa'],
    //         );
    //         $modes[] = $obj_line['transportmode'];
    //     }
    // }
    
    $places[] = array(
        'id'        =>  (String)    $obj['stop_id'],
        'name'      =>  (String)    $obj['stop_name'],
        'type'      =>  (String)    'stop_area', // $LOCATION_TYPE[$obj['location_type']],
        'quality'   =>  (int)       0,
        'distance'  =>  (int)       0,
        'zone'      =>  (int)       $obj['zone_id'] ?? 0,
        'town'      =>  (String)    $obj['town'],
        'zip_code'  =>  (String)    substr($obj['zip_code'], 0, 2),
        'coord'     => array(
            'lat'       =>      floatval( $obj['stop_lat'] ),
            'lon'       =>      floatval( $obj['stop_lon'] ),
        ),
        'lines'     => $lines,
        'modes'     => $modes,
    );
}

$echo["places"] = $places;

$echo = json_encode($echo);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>