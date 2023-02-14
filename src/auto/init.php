<?php

chdir('/var/www/navika/src');

include_once ('base/main.php');

// INIT SQL 
echo '> Init Database'. PHP_EOL;
$query = file_get_contents('../data/sql/SQL.sql');
SQLinit($query);

// Import GeoJson

echo '> GeoJson'. PHP_EOL;
clearTown();

$geojson = file_get_contents('../data/file/communes.geojson');
$geojson = json_decode($geojson);

foreach($geojson->features as $feature){
    $name = $feature->properties->nom;
    $id = $feature->properties->code;
    $coordinates = $feature->geometry->coordinates;

    $polygon_text = 'POLYGON((';

    $i = 0;
    foreach($coordinates[0] as $coordinate){
        $polygon_text .= $coordinate[1] . ' ' . $coordinate[0];
        if ((count($coordinates[0]) -1 ) > ($i)){
            $polygon_text .= ',';
        }
        $i++;
    }
    $polygon_text .= '))';

    try {
        addTown($id, $name, $polygon_text);
    } catch (Exception $e) {
        // echo $e;
    }
}                  

?>