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
// Chambért - https://transport.data.gouv.fr/api/datasets/5bae8c2806e3e75b699dc606
$l = [
    '60d2b1e50215101bf6f9ae1b', // IDFM
    '55ffbe0888ee387348ccb97d', // Bibus
    '5f0588426c51abada608d7a7', // TIGNES
    '5bae8c2806e3e75b699dc606'  // Chambéry
];

foreach($l as $url) {
    $ressources = getGTFSlistFromApi($url);
    echo '  > ' . $url . PHP_EOL;

    foreach ($ressources as $ressource){
        $request = getProvider($ressource);

        $action = false;
        if ($request->rowCount() == 0) $action = true;

        while ($obj = $request->fetch()) {
            if ($obj['url'] == $ressource['url']) {
                echo '    i Already existing file, not updated' . PHP_EOL;
            } else {
                $action = true;
            }
        }

        if ($action == true) {
            $provider = $ressource['slug'] . '/';
            $provider_id = $ressource['slug'];
            if (!is_dir($dossier . $provider)) {
                mkdir($dossier . $provider);
            }
            
            $zip = file_get_contents($ressource['original_url']);
            echo '    i ' . $ressource['original_url'] . PHP_EOL;
            file_put_contents($dossier . $provider . 'gtfs.zip', $zip);
            echo '    > Downloaded' . PHP_EOL;

            insertProvider($ressource);
        }
    }
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
                clearProviderData($provider);
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