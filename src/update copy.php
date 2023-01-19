<?php

    include_once ('base/main.php');
    include_once ('base/function.php');
    include_once ('base/request.php');
    include_once ('base/gtfs_request.php');

    $dossier = '../data/file/';

echo 'Clearing cache...' . PHP_EOL;

    $fichier = '../data/cache/';
    clear_directory($fichier);

    $fichier = '../data/report/';
    clear_directory($fichier);

    $fichier = '../data/file/';
    clear_directory($fichier);

echo 'Clearing cache ✅' . PHP_EOL;

// use the curl function to get json data from https://transport.data.gouv.fr/api/datasets

$json = curl('https://transport.data.gouv.fr/api/datasets');

// then read json data and for each dataset, if there is an ressources with type is 'GTFS', display it

    $json = json_decode($json);
    foreach($json as $dataset) {
        foreach($dataset as $resources) {
            if($resources->format == 'GTFS') {
                echo 'Dataset: '. $dataset->title. PHP_EOL;
                echo 'Format: '. $resources->format. PHP_EOL;
                echo 'URL: '. $resources->url. PHP_EOL;
            }
        }
    }

?>