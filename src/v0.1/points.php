<?php

$fichier = '../data/cache/points/';
$type = 'stop_point';

if ( isset($_GET['lat']) && isset($_GET['lon']) ) {
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];

    $url = $BASE_URL . '/coord/' . $lon .';' . $lat . '/places_nearby?type[]=' . $type . '&distance=1000';
    $fichier .= 'PRIM_' . $lat . '_' . $lon . '.json';

    $search_type = 2;

} else {
    ErrorMessage(
        400,
        'Required parameter "lat" and "lon" is missing or null.'
    );
}

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 60 * 60)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------

$results = curl_PRIM($url);
$results = json_decode($results);

$results = $results->places_nearby;

$places = [];
foreach($results as $result){

    $places[] = array(
        "id"        =>  (String)    $result->id,
        "name"      =>  (String)    $result->stop_point->name,
        "type"      =>  (String)    "stop_point",
        "quality"   =>  (int)       0,
        "distance"  =>  (int)       $result->distance ?? 0,
        "zone"      =>  (int)       0,
        "town"      =>  (String)    getTownByAdministrativeRegions( $result->stop_point->administrative_regions ),
        "zip_code"  =>  (String)    getZipByAdministrativeRegions( $result->stop_point->administrative_regions ),
        "coord"     => array(
            "lat"       =>  floatval( $result->stop_point->coord->lat ),
            "lon"       =>  floatval( $result->stop_point->coord->lon ),
        ),
        "lines"     =>              getAllLines( $result->stop_point->lines ),
        "modes"     =>              getPhysicalModes( $result->stop_point->physical_modes ),
    );
}

$echo["points"] = $places;

$echo = json_encode($echo);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>