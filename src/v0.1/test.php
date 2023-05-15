<?php

$dossier = '../data/cache/schedules/';

if (!isset($_GET['s']) || $_GET['s'] == null) {
    ErrorMessage(400, 'Required parameter "s" is missing or null.');
}

$id = $_GET['s'];

$request = getSubstop($id);

while ($obj = $request->fetch()) {
    echo '---------------' . PHP_EOL;
    echo $obj['stop_id'];

    $_request = getSchedulesBySubstop($obj['stop_id']);
    while ($_obj = $_request->fetch()) {
        echo $_obj['departure_time'];
    }

    echo PHP_EOL;
}