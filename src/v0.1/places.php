<?php

$fichier = '../data/cache/places/';

if ( isset($_GET['q']) && isset($_GET['lat']) && isset($_GET['lon']) ){
    $query = $_GET['q'];
    $query = urlencode( trim($query) );
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];

    $url = $BASE_URL . '/places?q=' . $query . '&from' . $lon .';' . $lat . '=&depth=2';
    $fichier .= 'PRIM_' . $query . '_' . $lat . '_' . $lon . '.json';

    $search_type = 3;

} else if ( isset($_GET['lat']) && isset($_GET['lon']) ) {
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];

    $url = $BASE_URL . '/coord/' . $lon .';' . $lat . '/places_nearby?depth=2&distance=1000';
    $fichier .= 'PRIM_' . $lat . '_' . $lon . '.json';

    $search_type = 2;

} else if (isset($_GET['q'])){
    $query = $_GET['q'];
    $query = urlencode( trim($query) );

    $url = $BASE_URL . '/places?q=' . $query . '&depth=2';
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

// ------------

$results = curl_Navitia($url);
$results = json_decode($results);

if ($search_type == 3){
    $results = $results->places;

} else if ($search_type == 2){
    $results = $results->places_nearby;

} else if ($search_type == 1){
    $results = $results->places;

} else {
    ErrorMessage(500);
}

$places = [];
foreach($results as $result){
    $places[] = array(
        "id"        =>  (String)    $result->id,
        "name"      =>  (String)    $result->{$result->embedded_type}->name,
        "type"      =>  (String)    $result->embedded_type,
        "quality"   =>  (int)       0,
        "distance"  =>  (int)       isset($result->distance) ? $result->distance : 0,
        "zone"      =>  (int)       0,
        "town"      =>  (String)    getTownByAdministrativeRegions( $result->{$result->embedded_type}->administrative_regions ),
        "zip_code"  =>  (String)    getZipCodeByInsee( getZipByAdministrativeRegions( $result->{$result->embedded_type}->administrative_regions ) )->fetch()['zip_code'],
        "coord"     => array(
            "lat"       =>  floatval( $result->{$result->embedded_type}->coord->lat ),
            "lon"       =>  floatval( $result->{$result->embedded_type}->coord->lon ),
        ),
        "lines"     =>              isset( $result->{$result->embedded_type}->lines ) ? getAllLines( $result->{$result->embedded_type}->lines ) : [],
        "modes"     =>              isset( $result->stop_area->physical_modes ) ? getPhysicalModes( $result->stop_area->physical_modes ) : [],
    );
}

$echo["places"] = $places;

$echo = json_encode($echo);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>