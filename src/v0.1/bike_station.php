<?php

$dossier = '../data/cache/schedules/';

if (!isset($_GET['s']) || $_GET['s'] == null){
    ErrorMessage( 400, 'Required parameter "s" is missing or null.' );
} else {
    $id = $_GET['s'];
    $fichier = $dossier . $id . '.json';
}

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 20)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------
// On récupère toutes les lignes a l'arrets

$request = getStationById ($id);
$obj = $request->fetch();

$json = array(  
    'id'       => $obj['station_id'],      
    'name'     => $obj['station_name'],    
    'capacity' => $obj['station_capacity'],
    'coord' => array(
        'lat'      => $obj['station_lat'],    
        'lon'      => $obj['station_lon'],
    )
);

$content = file_get_contents( $obj['provider_id'] . 'station_status.json' );
$content = json_decode( $content );

foreach($content->data->stations as $station) {
    if ($station->station_id == $id) {
        
        foreach($station->num_bikes_available_types as $types) {
            foreach ($types as $key => $nb) {
                $json[$key] = $nb;
            }
        }
        break;
    }
}

$echo['station'] = $json;

$echo = json_encode($echo);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>