<?php

    include_once ('base/main.php');
    include_once ('base/function.php');
    include_once ('base/request.php');
    
    mkdir('../data/cache/journeys', 0777, true);
    mkdir('../data/cache/places', 0777, true);
    mkdir('../data/cache/schedules', 0777, true);
    mkdir('../data/cache/schedules_line', 0777, true);
    mkdir('../data/cache/stop_area', 0777, true);
    mkdir('../data/cache/stop_point', 0777, true);
    
    mkdir('../data/file/gtfs', 0777, true);

    mkdir('../data/report', 0777, true);

    $dossier = '../data/file/';

echo 'Add default values...' . PHP_EOL;

echo '   Fetching communes.geojson...' . PHP_EOL;
    $communes = file_get_contents('https://github.com/gregoiredavid/france-geojson/raw/master/communes.geojson');
    file_put_contents($dossier . 'communes.geojson', $communes);
echo '   Fetching communes.geojson ✅' . PHP_EOL;

echo '   Fetching laposte_hexasmal.csv...' . PHP_EOL;
    $laposte = file_get_contents('https://datanova.laposte.fr/explore/dataset/laposte_hexasmal/download/?format=csv&timezone=Europe/Berlin&lang=fr&use_labels_for_header=true&csv_separator=%3B');
    file_put_contents($dossier . 'laposte_hexasmal.csv', $laposte);
echo '   Fetching laposte_hexasmal.csv ✅' . PHP_EOL;

echo '   Truncate Tables ...' . PHP_EOL;
    clearLaPoste();
echo '   Truncate Tables ✅' . PHP_EOL;

echo '   Write communes.geojson...' . PHP_EOL;
    include("../data/import_geojson.php");
echo '   Write communes.geojson ✅' . PHP_EOL;

echo '   Write laposte_hexasmal.csv...' . PHP_EOL;
    writeLaPoste ($dossier . 'laposte_hexasmal.csv');
echo '   Write laposte_hexasmal.csv ✅' . PHP_EOL;

echo '   Write ✅' . PHP_EOL;
echo 'Updating tables ✅' . PHP_EOL;

include('update.php');

?>