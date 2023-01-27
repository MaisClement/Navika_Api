<?php

include_once('base/main.php');
include_once('base/function.php');
include_once('base/request.php');
include_once ('base/gtfs_request.php');

$dossier = '../data/file/gtfs/';

echo '> Clearing file' . PHP_EOL;

$fichier = '../data/cache/';
clear_directory($fichier);

$fichier = '../data/report/';
clear_directory($fichier);

$fichier = '../data/file/gtfs/';
remove_directory($fichier);

echo '> Looking for GTFS...' . PHP_EOL;

// -----------------------------------------------------
// IDFM - https://transport.data.gouv.fr/api/datasets/60d2b1e50215101bf6f9ae1b
// Bibus - https://transport.data.gouv.fr/api/datasets/55ffbe0888ee387348ccb97d
// TIGNES - https://transport.data.gouv.fr/api/datasets/5f0588426c51abada608d7a7
// Chambéry - https://transport.data.gouv.fr/api/datasets/5bae8c2806e3e75b699dc606
// Strasbourg - https://transport.data.gouv.fr/api/datasets/5ae1715488ee384c8ba0342b

$l = [
    '60d2b1e50215101bf6f9ae1b',  // IDFM
    '55ffbe0888ee387348ccb97d',  // Bibus
    // '5f0588426c51abada608d7a7',  // TIGNES
    // '5bae8c2806e3e75b699dc606',  // Chambéry
    // '5ae1715488ee384c8ba0342b',  // Strasbourg
];

$needupdate = false;

foreach($l as $url) {
    $ressource = getGTFSlistFromApi($url);
    echo '  > ' . $url . PHP_EOL;

    $request = getProvider($ressource);

    $action = false;
    if ($request->rowCount() == 0) {
        $action = true;
        $needupdate = true;
    }

    while ($obj = $request->fetch()) {
        if (strtotime($ressource['updated']) > strtotime($obj['updated'])) {
            print_r($ressource['updated']);
            print_r($obj['updated']);
            $action = true;
            $needupdate = true;
        }
    }

    if ($action == true) {
        $provider = $ressource['slug'] . '/';
        $provider_id = $ressource['slug'];
        if (!is_dir($dossier . $provider)) {
            mkdir($dossier . $provider);
        }
        
        $zip = file_get_contents($ressource['url']);
        echo '    i ' . $ressource['url'] . PHP_EOL;
        file_put_contents($dossier . $provider . 'gtfs.zip', $zip);
        echo '    > Downloaded' . PHP_EOL;

        insertProvider($ressource);
    } else {
        echo '    i Already existing file, not updated' . PHP_EOL;
    }
}

// Si rien a faire
if ($needupdate == false) {
    echo PHP_EOL . '-----' . PHP_EOL;
    echo 'Nothing to do ✅';
    echo PHP_EOL . '-----' . PHP_EOL;
    exit;
}

echo '> Unzip GTFS...' . PHP_EOL;

$provider_dir = scandir($dossier);
foreach ($provider_dir as $provider_id) {
    if (is_dir($dossier . $provider_id)) {
        if (is_file($dossier . $provider_id . '/gtfs.zip')) {
            echo '  ' . $provider_id . PHP_EOL;
        
            $zip = new ZipArchive;
            try {
                $zip->open($dossier . $provider_id . '/gtfs.zip');
                $zip->extractTo($dossier . $provider_id . '/');
                $zip->close();
                unlink($dossier . $provider_id . '/gtfs.zip');
                clearProviderData($provider_id);
            } catch (ValueError $e) {
                echo '  ! Failed to unzip GTFS !' . PHP_EOL;
            }

            unset($zip);
        }
    }
}

echo '> Formatting file...' . PHP_EOL;

$provider_dir = scandir($dossier);
foreach ($provider_dir as $provider_id) {
    if (is_dir($dossier . $provider_id)) {

        $files = glob($dossier . $provider_id . '/*.{txt}', GLOB_BRACE);
        foreach($files as $file) {
            // echo $file;
            $content = file_get_contents($file);
            $content = str_replace('\r\n', '\n', $content);
            file_put_contents($file, $content);
        }
    }
}

include('update_gtfs.php');

?>