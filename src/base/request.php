<?php

function getStopByQuery($query){
    $db = $GLOBALS["db"];
    $query = strtolower( trim( $query ) );

    $req = $db->prepare("
        SELECT stop_id, stop_code, stop_name, stop_lat, stop_lon
        GeomFromText(CONCAT('POINT (', stop_lat, ' ', stop_lon, ')')) AS geo_point,
        ( 0 ) AS distance,
        stop_desc,
        zone_id, stop_url, location_type,
        stop_timezone, level_id, platform_code 
        FROM stops 
        WHERE LOWER( stops.stop_name ) LIKE  '%?%'
    ");
    $req->execute(array($query));
    return $req;
}

function getStopByGeoCoords($lat, $lon, $distance = 1000){
    $db = $GLOBALS["db"];
    $query = trim( $lat );
    $query = trim( $lon );

    $req = $db->prepare("
        SELECT stop_id, stop_code, stop_name, stop_lat, stop_lon,
        ( ST_distance_sphere(
            GeomFromText(CONCAT('POINT (', stop_lat, ' ', stop_lon, ')')), 
            ST_GeomFromText('POINT(? ?)')) 
        ) AS distance,
        stop_desc, zone_id, stop_url, location_type, stop_timezone, level_id, platform_code
        FROM stops 
        WHERE ( ST_distance_sphere(
            GeomFromText(CONCAT('POINT (', stop_lat, ' ', stop_lon, ')')), 
            ST_GeomFromText('POINT(? ?)')) 
        ) < ?
        ORDER BY `distance` ASC
    ");
    $req->execute( array($lat, $lon, $lat, $lon, $distance) );
    return $req;
}

function getStopByQueryAndGeoCoords($query, $lat, $lon, $distance = 1000){
    $db = $GLOBALS["db"];
    $query = strtolower( trim( $query ) );
    $query = trim( $lat );
    $query = trim( $lon );

    $req = $db->prepare("
        SELECT stop_id, stop_code, stop_name, stop_lat, stop_lon,
        ( ST_distance_sphere(
            GeomFromText(CONCAT('POINT (', stop_lat, ' ', stop_lon, ')')), 
            ST_GeomFromText('POINT(? ?)')) 
        ) AS distance,
        stop_desc, zone_id, stop_url, location_type, stop_timezone, level_id, platform_code
        FROM stops 
        WHERE ( ST_distance_sphere(
            GeomFromText(CONCAT('POINT (', stop_lat, ' ', stop_lon, ')')), 
            ST_GeomFromText('POINT(? ?)')) 
        ) < ?
        AND LOWER( stops.stop_name ) LIKE  '%?%'
        ORDER BY `distance` ASC
    ");
    $req->execute( array($lat, $lon, $lat, $lon, $distance, ) );
    return $req;
}

?>