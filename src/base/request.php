<?php

function getStopByQuery($type, $query, $count = 15){
    $db = $GLOBALS["db"];
    $query = strtolower( trim( $query ) );

    $req = $db->prepare("
        SELECT stop_id, stop_code, stop_name, stop_lat, stop_lon,
        GeomFromText(CONCAT('POINT (', stop_lat, ' ', stop_lon, ')')) AS geo_point,
        ( 0 ) AS distance,
        stop_desc, zone_id, stop_url, location_type, stop_timezone, level_id, platform_code 
        FROM stops 
        WHERE LOWER( stops.stop_name ) LIKE ?
        AND location_type = ?
        LIMIT 15
    ");
    $req->execute( array( '%'.$query.'%', $type) );
    return $req;
}
function getStopByGeoCoords($type, $lat, $lon, $distance = 1000){
    $db = $GLOBALS["db"];
    $lat = trim( $lat );
    $lon = trim( $lon );

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
        AND location_type = ?
        LIMIT 15
    ");
    $req->execute( array($lat, $lon, $lat, $lon, $distance, $type) );
    return $req;
}
function getStopByQueryAndGeoCoords($type, $query, $lat, $lon, $distance = 1000){
    $db = $GLOBALS["db"];
    $query = strtolower( trim( $query ) );
    $lat = trim( $lat );
    $lon = trim( $lon );

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
        AND location_type = ?
        LIMIT 15
    ");
    $req->execute( array($lat, $lon, $lat, $lon, $distance, $query, $type) );
    return $req;
}

function clearTown(){
    $db = $GLOBALS["db"];

    $req = $db->prepare("TRUNCATE town");
    $req->execute( );
    return $req;
}
function addTown($id, $name, $polygon){
    $db = $GLOBALS["db"];
    $id = trim( $id );
    $name = trim( $name );

    $req = $db->prepare("
            INSERT INTO town
            VALUES(?, ?, PolygonFromText(?));
    ");
    $req->execute( array($id, $name, $polygon) );
    return $req;
}
function getTownByGeoPoint($lat, $lon){
    $db = $GLOBALS["db"];
    $lat = trim( $lat );
    $lon = trim( $lon );

    $req = $db->prepare("
                    SELECT T.town_name, Z.zip_code
                    FROM town T
                    LEFT JOIN zip_code Z
                    ON T.town_id = Z.town_id
                    WHERE ST_CONTAINS(T.town_polygon, ST_GeomFromText('POINT($lat $lon)') );
    ");
    $req->execute( ); // $req->execute( array($lat, $lon) );
    return $req;
}


?>