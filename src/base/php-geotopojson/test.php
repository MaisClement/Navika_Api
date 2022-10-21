<?php

include(__DIR__ . '/GeoTopoJSON.php');

$geojsons = GeoTopoJSON::toGeoJSONs(file_get_contents('sample-topo.json'));
$geojson = json_encode($geojsons);
file_put_contents('geojson.json', $geojson);
