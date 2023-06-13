<?php

$search = [" ", "-", "'", "À", "Á", "Â", "Ã", "Ä", "Å", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ù", "Ú", "Û", "Ü", "Ý", "ß", "à", "á", "â", "ã", "ä", "å", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ñ", "ò", "ó", "ô", "õ", "ö", "ù", "ú", "û", "ü", "ý", "ÿ", "Ā", "ā", "Ă", "ă", "Ą", "ą", "Ć", "ć", "Ĉ", "ĉ", "Ċ", "ċ", "Č", "č", "Ď", "ď", "Đ", "đ", "Ē", "ē", "Ĕ", "ĕ", "Ė", "ė", "Ę", "ę", "Ě", "ě", "Ĝ", "ĝ", "Ğ", "ğ", "Ġ", "ġ", "Ģ", "ģ", "Ĥ", "ĥ", "Ħ", "ħ", "Ĩ", "ĩ", "Ī", "ī", "Ĭ", "ĭ", "Į", "į", "İ", "ı", "Ĵ", "ĵ", "Ķ", "ķ", "ĸ", "Ĺ", "ĺ", "Ļ", "ļ", "Ľ", "ľ", "Ŀ", "ŀ", "Ł", "ł", "Ń", "ń", "Ņ", "ņ", "Ň", "ň", "ŉ", "Ŋ", "ŋ", "Ō", "ō", "Ŏ", "ŏ", "Ő", "ő", "Œ", "œ", "Ŕ", "ŕ", "Ŗ", "ŗ", "Ř", "ř", "Ś", "ś", "Ŝ", "ŝ", "Ş", "ş", "Š", "š", "Ţ", "ţ", "Ť", "ť", "Ŧ", "ŧ", "Ũ", "ũ", "Ū", "ū", "Ŭ", "ŭ", "Ů", "ů", "Ű", "ű", "Ų", "ų", "Ŵ", "ŵ", "Ŷ", "ŷ", "Ÿ", "Ź", "ź", "Ż", "ż", "Ž", "ž", "ſ"];
$replace = ["", "",  "",  "A", "A", "A", "A", "A", "A", "C", "E", "E", "E", "E", "I", "I", "I", "I", "N", "O", "O", "O", "O", "O", "U", "U", "U", "U", "Y", "s", "a", "a", "a", "a", "a", "a", "c", "e", "e", "e", "e", "i", "i", "i", "i", "n", "o", "o", "o", "o", "o", "u", "u", "u", "u", "y", "y", "A", "a", "A", "a", "A", "a", "C", "c", "C", "c", "C", "c", "C", "c", "D", "d", "D", "d", "E", "e", "E", "e", "E", "e", "E", "e", "E", "e", "G", "g", "G", "g", "G", "g", "G", "g", "H", "h", "H", "h", "I", "i", "I", "i", "I", "i", "I", "i", "I", "i", "J", "j", "K", "k", "k", "L", "l", "L", "l", "L", "l", "L", "l", "L", "l", "N", "n", "N", "n", "N", "n", "N", "n", "N", "O", "o", "O", "o", "O", "o", "OE", "oe", "R", "r", "R", "r", "R", "r", "S", "s", "S", "s", "S", "s", "S", "s", "T", "t", "T", "t", "T", "t", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "W", "w", "Y", "y", "Y", "Z", "z", "Z", "z", "Z", "z", "s"];

function getStopByQuery($query){
    $db = $GLOBALS["db"];
    $query = urldecode(strtolower(trim($query)));
    $query = str_replace($GLOBALS['search'], $GLOBALS['replace'], $query);

    $req = $db->prepare("
        SELECT route_id, route_long_name, route_short_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_lat, stop_lon, town_id, town_name
        FROM stop_route
        WHERE LOWER( stop_query_name ) LIKE ?
        
        UNION DISTINCT
        
        SELECT route_id, route_long_name, route_short_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_lat, stop_lon, town_id, town_name
        FROM stop_route
        WHERE LOWER( town_query_name ) LIKE ?
    ");
    $req->execute(array('%' . $query . '%', '%' . $query . '%'));
    return $req;
}
function getStopByGeoCoords($lat, $lon, $distance = 1000){
    $db = $GLOBALS["db"];
    $lat = trim($lat);
    $lon = trim($lon);

    $req = $db->prepare("
        SELECT route_id, route_long_name, route_short_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_lat, stop_lon, town_id, town_name,
        ST_Distance_Sphere(
                point(stop_lat, stop_lon),
                point(?, ?)
        ) AS distance
        
        FROM stop_route
            
        WHERE ST_Distance_Sphere(
                point(stop_lat, stop_lon),
                point(?, ?)
        ) < ?
        
        ORDER BY distance;
    ");
    $req->execute(array($lat, $lon, $lat, $lon, $distance));
    return $req;
}
function getStopByQueryAndGeoCoords($query, $lat, $lon){
    $db = $GLOBALS["db"];
    $query = urldecode(strtolower(trim($query)));
    $query = str_replace($GLOBALS['search'], $GLOBALS['replace'], $query);
    $lat = trim($lat);
    $lon = trim($lon);

    $req = $db->prepare("
        SELECT route_id, route_long_name, route_short_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_lat, stop_lon, town_id, town_name,
        ST_Distance_Sphere(
                point(stop_lat, stop_lon),
                point(?, ?)
        ) AS distance
        FROM stop_route
        WHERE LOWER( stop_query_name ) LIKE ?
        
        UNION DISTINCT
        
        SELECT route_id, route_long_name, route_short_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_lat, stop_lon, town_id, town_name,
        ST_Distance_Sphere(
                point(stop_lat, stop_lon),
                point(?, ?)
        ) AS distance
        FROM stop_route
        WHERE LOWER( town_query_name ) LIKE ?
    ");
    $req->execute(array($lat, $lon, '%' . $query . '%', $lat, $lon, '%' . $query . '%'));
    return $req;
}

// ------------------------------------------------

function getStationByGeoCoords($lat, $lon, $distance = 1000){
    $db = $GLOBALS["db"];
    $lat = trim($lat);
    $lon = trim($lon);

    $req = $db->prepare("
        SELECT station_id, station_name, station_lat, station_lon, station_capacity, 
        ST_Distance_Sphere(
                point(station_lat, station_lon),
                point(?, ?)
        ) AS distance
        
        FROM stations
            
        WHERE ST_Distance_Sphere(
                point(station_lat, station_lon),
                point(?, ?)
        ) < ?
        
        ORDER BY distance;
    ");
    $req->execute(array($lat, $lon, $lat, $lon, $distance));
    return $req;
}

// ------------------------------------------------

function addTown($id, $name, $polygon){
    $db = $GLOBALS["db"];
    $id = trim($id);
    $name = trim($name);

    $req = $db->prepare("
            INSERT INTO town
            VALUES(?, ?, PolygonFromText(?));
    ");
    $req->execute(array($id, $name, $polygon));
    return $req;
}

function clearTown(){
    $db = $GLOBALS["db"];

    $req = $db->prepare("TRUNCATE town");
    $req->execute();
    return $req;
}

// ------------------------------------------------

function getLinesById($id){
    $db = $GLOBALS["db"];
    $id = trim($id);

    $req = $db->prepare("
        SELECT route_id, route_long_name, route_short_name, route_type, route_color, route_text_color
        FROM stop_route
        WHERE route_id = ?
    ");
    $req->execute(array($id));
    return $req;
}

function getStationById($id){
    $db = $GLOBALS["db"];
    $id = trim($id);

    $req = $db->prepare("
            SELECT * 
            FROM stations
            WHERE station_id = ?;
    ");
    $req->execute(array($id));
    return $req;
}

function getAllLinesAtStop($id){
    $db = $GLOBALS["db"];
    $id = trim($id);

    $req = $db->prepare("
        SELECT route_key, route_id, route_long_name, route_short_name, route_type, route_color, route_text_color
        FROM stop_route
        WHERE stop_id = ?
    ");
    $req->execute(array($id));
    return $req;
}

function getDirection($id){
    $db = $GLOBALS["db"];
    $id = idfm_format($id);
    $id = trim($id);
    $id = 'IDFM:' . $id;

    $req = $db->prepare("
            SELECT stop_name
            FROM stops
            WHERE (stop_id = ? OR parent_station = ?) AND location_type = 1;
    ");
    $req->execute(array($id, $id));
    return $req;
}

// ------------------------------------------------
function getScheduleByStop($id, $line, $dt, $time){
    $db = $GLOBALS["db"];
    $id = trim($id);
    $line = trim($line);
    $dt = trim($dt);

    $req = $db->prepare("
        SELECT DISTINCT ST.trip_id, ST.departure_time, ST.arrival_time, T.*

        FROM stops S
        
        INNER JOIN stop_times ST 
        ON S.stop_id = ST.stop_id
        
        INNER JOIN trips T 
        ON ST.trip_id = T.trip_id
        
        INNER JOIN calendar C 
        ON T.service_id = C.service_id
        
        LEFT JOIN calendar_dates CD 
        ON (T.service_id = CD.service_id AND CD.date = ?)
        
        WHERE S.parent_station = ?
            AND T.route_id = ?
            AND departure_time >= ?
            AND (
                (C.start_date <= ?
                    AND C.end_date >= ?
                    AND (
                        DATE_FORMAT(?, '%w') = '1' AND C.monday = '1'
                        OR DATE_FORMAT(?, '%w') = '2' AND C.tuesday = '1'
                        OR DATE_FORMAT(?, '%w') = '3' AND C.wednesday = '1'
                        OR DATE_FORMAT(?, '%w') = '4' AND C.thursday = '1'
                        OR DATE_FORMAT(?, '%w') = '5' AND C.friday = '1'
                        OR DATE_FORMAT(?, '%w') = '6' AND C.saturday = '1'
                        OR DATE_FORMAT(?, '%w') = '0' AND C.sunday = '1'
                    ) 
                    AND (CD.exception_type <> 2 OR CD.exception_type IS NULL)
                )
                OR CD.exception_type = 1 
            )
        ORDER BY ST.departure_time
    ");
    $req->execute(array($dt, $id, $line, $time, $dt, $dt, $dt, $dt, $dt, $dt, $dt, $dt, $dt));
    return $req;
}


// ------------------------------------------------

function insertProvider($opt){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO provider
        (provider_id, slug, title, type, url, updated, flag)
        VALUES
        (?, ?, ?, ?, ?, ?, ?)
	  ");
    $req->execute(array(
        isset($opt['provider_id']) ? $opt['provider_id'] : '',
        isset($opt['slug']) ? $opt['slug'] : '',
        isset($opt['title']) ? $opt['title'] : '',
        isset($opt['type']) ? $opt['type'] : '',
        isset($opt['url']) ? $opt['url'] : '',
        isset($opt['updated']) ? $opt['updated'] : '',
        isset($opt['flag']) ? $opt['flag'] : '',
    ));
    return $req;
}

function deleteProvider($provider_id){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        DELETE FROM provider
        WHERE provider_id = ?
	  ");
    $req->execute(array($provider_id));
    return $req;
}

function getProvider($opt){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        SELECT *
        FROM provider
        WHERE provider_id = ?
	  ");
    $req->execute(array(
        isset($opt['provider_id']) ? $opt['provider_id'] : ''
    ));
    return $req;
}


function setParentStation($opt){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        UPDATE stops
        SET parent_station = ?
        
        WHERE stop_id = ?;
    
	  ");
    $req->execute(array(
        isset($opt['parent_station']) ? $opt['parent_station'] : '',
        isset($opt['stop_id']) ? $opt['stop_id'] : '',
    ));
    return $req;
}

// ------------------------------------------------

function SQLinit($query){
    $db = $GLOBALS["db"];

    $req = $db->prepare($query);
    $req->execute();
    return $req;
}
