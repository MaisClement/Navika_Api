<?php

chdir('/var/www/navika');

include_once('src/base/main.php');

$dir = 'data/file/gtfs/';

echo '> Clear cache...' . PHP_EOL;

// clear_directory('data/cache/');
// clear_directory('data/report/');
// remove_directory('data/file/gtfs/');

// -----------------------------------------------------
// Check if there is GTFS update
echo '> Looking for GTFS...' . PHP_EOL;

$needupdate = false;

foreach ($CONFIG->gtfs as $gtfs) {
    echo '  > ' . $gtfs->name . PHP_EOL;

    // get data from GTFS
    $ressource = getGTFSlistFromApi($gtfs);
    $request = getProvider($ressource);

    $action = false;

    if ($request->rowCount() == 0) {
        $action = true;
    }
    while ($obj = $request->fetch()) {
        if (strtotime($ressource['updated']) > strtotime($obj['updated'])) {
            echo '    i ' . $ressource['updated'] . ' - ' . $obj['updated'] . PHP_EOL;
            $action = true;
            break;
        }
    }

    if (!$action) {
        echo '    i Data already up-to-date, not updated' . PHP_EOL;

    } else {
        $needupdate = true;
        $provider = $gtfs->id;

        if (!is_dir($dir . $provider)) {
            mkdir($dir . $provider);
        }

        echo '    i ' . $ressource['url'] . PHP_EOL;

        try {
            // download gtfs
            $zip_name = $dir . $provider . '/gtfs.zip';

            $zip = file_get_contents($ressource['url']);
            file_put_contents($zip_name, $zip);
            unset($zip);

            echo '    > Downloaded' . PHP_EOL;

            deleteProvider($ressource['provider_id']);
            insertProvider($ressource);

            // unzip gtfs
            echo '    > Unzip gtfs...' . PHP_EOL;
            unzip($zip_name, $dir . $provider);
            
            // format file + rename
            echo '    > Format file...' . PHP_EOL;
            foreach($ressource['filenames'] as $filename) {
                // on deplace hors d'un potentiel fichier
                $content = file_get_contents($dir . $provider . '/' . $filename);
                $content = str_replace('\r\n', '\n', $content);
                file_put_contents($dir . $provider . '/' . $filename, $content);
                
                if (strpos($filename, '/')) {
                    $new = substr($filename, strpos($filename, '/') + 1);
                    echo $dir . $provider . '/' . $filename;
                    echo $dir . $provider . '/' . $new;
                    rename($dir . $provider . '/' . $filename, $dir . $provider . '/' . $new);
                }
            }

            // remove file and clear data
            echo '    > Remove old data...' . PHP_EOL;
            unlink($zip_name);
            clearProviderData($provider);
            
            // import gtfs
            echo '    > Import new GTFS...' . PHP_EOL;
            $err = 0;
            $types = [
                'agency'            => ['agency_id'],
                'stops'             => ['stop_id', 'level_id', 'parent_station'],
                'routes'            => ['route_id', 'agency_id'],
                'trips'             => ['route_id', 'service_id', 'trip_id'],
                'stop_times'        => ['trip_id', 'stop_id'],
                'calendar'          => ['service_id'],
                'calendar_dates'    => ['service_id'],
                'fare_attributes'   => ['fare_id', 'agency_id'],
                'fare_rules'        => ['fare_id', 'route_id', 'origin_id', 'destination_i'],
                'frequencies'       => ['trip_id'],
                'transfers'         => ['from_stop_id', 'to_stop_id'],
                'pathways'          => ['pathway_id', 'from_stop_id', 'to_stop_id'],
                'levels'            => ['level_id', ''],
                'feed_info'         => [], //
                'translations'      => [], //
                'attributions'      => []   //
            ];
            
            foreach ($types as $type => $columns) {
                $file = $dir . $provider . '/' . $type . '.txt';
                if (is_file($file)) {
                    echo '        ' . $type . '        ';
                    $header = getCSVHeader($file)[0][0];
                    echo '1/5 ';
                    try {
                        $table = 'temp_' . $type;
                        
                        perpareTempTable($type, $table);
                        echo '2/5 ';
                        
                        insertFile($table, $file, $header, ',', $provider);
                        echo '3/5 ';

                        $prefix = $provider . ':';
                        foreach($columns as $column) {
                            prefixTable($table, $column, $prefix);
                        }
                        echo '4/5 ';

                        copyTable($table, $type);
                        echo '5/5 ' . PHP_EOL;

                        // unlink($file);
                    } catch (Exception $e) {
                        echo $e;
                        $err++;
                    }
                }
            }
            
            echo '      ' . $err . ' errors' . PHP_EOL;

        } catch (Exception $e) {
            echo '    > Failed to download :' . $e . PHP_EOL;
        }
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

echo '> Generate stop_area...' . PHP_EOL;

$stops = [];
$request = getStopsNotInArea();
while ($obj = $request->fetch()) {
    $id = $obj['provider_id'] . $obj['stop_name'];
    if (!isset($stops[$id])) {
        $stops[$id] = array(
            'provider_id'   => isset($obj['provider_id'])   ? $obj['provider_id']   : '',
            'stop_id'       => 'ADMIN:' . $obj['stop_id'],
            'stop_code'     => isset($obj['stop_code'])     ? $obj['stop_code']     : '',
            'stop_name'     => isset($obj['stop_name'])     ? $obj['stop_name']     : '',
            'stop_lat'      => isset($obj['stop_lat'])      ? $obj['stop_lat']      : '',
            'stop_lon'      => isset($obj['stop_lon'])      ? $obj['stop_lon']      : '',
            'location_type' => '1',
            'stops' => array(),
        );
    }
    $stops[$id]['stops'][] = $obj['stop_id'];
}

foreach ($stops as $key => $stop) {
    insertStops($stop, $stop['provider_id']);

    foreach ($stop['stops'] as $child_stop) {
        setParentStation([
            'parent_station' => $stop['stop_id'],
            'stop_id' => $child_stop,
        ]);
    }
}

echo '> Generate temp stop_route...' . PHP_EOL;

truncateTempStopRoute();
generateTempStopRoute();

echo '  > Import SNCF stops...' . PHP_EOL;

include('update_sncf.php');

echo '> Updating stop_route...' . PHP_EOL;

autoDeleteStopRoute();
autoInsertStopRoute();

echo '> Looking for GBFS...' . PHP_EOL;

clearGBFS();
foreach ($CONFIG->gbfs as $gbfs) {
    echo '  > ' . $gbfs->id . PHP_EOL;

    $content = file_get_contents($gbfs->url . 'gbfs.json');
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
                getGBFSstation($feed->url, $gbfs->url, $gbfs->id);
            }
        }
    }
}

echo PHP_EOL . '-----' . PHP_EOL;
echo 'Ready ✅';
echo PHP_EOL . '-----' . PHP_EOL;

echo '> Updating stop_toute for Town...' . PHP_EOL;
generateTownInStopRoute();

echo '> Preparing for query...' . PHP_EOL;
generateQueryRoute();

// Monitoring
file_get_contents('https://betteruptime.com/api/v1/heartbeat/SrRkcBMzc4AgsXXzzZa2qFDa');

$subject = 'Navika AutoUpdate';
$message = file_get_contents('data/output.txt');
$headers = array(
    'MIME-Version' => '1.0',
    'From' => 'Navika Auto Update Monitoring <do-not-reply@hackernwar.com>',
    'Return-Path' => 'Navika Auto Update Monitoring <do-not-reply@hackernwar.com>',
    'Reply-To' => 'Navika Auto Update Monitoring <do-not-reply@hackernwar.com>',
    'Content-Transfer-Encoding' => 'quoted-printable',
    'Content-type' => 'text/html; charset="utf-8"',
);

// if (!mail('clementf78@gmail.com', $subject, str_replace(PHP_EOL, '<br>', $message), $headers)) {
//     echo '! failed to send mail !' . PHP_EOL;
// }
