<?php

$dossier = '../data/cache/schedules/';

if (!isset($_GET['s']) || $_GET['s'] == null || !isset($_GET['l']) || $_GET['l'] == null) {
    ErrorMessage(400, 'Required parameter "s" or "l" is missing or null.');
}

$id = $_GET['s'];
$line = $_GET['l'];

$request = getScheduleByStop($id, $line, date("Y-m-d"), date("G:i:s"));

while ($obj = $request->fetch()) {
    echo '---------------' . PHP_EOL;
    echo $obj['departure_time'];
    echo PHP_EOL;
}