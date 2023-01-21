<?php

$search = [" ", "-", "'", "À", "Á", "Â", "Ã", "Ä", "Å", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ù", "Ú", "Û", "Ü", "Ý", "ß", "à", "á", "â", "ã", "ä", "å", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ñ", "ò", "ó", "ô", "õ", "ö", "ù", "ú", "û", "ü", "ý", "ÿ", "Ā", "ā", "Ă", "ă", "Ą", "ą", "Ć", "ć", "Ĉ", "ĉ", "Ċ", "ċ", "Č", "č", "Ď", "ď", "Đ", "đ", "Ē", "ē", "Ĕ", "ĕ", "Ė", "ė", "Ę", "ę", "Ě", "ě", "Ĝ", "ĝ", "Ğ", "ğ", "Ġ", "ġ", "Ģ", "ģ", "Ĥ", "ĥ", "Ħ", "ħ", "Ĩ", "ĩ", "Ī", "ī", "Ĭ", "ĭ", "Į", "į", "İ", "ı", "Ĵ", "ĵ", "Ķ", "ķ", "ĸ", "Ĺ", "ĺ", "Ļ", "ļ", "Ľ", "ľ", "Ŀ", "ŀ", "Ł", "ł", "Ń", "ń", "Ņ", "ņ", "Ň", "ň", "ŉ", "Ŋ", "ŋ", "Ō", "ō", "Ŏ", "ŏ", "Ő", "ő", "Œ", "œ", "Ŕ", "ŕ", "Ŗ", "ŗ", "Ř", "ř", "Ś", "ś", "Ŝ", "ŝ", "Ş", "ş", "Š", "š", "Ţ", "ţ", "Ť", "ť", "Ŧ", "ŧ", "Ũ", "ũ", "Ū", "ū", "Ŭ", "ŭ", "Ů", "ů", "Ű", "ű", "Ų", "ų", "Ŵ", "ŵ", "Ŷ", "ŷ", "Ÿ", "Ź", "ź", "Ż", "ż", "Ž", "ž", "ſ"];
$replace = ["", "",  "" ,  "A", "A", "A", "A", "A", "A", "C", "E", "E", "E", "E", "I", "I", "I", "I", "N", "O", "O", "O", "O", "O", "U", "U", "U", "U", "Y", "s", "a", "a", "a", "a", "a", "a", "c", "e", "e", "e", "e", "i", "i", "i", "i", "n", "o", "o", "o", "o", "o", "u", "u", "u", "u", "y", "y", "A", "a", "A", "a", "A", "a", "C", "c", "C", "c", "C", "c", "C", "c", "D", "d", "D", "d", "E", "e", "E", "e", "E", "e", "E", "e", "E", "e", "G", "g", "G", "g", "G", "g", "G", "g", "H", "h", "H", "h", "I", "i", "I", "i", "I", "i", "I", "i", "I", "i", "J", "j", "K", "k", "k", "L", "l", "L", "l", "L", "l", "L", "l", "L", "l", "N", "n", "N", "n", "N", "n", "N", "n", "N", "O", "o", "O", "o", "O", "o", "OE", "oe", "R", "r", "R", "r", "R", "r", "S", "s", "S", "s", "S", "s", "S", "s", "T", "t", "T", "t", "T", "t", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "W", "w", "Y", "y", "Y", "Z", "z", "Z", "z", "Z", "z", "s"];

function getStopByQuery($query){
    $db = $GLOBALS["db"];
    $query = urldecode( strtolower( trim( $query ) ) );
    $query = str_replace( $GLOBALS['search'], $GLOBALS['replace'], $query);

    $req = $db->prepare("
        SELECT route_id, route_long_name, route_short_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_lat, stop_lon, town_id, town_name
        FROM stop_route
        WHERE LOWER( stop_query_name ) LIKE ?
        
        UNION DISTINCT
        
        SELECT route_id, route_long_name, route_short_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_lat, stop_lon, town_id, town_name
        FROM stop_route
        WHERE LOWER( town_query_name ) LIKE ?
    ");
    $req->execute( array( '%'.$query.'%', '%'.$query.'%') );
    return $req;
}
function getStopByGeoCoords($lat, $lon, $distance = 1000){
    $db = $GLOBALS["db"];
    $lat = trim( $lat );
    $lon = trim( $lon );

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
    $req->execute( array($lat, $lon, $lat, $lon, $distance) );
    return $req;
}
function getStopByQueryAndGeoCoords($query, $lat, $lon){
    $db = $GLOBALS["db"];
    $query = urldecode( strtolower( trim( $query ) ) );
    $query = str_replace( $GLOBALS['search'], $GLOBALS['replace'], $query);
    $lat = trim( $lat );
    $lon = trim( $lon );

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
function clearTown(){
    $db = $GLOBALS["db"];

    $req = $db->prepare("TRUNCATE town");
    $req->execute( );
    return $req;
}

// ------------------------------------------------

function SQLinit($query){
    $db = $GLOBALS["db"];

    $req = $db->prepare( $query );
    $req->execute(  );
    return $req;
}

?>