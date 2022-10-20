<?php

// ------ Parameters
// q
// lat
// lon
// distance

if ( isset($_GET['q']) && isset($_GET['lat']) && isset($_GET['lon']) ){
    $query = $_GET['q'];
    $latitude = $_GET['lat'];
    $longitude = $_GET['lon'];

    $search_type = 3;

} else if ( isset($_GET['lat']) && isset($_GET['lon']) ) {
    $latitude = $_GET['lat'];
    $longitude = $_GET['lon'];

    $search_type = 2;

} else if (isset($_GET['q'])){
    $query = $_GET['q'];
    
    $search_type = 1;

} else {
    ErrorMessage(
        400,
        'Required parameter "q" or "lat" and "lon" is missing or null.'
    );
}

// ------ Request
//

if ($search_type == 3){
    $result = getStopByQueryAndGeoCoords($query, $lat, $lon, $_GET['distance']);

} else if ($search_type == 2){
    $result = getStopByQueryAndGeoCoords($query, $lat, $lon, $_GET['distance']);

} else if ($search_type == 1){
    $result = getStopByQueryAndGeoCoords($query, $lat, $lon, $_GET['distance']);

} else {
    ErrorMessage(500);
}

$places = [];

while ($obj = $result->fetch()) {
    $places[] = array(
        'id'        =>  (String)    $obj['stop_id'],
        'name'      =>  (String)    $obj['stop_name'],
        'type'      =>  (String)    '',
        'quality'   =>  (int)       0 ?? 0,
        'distance'  =>  (int)       0 ?? 0,
        'zone'      =>  (int)       0 ?? 0,
        'town'      =>  (String)    '',
        'zip_code'  =>  (String)    '',
        'coord'     => array(
            'lat'       =>      '',
            'lon'       =>      '',
        ),
        'lines'     => array(),
        'modes'     => array(),
    );
}

echo json_encode($places);
exit;
?>