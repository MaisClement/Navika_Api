<?php

include('base/main.php');
include('base/function.php');
include('base/request.php');

$dossier = '../data/file/';

echo 'Clearing cache...' . PHP_EOL;

$fichier = '../data/cache/';
clear_directory($fichier);

$fichier = '../data/report/';
clear_directory($fichier);

echo 'Clearing cache ✅' . PHP_EOL;
echo 'Updating tables...' . PHP_EOL;
echo '   Fetching arrets_lignes.csv...' . PHP_EOL;

$arrets_lignes = file_get_contents('https://data.iledefrance-mobilites.fr/explore/dataset/arrets-lignes/download/?format=csv&timezone=Europe/Berlin&lang=fr&use_labels_for_header=true&csv_separator=%3B');
file_put_contents($dossier . 'arrets_lignes.csv', $arrets_lignes);

echo '   Fetching arrets_lignes.csv ✅' . PHP_EOL;
echo '   Fetching lignes.csv...' . PHP_EOL;

$lignes = file_get_contents('https://data.iledefrance-mobilites.fr/explore/dataset/referentiel-des-lignes/download/?format=csv&timezone=Europe/Berlin&lang=fr&use_labels_for_header=true&csv_separator=%3B');
file_put_contents($dossier . 'lignes.csv', $lignes);

echo '   Fetching lignes.csv ✅' . PHP_EOL;
echo '   Truncate Tables ...' . PHP_EOL;

clearLignes ();
clearArretsLignes();

echo '   Truncate Tables ✅' . PHP_EOL;
echo '   Write...' . PHP_EOL;

writeLignes ($dossier . 'lignes.csv');
writeArretsLignes ($dossier . 'arrets_lignes.csv');

echo '   Write ✅' . PHP_EOL;
echo 'Updating tables ✅' . PHP_EOL;

?>