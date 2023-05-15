<?php

chdir('/var/www/navika/src');

include_once('base/main.php');

echo '> Import GTFS...' . PHP_EOL;

// import GTFS
$directory = "../data/file/gtfs/";
$err = 0;

$types = ['agency.txt', 'stops.txt', 'routes.txt', 'trips.txt', 'stop_times.txt', 'calendar.txt', 'calendar_dates.txt', 'fare_attributes.txt', 'fare_rules.txt', 'frequencies.txt', 'transfers.txt', 'pathways.txt', 'levels.txt', 'feed_info.txt', 'translations.txt', 'attributions.txt'];

$provider_dir = scandir($directory);
foreach ($provider_dir as $provider_id) {
    if (is_dir($directory . $provider_id) && strlen($provider_id) > 2) {
        echo '  ' . $provider_id . PHP_EOL;

        foreach ($types as $type) {
            $file = $directory . $provider_id  . '/' . $type;
            if (is_file($file)) {
                echo '    > ' . $type . PHP_EOL;

                $header = getCSVHeader($file)[0][0];

                try {
                    insertFile($type, $file, $header, ',', $provider_id);
                    unlink($file);
                } catch (Exception $e) {
                    echo $e;
                    $err++;
                }
            }
        }
    }
}
echo PHP_EOL . '-----' . PHP_EOL;
echo $err . ' errors';
echo PHP_EOL . '-----' . PHP_EOL;

echo '> Generating Stop_Area...' . PHP_EOL;

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

echo '> Generating Temp Stop_Route table...' . PHP_EOL;

truncateTempStopRoute();
generateTempStopRoute();

include('update_sncf.php');

echo '> Updating Stop_Route table...' . PHP_EOL;

autoDeleteStopRoute();
autoInsertStopRoute();

file_get_contents('https://betteruptime.com/api/v1/heartbeat/SrRkcBMzc4AgsXXzzZa2qFDa');

echo PHP_EOL . '-----' . PHP_EOL;
echo 'Ready ✅';
echo PHP_EOL . '-----' . PHP_EOL;

$subject = 'Navika AutoUpdate';
$message = file_get_contents('../data/output.txt');
$headers = array(
    'MIME-Version' => '1.0',
    'From' => 'Navika Auto Update Monitoring <do-not-reply@hackernwar.com>',
    'Return-Path' => 'Navika Auto Update Monitoring <do-not-reply@hackernwar.com>',
    'Reply-To' => 'Navika Auto Update Monitoring <do-not-reply@hackernwar.com>',
    'Content-Transfer-Encoding' => 'quoted-printable',
    'Content-type' => 'text/html; charset="utf-8"',
);

if (!mail('clementf78@gmail.com', $subject, str_replace(PHP_EOL, '<br>', $message), $headers)) {
    echo '! failed to send mail !' . PHP_EOL;
}

echo '> Updating Stop_Route for Town...' . PHP_EOL;
generateTownInStopRoute();

echo '> Preparing for query...' . PHP_EOL;
generateQueryRoute();
