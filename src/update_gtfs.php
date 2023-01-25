<?php

include_once ('base/main.php');
include_once ('base/function.php');
include_once ('base/request.php');
include_once ('base/gtfs_request.php');

echo '  > Truncate GTFS'. PHP_EOL;

$query = file_get_contents('../data/sql/reset.sql');
SQLinit($query);

echo '  > Import GTFS'. PHP_EOL;

// import GTFS
$directory = "../data/file/gtfs/";
$err = 0;

// $types = ['agency.txt', 'stops.txt', 'routes.txt', 'trips.txt', 'stop_times.txt', 'calendar.txt', 'calendar_dates.txt', 'fare_attributes.txt', 'fare_rules.txt', 'shapes.txt', 'frequencies.txt', 'transfers.txt', 'pathways.txt', 'levels.txt', 'feed_info.txt', 'translations.txt', 'attributions.txt'];
$types = ['agency.txt', 'stops.txt', 'routes.txt', 'trips.txt', 'stop_times.txt', 'calendar.txt', 'calendar_dates.txt', 'fare_attributes.txt', 'fare_rules.txt', 'frequencies.txt', 'transfers.txt', 'pathways.txt', 'levels.txt', 'feed_info.txt', 'translations.txt', 'attributions.txt'];

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
                    insertFile($type, $file, $header, ',', $provider_id);
                } catch (Exception $e) {
                    echo $e;
                    $err++;
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
    }
}

echo $err . ' erreurs' . PHP_EOL;

echo 'Generate stop_route table' . PHP_EOL;

generateStopRoute();

echo 'Ready !' . PHP_EOL;
echo 'Preparing for query...' . PHP_EOL;

generateQueryRoute();

?>