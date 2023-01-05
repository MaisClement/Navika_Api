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

    $fichier = '../data/file/';
    clear_directory($fichier);

echo 'Clearing cache ✅' . PHP_EOL;
echo 'Updating tables...' . PHP_EOL;

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
echo '   Unzip GTFS...' . PHP_EOL;
    $zip = new ZipArchive;
    if ( $zip->open($dossier . 'gtfs.zip') ) {
        $zip->extractTo($dossier . 'gtfs/');
        $zip->close();
        echo '   Unzip GTFS ✅' . PHP_EOL;
    } else {
        echo '   Unzip GTFS ⚠️' . PHP_EOL;
    }

echo '   GTFS ✅' . PHP_EOL;

echo '   Fetching arrets_lignes.csv...' . PHP_EOL;
    $arrets_lignes = file_get_contents('https://data.iledefrance-mobilites.fr/explore/dataset/arrets-lignes/download/?format=csv&timezone=Europe/Berlin&lang=fr&use_labels_for_header=true&csv_separator=%3B');
    file_put_contents($dossier . 'arrets_lignes.csv', $arrets_lignes);
echo '   Fetching arrets_lignes.csv ✅' . PHP_EOL;

echo '   Fetching lignes.csv...' . PHP_EOL;
    $lignes = file_get_contents('https://data.iledefrance-mobilites.fr/explore/dataset/referentiel-des-lignes/download/?format=csv&timezone=Europe/Berlin&lang=fr&use_labels_for_header=true&csv_separator=%3B');
    file_put_contents($dossier . 'lignes.csv', $lignes);
echo '   Fetching lignes.csv ✅' . PHP_EOL;

echo '   Fetching sncf.csv...' . PHP_EOL;
    $sncf = file_get_contents('https://ressources.data.sncf.com/explore/dataset/referentiel-gares-voyageurs/download/?format=csv&timezone=Europe/Berlin&lang=fr');
    file_put_contents($dossier . 'sncf.csv', $sncf);
echo '   Fetching sncf.csv ✅' . PHP_EOL;

echo '   Truncate Tables ...' . PHP_EOL;
    clearLignes ();
    clearArretsLignes();
    clearGTFS();
echo '   Truncate Tables ✅' . PHP_EOL;

echo '   Write GTFS...' . PHP_EOL;
    writeGTFS();
echo '   Write GTFS ✅' . PHP_EOL;

echo '   Write lignes.csv...' . PHP_EOL;
    writeLignes ($dossier . 'lignes.csv');
echo '   Write lignes.csv ✅' . PHP_EOL;

echo '   Write arrets_lignes.csv...' . PHP_EOL;
    writeArretsLignes ($dossier . 'arrets_lignes.csv');
echo '   Write arrets_lignes.csv ✅' . PHP_EOL;

echo '   Write sncf.csv...' . PHP_EOL;
    $sncf = read_csv($dossier . 'sncf.csv');
    
    foreach ($sncf as $row) {
        if ($row[0] != 'code' && $row[1] != '' && $row[1] != false) {
                        
            $id = 'SNCF';
            $route_long_name = 'Trains SNCF';
            $operatorname = 'SNCF';

            $stop_id            = 'SNCF:' . $row[1];
            $parent_station     = 'SNCF:' . substr($row[2], 2);
            $stop_code          = $row[27];
            $stop_name          = $row[4];
            $stop_lon           = isset($row[10]) ? $row[10] : "";
            $stop_lat           = isset($row[11]) ? $row[11] : "";
            $pointgeo           = isset($row[10]) ? $row[10] . ',' . $row[11] : "";
            $nom_commune        = $row[7];
            $code_insee         = $row[8] . $row[6];
            $departement        = $row[8];

            $allowed = true;

            if (in_array($departement, $SNCF_FORBIDDEN_DEPT)) {
                $allowed = false;
                echo $stop_id . ' - ' . $stop_name . ' - Departement non autorisé' . PHP_EOL;
            } 
            if (in_array($stop_name, $SNCF_FORBIDDEN)) {
                $allowed = false;
                echo $stop_id . ' - ' . $stop_name . ' - Nom non autorisé' . PHP_EOL;
            } 
            if (in_array($stop_name, $SNCF_FORCE))
                $allowed = true;

            if ($allowed == true) {
                try {
                    // location_type = 0
                    insertStops ($stop_id, $stop_code, $stop_name, "", $stop_lon, $stop_lat, "0", "", "0", $parent_station, "", "", "0", "");
                    // location_type = 1
                    insertStops ($parent_station, $stop_code, $stop_name, "", $stop_lon, $stop_lat, "0", "", "1", "", "", "", "0", "");
                    
                    insertArretLigne ($id, $route_long_name, $stop_id, $stop_name, $stop_lon, $stop_lat, $operatorname, $pointgeo, $nom_commune, $code_insee);
                } catch (Exception $e) {
                    echo $e;
                }
            }
        } 
    }

echo '   Write sncf.csv ✅' . PHP_EOL;
echo '   Write ✅' . PHP_EOL;
    $query = file_get_contents('../data/sql/insert_admin.sql');
    SQLinit($query);
echo 'Update ✅' . PHP_EOL;

?>