<?php

chdir('/var/www/navika/src');

include_once('base/main.php');

$dossier = '../data/file/gtfs/';

echo '> Clearing file' . PHP_EOL;

$fichier = '../data/cache/';
clear_directory($fichier);

$fichier = '../data/report/';
clear_directory($fichier);

$fichier = '../data/file/gtfs/';
remove_directory($fichier);

// -----------------------------------------------------
// IDFM - https://transport.data.gouv.fr/api/datasets/60d2b1e50215101bf6f9ae1b
// Bibus - https://transport.data.gouv.fr/api/datasets/55ffbe0888ee387348ccb97d
// TIGNES - https://transport.data.gouv.fr/api/datasets/5f0588426c51abada608d7a7
// Chambéry - https://transport.data.gouv.fr/api/datasets/5bae8c2806e3e75b699dc606
// Strasbourg - https://transport.data.gouv.fr/api/datasets/5ae1715488ee384c8ba0342b

$gtfs = [
    '60d2b1e50215101bf6f9ae1b',  // IDFM
    // '55ffbe0888ee387348ccb97d',  // Bibus
    // '5b873d7206e3e76e5b2ffd32',  // Nantes Métropole
    // '5f0588426c51abada608d7a7',  // TIGNES
    // '5bae8c2806e3e75b699dc606',  // Chambéry
    // '5ae1715488ee384c8ba0342b',  // Strasbourg
];

$gbfs = [
    'VELIB'     => 'https://velib-metropole-opendata.smoove.pro/opendata/Velib_Metropole/', // Vélib'
    'VELOV'     => 'https://transport.data.gouv.fr/gbfs/lyon/', // Lyon
    'CVELO'     => 'https://clermontferrand.publicbikesystem.net/customer/gbfs/v2/', // Clermont Ferrand
    'VELOMAGG'  => 'https://montpellier-fr-smoove.klervi.net/gbfs/', // Montpellier 
    'BENSANC'   => 'https://transport.data.gouv.fr/gbfs/besancon/', // Besançon 
    'VLILLE'    => 'https://transport.data.gouv.fr/gbfs/vlille/', // Lille V'lille
    'CRISTOLIB' => 'https://transport.data.gouv.fr/gbfs/creteil/', // Créteil Cristolib 
    'CYCLIC'    => 'https://transport.data.gouv.fr/gbfs/rouen/', // Rouen Cy'clic
    'VELAM'     => 'https://transport.data.gouv.fr/gbfs/amiens/', // Amiens Vélam
    'VELOSTAN'  => 'https://transport.data.gouv.fr/gbfs/nancy/', // Nancy vélOstan’lib 
    'VELOCITE'  => 'https://transport.data.gouv.fr/gbfs/mulhouse/', // Mulhouse Vélocité
    'STAR'      => 'https://eu.ftp.opendatasoft.com/star/gbfs/', // Rennes STAR !!!!
    'VELIVERT'  => 'https://saint-etienne-fr-smoove.klervi.net/gbfs/', // St Etienne VéliVert
    'VELOTOUS'  => 'https://transport.data.gouv.fr/gbfs/toulouse/', // Toulouse VélÔToulouse
    'VCUB'      => 'https://transport.data.gouv.fr/gbfs/vcub/', // Bordeaux VCub
    'VELOPOP'   => 'https://avignon-gbfs.klervi.net/gbfs/', // Avignon Velopop’ 
    'VELO2'     => 'https://transport.data.gouv.fr/gbfs/cergy-pontoise/', // Cergy Vélo² 
    'BICLOO'    => 'https://transport.data.gouv.fr/gbfs/nantes/', // Nantes Bicloo
    'VELOCEO'   => 'https://vannes-gbfs.klervi.net/gbfs/', // Vannes Vélocéo
    'OPTYMO'    => 'https://belfort-gbfs.klervi.net/gbfs/', // Belfort Belfort

    // https://gateway.prod.partners-fs37hd8.zoov.site/gbfs/2.2/saintbrieuc/en/gbfs.json?key=YmE1ZDVlNDYtMGIwNy00MGEyLWIxZWYtNGEwOGQ4NTYxNTYz
    // ST BRIEUC
    // KEY obligatoire
];

echo '> Looking for GTFS...' . PHP_EOL;

$needupdate = false;

foreach ($gtfs as $url) {
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
            print_r($ressource['updated'] . PHP_EOL);
            print_r($obj['updated'] . PHP_EOL);
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

        deleteProvider($ressource['provider_id']);
        insertProvider($ressource);
    } else {
        print_r($ressource['updated'] . PHP_EOL);
        echo '    i Already existing file, not updated' . PHP_EOL;
    }
}

// Si rien a faire
if ($needupdate == false) {
    echo PHP_EOL . '-----' . PHP_EOL;
    echo 'Nothing to do ✅';
    echo PHP_EOL . '-----' . PHP_EOL;

    // Monitoring
    file_get_contents('https://betteruptime.com/api/v1/heartbeat/SrRkcBMzc4AgsXXzzZa2qFDa');
    exit;
}

echo '> Looking for GBFS...' . PHP_EOL;

clearGBFS();

foreach ($gbfs as $id => $url) {
    echo '  > ' . $id . PHP_EOL;

    $content = file_get_contents($url . 'gbfs.json');
    $content = json_decode($content);

    if (isset($feeds)) {
        unset($feeds);
    }

    if (isset($content->data->fr)) {
        $feeds = $content->data->fr->feeds;
    } else if (isset($content->data->en)) {
        $feeds = $content->data->en->feeds;
    } else {
        echo '🤔';
    }

    if (isset($feeds)) {
        foreach ($feeds as $feed) {
            if ($feed->name == 'station_information') {
                getGBFSstation($feed->url, $url, $id);
            }
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
        foreach ($files as $file) {
            // echo $file;
            $content = file_get_contents($file);
            $content = str_replace('\r\n', '\n', $content);
            file_put_contents($file, $content);
        }
    }
}

include('update_gtfs.php');