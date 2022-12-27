<?php

    include_once ('base/main.php');
    include_once ('base/function.php');
    include_once ('base/request.php');

    $dossier = '../data/file/';

echo 'Clearing cache...' . PHP_EOL;

    $fichier = '../data/cache/';
    clear_directory($fichier);

    $fichier = '../data/report/';
    clear_directory($fichier);

echo 'Clearing cache ✅' . PHP_EOL;
echo 'Updating tables...' . PHP_EOL;


// Looking for GTFS...

echo '   Looking for GTFS...' . PHP_EOL;
    $gtfs = curl_GTFS('https://data.iledefrance-mobilites.fr/explore/dataset/offre-horaires-tc-gtfs-idfm/download/?format=csv&timezone=Europe/Berlin&lang=fr&csv_separator=%3B');
    file_put_contents($dossier . 'gtfs.csv', $gtfs);
    $gtfs = read_csv($dossier . 'gtfs.csv');
    
    foreach ($gtfs as $row) {
        if ($row[1] != 'url' && $row[1] != '' && $row[1] != false) {
            echo '   Fetching GTFS...' . PHP_EOL;
            echo $row[1] . PHP_EOL;
                $zip = file_get_contents($row[1]);
                file_put_contents($dossier . 'gtfs.zip', $zip);
            echo '   Fetching GTFS ✅' . PHP_EOL;
        } 
    }

    // Unzip
    echo '   Unzip GTFS...' . PHP_EOL;
    $zip = new ZipArchive;
    if ( $zip->open($dossier . 'gtfs.zip') ) {
        $zip->extractTo($dossier . 'gtfs/');
        $zip->close();
        echo '   Unzip GTFS ✅' . PHP_EOL;
    } else {
        echo '   Unzip GTFS ⚠️' . PHP_EOL;
    }

echo '   Truncate GTFS ...' . PHP_EOL;
    clearGTFS();
echo '   Truncate GTFS ✅' . PHP_EOL;

echo '   Write GTFS...' . PHP_EOL;
    writeGTFS();
echo '   Write GTFS ✅' . PHP_EOL;

echo '   GTFS ✅' . PHP_EOL;


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

echo '   Write lignes.csv...' . PHP_EOL;
    writeLignes ($dossier . 'lignes.csv');
echo '   Write lignes.csv ✅' . PHP_EOL;

echo '   Write arrets_lignes.csv...' . PHP_EOL;
    writeArretsLignes ($dossier . 'arrets_lignes.csv');
echo '   Write arrets_lignes.csv ✅' . PHP_EOL;

echo '   Write ✅' . PHP_EOL;
echo 'Updating tables ✅' . PHP_EOL;

?>