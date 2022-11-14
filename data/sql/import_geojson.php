<?php

include '../base/main.php';
include '../base/request.php';

$geojson = file_get_contents('communes.geojson');
$geojson = json_decode($geojson);

clearTown();

$len = count($geojson->features);
$y = 0;
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
        echo 'pas content :(';
    }

    $y++;
    echo $y . ' / ' . $len . PHP_EOL;
}

?>