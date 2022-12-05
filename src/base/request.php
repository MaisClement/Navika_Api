<?php

function getStopByQuery($type, $query, $count = 15){
    $db = $GLOBALS["db"];
    $query = strtolower( trim( $query ) );

    $req = $db->prepare("
        SELECT S.stop_id, S.stop_code, S.stop_name, S.stop_lat, S.stop_lon, point(S.stop_lat, S.stop_lon) AS geo_point, 0 AS distance, S.stop_desc, S.zone_id, S.location_type, S.level_id, S.platform_code, 
        CONCAT((
            SELECT CONCAT(Z.zip_code,'; ', T.town_name)
            FROM town T
            LEFT JOIN zip_code Z
            ON T.town_id = Z.town_id
            WHERE ST_CONTAINS(T.town_polygon, GeomFromText(CONCAT('POINT (', S.stop_lat, ' ', S.stop_lon, ')')))
            LIMIT 1
        )) as town
        FROM stops S

        WHERE LOWER( S.stop_name ) LIKE ?
        AND location_type = ?

        LIMIT 15; 
    ");
    $req->execute( array( '%'.$query.'%', $type) );
    return $req;
}
function getStopByGeoCoords($type, $lat, $lon, $distance = 1000){
    $db = $GLOBALS["db"];
    $lat = trim( $lat );
    $lon = trim( $lon );

    $req = $db->prepare("
        SELECT S.stop_id, S.stop_code, S.stop_name, S.stop_lat, S.stop_lon, point(S.stop_lat, S.stop_lon) AS geo_point,
        ST_Distance_Sphere(
            point(S.stop_lat, S.stop_lon),
            point(?, ?)
        ) AS distance, S.stop_desc, S.zone_id, S.location_type, S.level_id, S.platform_code, 
        CONCAT((
            SELECT CONCAT(Z.zip_code,'; ', T.town_name)
            FROM town T
            LEFT JOIN zip_code Z
            ON T.town_id = Z.town_id
            WHERE ST_CONTAINS(T.town_polygon, point(S.stop_lat, S.stop_lon))
            LIMIT 1
        )) as town
        FROM stops S

        WHERE  LOWER( S.stop_name ) LIKE ?
        AND(ST_Distance_Sphere(
            point(S.stop_lat, S.stop_lon),
            point(?, ?)
        )) < ?
        AND location_type = ?

        ORDER BY distance ASC

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
        SELECT S.stop_id, S.stop_code, S.stop_name, S.stop_lat, S.stop_lon, point(S.stop_lat, S.stop_lon) AS geo_point,
        ST_Distance_Sphere(
            point(S.stop_lat, S.stop_lon),
            point(?, ?)
        ) AS distance, S.stop_desc, S.zone_id, S.location_type, S.level_id, S.platform_code, 
        CONCAT((
            SELECT CONCAT(Z.zip_code,'; ', T.town_name)
            FROM town T
            LEFT JOIN zip_code Z
            ON T.town_id = Z.town_id
            WHERE ST_CONTAINS(T.town_polygon, point(S.stop_lat, S.stop_lon))
            LIMIT 1
        )) as town
        FROM stops S

        WHERE (ST_Distance_Sphere(
            point(S.stop_lat, S.stop_lon),
            point(?, ?)
        )) < ?
        AND location_type = ?

        ORDER BY distance ASC

        LIMIT 15
    ");
    $req->execute( array($lat, $lon, $lat, $lon, $distance, '%'.$query.'%', $type) );
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

function getZipCodeByInsee ($code) {
    $db = $GLOBALS["db"];
    $code = trim( $code );

    $req = $db->prepare("
            SELECT * 
            FROM zip_code
            WHERE town_id = ?;
    ");
    $req->execute( array($code) );
    return $req;
}

function getLinesById ($id) {
    $db = $GLOBALS["db"];
    $id = trim( $id );

    $req = $db->prepare("
            SELECT * 
            FROM lignes
            WHERE id_line = ?;
    ");
    $req->execute( array($id) );
    return $req;
}

function getAllLinesAtStop ($id) {
    $db = $GLOBALS["db"];
    $id = trim( $id );

    $req = $db->prepare("
            SELECT L.*
            FROM stops S
            INNER JOIN arrets_lignes A
            ON S.stop_id = A.stop_id 
            INNER JOIN lignes L
            ON REPLACE(A.id, 'IDFM:', '') = L.id_line
            
            WHERE parent_station = ?;
    ");
    $req->execute( array($id) );
    return $req;
}


function clearLignes () {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        TRUNCATE lignes;
    ");
    $req->execute( );
    return $req;
}
function clearArretsLignes () {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        TRUNCATE arrets_lignes;
    ");
    $req->execute( );
    return $req;
}
function writeLignes () {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        LOAD DATA INFILE 
        '/var/www/navika/data/file/lignes.csv'
        INTO TABLE lignes 
        FIELDS TERMINATED BY ';' 
        ENCLOSED BY '\"'LINES TERMINATED BY '\n'
        IGNORE 1 ROWS;
    ");
    $req->execute(  );
    return $req;
}
function writeArretsLignes () {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        LOAD DATA INFILE 
        '/var/www/navika/data/file/arrets_lignes.csv'
        INTO TABLE arrets_lignes 
        FIELDS TERMINATED BY ';' 
        ENCLOSED BY '\"'LINES TERMINATED BY '\n'
        IGNORE 1 ROWS;
    ");
    $req->execute(  );
    return $req;
}

?>
