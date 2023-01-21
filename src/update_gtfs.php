<?php

include_once ('base/main.php');
include_once ('base/function.php');
include_once ('base/request.php');
include_once ('base/gtfs_request.php');

echo '  > Truncate GTFS'. PHP_EOL;

$query = file_get_contents('../data/sql/reset.sql');
SQLinit($query);

echo '  > Import GTFS'. PHP_EOL;

// use the curl function to get json data from https://transport.data.gouv.fr/api/datasets
// then read json data and for each dataset, if there is an ressources with type is 'GTFS', display it

// $json = curl('https://transport.data.gouv.fr/api/datasets');
// $json = json_decode($json);
// 
// $i = 0;
// foreach ($json as $dataset) {
//     foreach ($dataset->resources as $resources) {
//         if ($resources->format == 'gtfs-rt' && $resources->is_available == true) {
//             echo 'Dataset: ' . $dataset->title . PHP_EOL;
//             echo 'Format: ' . $resources->format . PHP_EOL;
//             echo 'URL: ' . $resources->url . PHP_EOL;
//             echo PHP_EOL;
// 
//             $i++;
//         }
//     }
// }
// echo 'Total: ' . $i . PHP_EOL;

// import GTFS
$err = 0;

$directory = "../data/file/gtfs/";

// $types = ['agency.txt', 'stops.txt', 'routes.txt', 'trips.txt', 'stop_times.txt', 'calendar.txt', 'calendar_dates.txt', 'fare_attributes.txt', 'fare_rules.txt', 'shapes.txt', 'frequencies.txt', 'transfers.txt', 'pathways.txt', 'levels.txt', 'feed_info.txt', 'translations.txt', 'attributions.txt'];
$types = ['agency.txt', 'stops.txt', 'routes.txt', 'trips.txt', 'stop_times.txt', 'calendar.txt', 'calendar_dates.txt', 'fare_attributes.txt', 'fare_rules.txt', 'frequencies.txt', 'transfers.txt', 'pathways.txt', 'levels.txt', 'feed_info.txt', 'translations.txt', 'attributions.txt'];

// $types = ['stop_times.txt'];

$provider_dir = scandir($directory);
foreach ($provider_dir as $provider_id) {
    if (is_dir($directory . $provider_id) && strlen($provider_id) > 2) {
        echo $provider_id . PHP_EOL;

        foreach ($types as $type) {
            $file = $directory . $provider_id  . '/' . $type;
            if (is_file($file)) {
                echo '  > ' . $type . PHP_EOL;

                $header = getCSVHeader($file)[0][0];

                try {
                    insertFile($type, $file, $header, ',', 'TEST');
                } catch (Exception $e) {
                    echo $e;
                }

                // $content = read_csv($file, ',');
                // $col = getGTFSHeader($content[0]);
                // $content = array_slice($content, 1);
                // 
                // foreach ($content as $row) {
                //     $y = 0;
                //     $opt = [];
                //     if (gettype($row) == 'array' || gettype($row) == 'object' ){
                //         foreach($row as $el) {
                //             $opt[$col[$y]] = $el;
                //             $y++;
                //         }
                //         try {
                //             insertGTFS($type, $opt, $provider_id);
                //         } catch (Exception $e) {
                //             $err++;
                //         }
                //     }
                // }
            }
        }
        break;
    }
}

echo 'Generate stop_route table' . PHP_EOL;

generateStopRoute();
generateQueryRoute()

// echo "Erreurs : " . $err . PHP_EOL;

?>