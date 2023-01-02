<?php

include_once ('base/main.php');
include_once ('base/function.php');
include_once ('base/request.php');
    
mkdir('../data/cache/journeys', 0777, true);
mkdir('../data/cache/places', 0777, true);
mkdir('../data/cache/schedules', 0777, true);
mkdir('../data/cache/schedules_line', 0777, true);
mkdir('../data/cache/stop_area', 0777, true);
mkdir('../data/cache/stop_point', 0777, true);

mkdir('../data/file/gtfs', 0777, true);

mkdir('../data/report', 0777, true);

$query = file_get_contents('../data/sql/SQL.sql');
SQLinit($query);

include('update.php');

?>