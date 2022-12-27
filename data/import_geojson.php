<?php

echo "   GeoJson Import, let's start !" . PHP_EOL;
echo '      Tuncate GeoJson...' . PHP_EOL;

clearTown();

echo '      Tuncate GeoJson ✅' . PHP_EOL;
echo '      Read data...' . PHP_EOL;

$geojson = file_get_contents($dossier . 'communes.geojson');
$geojson = json_decode($geojson);

echo '      Import data...' . PHP_EOL;

$len = count($geojson->features);
$y = 0;
$p = 0;
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
        // echo 'pas content :(';
    }

    $y++;
    if ($p != round(($y / $len) * 10)) {
        $p  = round(($y / $len) * 10);
        echo '         ' . ($p * 10) . '%' . PHP_EOL;
    }
}

echo '   Import GeoJson ✅' . PHP_EOL;

?>