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
$type = 1; // Area

if ($search_type == 3){
    $result = getStopByQueryAndGeoCoords($type, $query, $lat, $lon, $_GET['distance']);

} else if ($search_type == 2){
    $result = getStopByGeoCoords($type, $lat, $lon, $_GET['distance']);

} else if ($search_type == 1){
    $result = getStopByQuery($type, $query);

} else {
    ErrorMessage(500);
}

$places = [];
while ($obj = $result->fetch()) {
    $places[] = array(
        'id'        =>  (String)    $obj['stop_id'],
        'name'      =>  (String)    $obj['stop_name'],
        'type'      =>  (String)    $LOCATION_TYPE[$obj['location_type']],
        'quality'   =>  (int)       0 ?? 0,
        'distance'  =>  (int)       0 ?? 0,
        'zone'      =>  (int)       $obj['zone_id'] ?? 0,
        'town'      =>  (String)    '',
        'zip_code'  =>  (String)    '',
        'coord'     => array(
            'lat'       =>      $obj['stop_lat'],
            'lon'       =>      $obj['stop_lon'],
        ),
        'lines'     => array(),
        'modes'     => array(),
    );
}

echo json_encode($places);
exit;
?>