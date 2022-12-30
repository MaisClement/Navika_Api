<?php

$search = [" ", "À", "Á", "Â", "Ã", "Ä", "Å", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ù", "Ú", "Û", "Ü", "Ý", "ß", "à", "á", "â", "ã", "ä", "å", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ñ", "ò", "ó", "ô", "õ", "ö", "ù", "ú", "û", "ü", "ý", "ÿ", "Ā", "ā", "Ă", "ă", "Ą", "ą", "Ć", "ć", "Ĉ", "ĉ", "Ċ", "ċ", "Č", "č", "Ď", "ď", "Đ", "đ", "Ē", "ē", "Ĕ", "ĕ", "Ė", "ė", "Ę", "ę", "Ě", "ě", "Ĝ", "ĝ", "Ğ", "ğ", "Ġ", "ġ", "Ģ", "ģ", "Ĥ", "ĥ", "Ħ", "ħ", "Ĩ", "ĩ", "Ī", "ī", "Ĭ", "ĭ", "Į", "į", "İ", "ı", "Ĳ", "ĳ", "Ĵ", "ĵ", "Ķ", "ķ", "ĸ", "Ĺ", "ĺ", "Ļ", "ļ", "Ľ", "ľ", "Ŀ", "ŀ", "Ł", "ł", "Ń", "ń", "Ņ", "ņ", "Ň", "ň", "ŉ", "Ŋ", "ŋ", "Ō", "ō", "Ŏ", "ŏ", "Ő", "ő", "Œ", "œ", "Ŕ", "ŕ", "Ŗ", "ŗ", "Ř", "ř", "Ś", "ś", "Ŝ", "ŝ", "Ş", "ş", "Š", "š", "Ţ", "ţ", "Ť", "ť", "Ŧ", "ŧ", "Ũ", "ũ", "Ū", "ū", "Ŭ", "ŭ", "Ů", "ů", "Ű", "ű", "Ų", "ų", "Ŵ", "ŵ", "Ŷ", "ŷ", "Ÿ", "Ź", "ź", "Ż", "ż", "Ž", "ž", "ſ"];

function getStopByQuery($query){
    $db = $GLOBALS["db"];
    $query = urldecode( strtolower( trim( $query ) ) );
    $query = preg_replace('/[^A-Za-z0-9 ]/', '', $query);
    $query = str_replace( $GLOBALS['search'], '', $query);

    $req = $db->prepare("
        SELECT L.id_line, L.name_line, L.shortname_line, L.transportmode, L.colourweb_hexa, L.textcolourweb_hexa, S2.stop_id, S2.stop_name, S2.stop_lat, S2.stop_lon, S2.zone_id, A.nom_commune AS town, A.code_insee AS zip_code
        FROM lignes L
        
        INNER JOIN arrets_lignes A
        ON L.id_line = REPLACE(A.id, 'IDFM:', '')
        
        INNER JOIN stops S
        ON A.stop_id = S.stop_id
        
        INNER JOIN stops S2
        ON S.parent_station = S2.stop_id
        
        WHERE LOWER( REGEXP_REPLACE(A.stop_name, '[^0-9a-zA-Z]', '') ) LIKE ?
        
        UNION DISTINCT
        
        SELECT L.id_line, L.name_line, L.shortname_line, L.transportmode, L.colourweb_hexa, L.textcolourweb_hexa, S2.stop_id, S2.stop_name, S2.stop_lat, S2.stop_lon, S2.zone_id, A.nom_commune AS town, A.code_insee AS zip_code
        FROM lignes L
        
        INNER JOIN arrets_lignes A
        ON L.id_line = REPLACE(A.id, 'IDFM:', '')
        
        INNER JOIN stops S
        ON A.stop_id = S.stop_id
        
        INNER JOIN stops S2
        ON S.parent_station = S2.stop_id
        
        WHERE LOWER( REGEXP_REPLACE(A.nom_commune, '[^0-9a-zA-Z]', '') ) LIKE ?;
    ");
    $req->execute( array( '%'.$query.'%', '%'.$query.'%') );
    return $req;
}
function getStopByGeoCoords($lat, $lon, $distance = 1000){
    $db = $GLOBALS["db"];
    $lat = trim( $lat );
    $lon = trim( $lon );

    $req = $db->prepare("
        SELECT L.id_line, L.name_line, L.shortname_line, L.transportmode, L.colourweb_hexa, L.textcolourweb_hexa, S2.stop_id, S2.stop_name, S2.stop_lat, S2.stop_lon, 
        ST_Distance_Sphere(
                point(S2.stop_lat, S2.stop_lon),
                point(?, ?)
        ) AS distance, S2.zone_id, A.nom_commune AS town, A.code_insee AS zip_code
        FROM lignes L
        
        INNER JOIN arrets_lignes A
        ON L.id_line = REPLACE(A.id, 'IDFM:', '')
        
        INNER JOIN stops S
        ON A.stop_id = S.stop_id
        
        INNER JOIN stops S2
        ON S.parent_station = S2.stop_id
        
        WHERE ST_Distance_Sphere(
                point(S2.stop_lat, S2.stop_lon),
                point(?, ?)
        ) < ?
        
        ORDER BY distance
        
        LIMIT 15;
    ");
    $req->execute( array($lat, $lon, $lat, $lon, $distance) );
    return $req;
}
function getStopByQueryAndGeoCoords($query, $lat, $lon){
    $db = $GLOBALS["db"];
    $query = urldecode( strtolower( trim( $query ) ) );
    $query = preg_replace('/[^A-Za-z0-9 ]/', '', $query);
    $query = str_replace( $GLOBALS['search'], '', $query);
    $lat = trim( $lat );
    $lon = trim( $lon );

    $req = $db->prepare("
        SELECT L.id_line, L.name_line, L.shortname_line, L.transportmode, L.colourweb_hexa, L.textcolourweb_hexa, S2.stop_id, S2.stop_name, S2.stop_lat, S2.stop_lon, S2.zone_id, A.nom_commune AS town, A.code_insee AS zip_code,
        ST_Distance_Sphere(
                point(S2.stop_lat, S2.stop_lon),
                point(?, ?)
        ) AS distance
        FROM lignes L
        
        INNER JOIN arrets_lignes A
        ON L.id_line = REPLACE(A.id, 'IDFM:', '')
        
        INNER JOIN stops S
        ON A.stop_id = S.stop_id
        
        INNER JOIN stops S2
        ON S.parent_station = S2.stop_id
        
        WHERE LOWER( REGEXP_REPLACE(A.stop_name, '[^0-9a-zA-Z]', '') ) LIKE ?
        
        UNION DISTINCT
        
        SELECT L.id_line, L.name_line, L.shortname_line, L.transportmode, L.colourweb_hexa, L.textcolourweb_hexa, S2.stop_id, S2.stop_name, S2.stop_lat, S2.stop_lon, S2.zone_id, A.nom_commune AS town, A.code_insee AS zip_code,
        ST_Distance_Sphere(
                point(S2.stop_lat, S2.stop_lon),
                point(?, ?)
        ) AS distance
        FROM lignes L
        
        INNER JOIN arrets_lignes A
        ON L.id_line = REPLACE(A.id, 'IDFM:', '')
        
        INNER JOIN stops S
        ON A.stop_id = S.stop_id
        
        INNER JOIN stops S2
        ON S.parent_station = S2.stop_id
        
        WHERE LOWER( REGEXP_REPLACE(A.nom_commune, '[^0-9a-zA-Z]', '') ) LIKE ?;
    ");
    $req->execute( array($lat, $lon, '%'.$query.'%', $lat, $lon, '%'.$query.'%') );
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
        SELECT L.id_line, L.name_line, L.shortname_line, L.transportmode, L.colourweb_hexa, L.textcolourweb_hexa
        FROM lignes L
        
        INNER JOIN arrets_lignes A
        ON REPLACE(A.id, 'IDFM:', '') = L.id_line
        
        INNER JOIN stops S
        ON S.stop_id = A.stop_id 
        
        WHERE S.parent_station = ?
        GROUP BY L.id_line;
    ");
    $req->execute( array($id) );
    return $req;
}

function getDirection ($id) {
    $db = $GLOBALS["db"];
    $id = idfm_format( $id );
    $id = trim( $id );
    $id = 'IDFM:' . $id;

    $req = $db->prepare("
            SELECT stop_name
            FROM stops
            WHERE stop_id = ? OR parent_station = ?;
    ");
    $req->execute( array($id, $id) );
    return $req;
}

// --------------------------------------------------------

function clearGTFS () {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        TRUNCATE agency;
        TRUNCATE calendar;
        TRUNCATE calendar_dates;
        TRUNCATE pathways;
        TRUNCATE routes;
        TRUNCATE stop_extensions;
        TRUNCATE stop_times;
        TRUNCATE stops;
        TRUNCATE transfers;
        TRUNCATE trips;
    ");
    $req->execute( );
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
function clearLaPoste () {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        TRUNCATE zip_code;
    ");
    $req->execute( );
    return $req;
}
function clearTown(){
    $db = $GLOBALS["db"];

    $req = $db->prepare("TRUNCATE town");
    $req->execute( );
    return $req;
}

function writeGTFS () {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        LOAD DATA INFILE '/var/www/navika/data/file/gtfs/agency.txt'			INTO TABLE agency FIELDS TERMINATED BY ',' ENCLOSED BY '\"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
        LOAD DATA INFILE '/var/www/navika/data/file/gtfs/calendar.txt'		    INTO TABLE calendar FIELDS TERMINATED BY ',' ENCLOSED BY '\"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
        LOAD DATA INFILE '/var/www/navika/data/file/gtfs/calendar_dates.txt'	INTO TABLE calendar_dates FIELDS TERMINATED BY ',' ENCLOSED BY '\"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
        LOAD DATA INFILE '/var/www/navika/data/file/gtfs/pathways.txt'		    INTO TABLE pathways FIELDS TERMINATED BY ',' ENCLOSED BY '\"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
        LOAD DATA INFILE '/var/www/navika/data/file/gtfs/routes.txt'			INTO TABLE routes FIELDS TERMINATED BY ',' ENCLOSED BY '\"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
        LOAD DATA INFILE '/var/www/navika/data/file/gtfs/stop_extensions.txt'	INTO TABLE stop_extensions FIELDS TERMINATED BY ',' ENCLOSED BY '\"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
        LOAD DATA INFILE '/var/www/navika/data/file/gtfs/stop_times.txt'		INTO TABLE stop_times FIELDS TERMINATED BY ',' ENCLOSED BY '\"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
        LOAD DATA INFILE '/var/www/navika/data/file/gtfs/stops.txt'			    INTO TABLE stops FIELDS TERMINATED BY ',' ENCLOSED BY '\"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
        LOAD DATA INFILE '/var/www/navika/data/file/gtfs/transfers.txt'		    INTO TABLE transfers FIELDS TERMINATED BY ',' ENCLOSED BY '\"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
        LOAD DATA INFILE '/var/www/navika/data/file/gtfs/trips.txt'			    INTO TABLE trips FIELDS TERMINATED BY ',' ENCLOSED BY '\"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;    
    ");
    $req->execute(  );
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
function writeLaPoste () {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        LOAD DATA INFILE 
        '/var/www/navika/data/file/laposte_hexasmal.csv'
        INTO TABLE zip_code 
        FIELDS TERMINATED BY ';' 
        ENCLOSED BY '\"'LINES TERMINATED BY '\n'
        IGNORE 1 ROWS;
    ");
    $req->execute(  );
    return $req;
}

function insertStops ($stop_id, $stop_code, $stop_name, $stop_desc, $stop_lon, $stop_lat, $zone_id, $stop_url, $location_type, $parent_station, $wheelchair_boarding, $stop_timezone, $level_id, $platform_code) {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO stops
        (stop_id, stop_code, stop_name, stop_desc, stop_lon, stop_lat, zone_id, stop_url, location_type, parent_station, wheelchair_boarding, stop_timezone, level_id, platform_code)
        VALUES  
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $req->execute( array($stop_id, $stop_code, $stop_name, $stop_desc, $stop_lon, $stop_lat, $zone_id, $stop_url, $location_type, $parent_station, $wheelchair_boarding, $stop_timezone, $level_id, $platform_code) );
}
function insertArretLigne ($id, $route_long_name, $stop_id, $stop_name, $stop_lon, $stop_lat, $operatorname, $pointgeo, $nom_commune, $code_insee) {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO arrets_lignes
        (id, route_long_name, stop_id, stop_name, stop_lon, stop_lat, operatorname, pointgeo, nom_commune, code_insee)
        VALUES
        (?, ?, ?, ? , ?, ?, ?, ?, ?, ?)
    ");
    $req->execute( array($id, $route_long_name, $stop_id, $stop_name, $stop_lon, $stop_lat, $operatorname, $pointgeo, $nom_commune, $code_insee) );
    return $req;
}

// ------------------------------------------------

?>