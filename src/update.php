<?php

include_once('base/main.php');
include_once('base/function.php');
include_once('base/request.php');

$dossier = '../data/file/gtfs/';

echo '> Clearing file' . PHP_EOL;

$fichier = '../data/cache/';
clear_directory($fichier);

$fichier = '../data/report/';
clear_directory($fichier);

$fichier = '../data/file/gtfs/';
remove_directory($fichier);

echo '> Looking for GTFS...' . PHP_EOL;

$gtfs = curl_GTFS('https://data.iledefrance-mobilites.fr/explore/dataset/offre-horaires-tc-gtfs-idfm/download/?format=csv&timezone=Europe/Berlin&lang=fr&csv_separator=%3B');
file_put_contents($dossier . 'gtfs.csv', $gtfs);
$gtfs = read_csv($dossier . 'gtfs.csv');
$provider = "idfm/";

if (!is_dir($dossier . $provider)) {
    mkdir($dossier . $provider);
}

foreach ($gtfs as $row) {
    if ($row && $row[1] && $row[1] != 'url' && $row[1] != '' && $row[1] != false) {
        echo '  > ' . $row[1] . PHP_EOL;

        $request = getProvider([
            'provider_id' => 'idfm',
            'url' => $row[1],
        ]);

        while ($obj = $request->fetch()) {
            if ($obj['url'] == $row[1]) {
                echo '    i Already existing file, not updated' . PHP_EOL;
            } else {
                $zip = file_get_contents($row[1]);
                file_put_contents($dossier . $provider . 'gtfs.zip', $zip);
                echo '    > Downloaded' . PHP_EOL;

                insertProvider([
                    'provider_id' => 'idfm',
                    'slug'        => 'IDFM',
                    'title'       => 'IDFM',
                    'type'        => 'GTFS',
                    'url'         => $row[1],
                    'updated'     => date('Y-m-d H:i:s'),
                    'flag'        => 0,
                ]);
            }
        }
    }
}

// -----------------------------------------------------
// Bibus - https://transport.data.gouv.fr/api/datasets/55ffbe0888ee387348ccb97d



if (is_file($dossier . $provider . 'gtfs.zip')) {
    echo '    > Unzip GTFS...' . PHP_EOL;

    $zip = new ZipArchive;
    if ($zip->open($dossier . $provider . 'gtfs.zip')) {
        $zip->extractTo($dossier . $provider);
        $zip->close();
    } else {
        echo '  ! Failed to unzip GTFS !' . PHP_EOL;
    }
    
    include('update_gtfs.php');
}

?>