<?php

$search = [" ", "-", "À", "Á", "Â", "Ã", "Ä", "Å", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ù", "Ú", "Û", "Ü", "Ý", "ß", "à", "á", "â", "ã", "ä", "å", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ñ", "ò", "ó", "ô", "õ", "ö", "ù", "ú", "û", "ü", "ý", "ÿ", "Ā", "ā", "Ă", "ă", "Ą", "ą", "Ć", "ć", "Ĉ", "ĉ", "Ċ", "ċ", "Č", "č", "Ď", "ď", "Đ", "đ", "Ē", "ē", "Ĕ", "ĕ", "Ė", "ė", "Ę", "ę", "Ě", "ě", "Ĝ", "ĝ", "Ğ", "ğ", "Ġ", "ġ", "Ģ", "ģ", "Ĥ", "ĥ", "Ħ", "ħ", "Ĩ", "ĩ", "Ī", "ī", "Ĭ", "ĭ", "Į", "į", "İ", "ı", "Ĵ", "ĵ", "Ķ", "ķ", "ĸ", "Ĺ", "ĺ", "Ļ", "ļ", "Ľ", "ľ", "Ŀ", "ŀ", "Ł", "ł", "Ń", "ń", "Ņ", "ņ", "Ň", "ň", "ŉ", "Ŋ", "ŋ", "Ō", "ō", "Ŏ", "ŏ", "Ő", "ő", "Œ", "œ", "Ŕ", "ŕ", "Ŗ", "ŗ", "Ř", "ř", "Ś", "ś", "Ŝ", "ŝ", "Ş", "ş", "Š", "š", "Ţ", "ţ", "Ť", "ť", "Ŧ", "ŧ", "Ũ", "ũ", "Ū", "ū", "Ŭ", "ŭ", "Ů", "ů", "Ű", "ű", "Ų", "ų", "Ŵ", "ŵ", "Ŷ", "ŷ", "Ÿ", "Ź", "ź", "Ż", "ż", "Ž", "ž", "ſ"];
$replace = ["", "", "A", "A", "A", "A", "A", "A", "C", "E", "E", "E", "E", "I", "I", "I", "I", "N", "O", "O", "O", "O", "O", "U", "U", "U", "U", "Y", "s", "a", "a", "a", "a", "a", "a", "c", "e", "e", "e", "e", "i", "i", "i", "i", "n", "o", "o", "o", "o", "o", "u", "u", "u", "u", "y", "y", "A", "a", "A", "a", "A", "a", "C", "c", "C", "c", "C", "c", "C", "c", "D", "d", "D", "d", "E", "e", "E", "e", "E", "e", "E", "e", "E", "e", "G", "g", "G", "g", "G", "g", "G", "g", "H", "h", "H", "h", "I", "i", "I", "i", "I", "i", "I", "i", "I", "i", "J", "j", "K", "k", "k", "L", "l", "L", "l", "L", "l", "L", "l", "L", "l", "N", "n", "N", "n", "N", "n", "N", "n", "N", "O", "o", "O", "o", "O", "o", "OE", "oe", "R", "r", "R", "r", "R", "r", "S", "s", "S", "s", "S", "s", "S", "s", "T", "t", "T", "t", "T", "t", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "W", "w", "Y", "y", "Y", "Z", "z", "Z", "z", "Z", "z", "s"];

function insertAgency($opt){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
      INSERT INTO agency
      (agency_id, agency_name, agency_url, agency_timezone, agency_lang, agency_phone, agency_fare_url, agency_email)
      VALUES
      (?,?,?,?,?,?,?,?)
      ");
    $req->execute(array(
        isset($opt['agency_id']) ? $opt['agency_id'] : '',
        isset($opt['agency_name']) ? $opt['agency_name'] : '',
        isset($opt['agency_url']) ? $opt['agency_url'] : '',
        isset($opt['agency_timezone']) ? $opt['agency_timezone'] : '',
        isset($opt['agency_lang']) ? $opt['agency_lang'] : '',
        isset($opt['agency_phone']) ? $opt['agency_phone'] : '',
        isset($opt['agency_fare_url']) ? $opt['agency_fare_url'] : '',
        isset($opt['agency_email']) ? $opt['agency_email'] : '',
    ));
    return $req;
}

function insertStops($opt){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
      INSERT INTO stops
      (stop_id, stop_code, stop_name, stop_desc, stop_lat, stop_lon, zone_id, stop_url, location_type, parent_station, stop_timezone, wheelchair_boarding, level_id, platform_code)
      VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $req->execute(array(
        isset($opt['stop_id']) ? $opt['stop_id'] : '',
        isset($opt['stop_code']) ? $opt['stop_code'] : '',
        isset($opt['stop_name']) ? $opt['stop_name'] : '',
        isset($opt['stop_desc']) ? $opt['stop_desc'] : '',
        isset($opt['stop_lat']) ? $opt['stop_lat'] : '',
        isset($opt['stop_lon']) ? $opt['stop_lon'] : '',
        isset($opt['zone_id']) ? $opt['zone_id'] : '',
        isset($opt['stop_url']) ? $opt['stop_url'] : '',
        isset($opt['location_type']) ? $opt['location_type'] : '',
        isset($opt['parent_station']) ? $opt['parent_station'] : '',
        isset($opt['stop_timezone']) ? $opt['stop_timezone'] : '',
        isset($opt['wheelchair_boarding']) ? $opt['wheelchair_boarding'] : '',
        isset($opt['level_id']) ? $opt['level_id'] : '',
        isset($opt['platform_code']) ? $opt['platform_code'] : '',
    ));
    return $req;
}

function insertRoutes($opt){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
      INSERT INTO routes
      (route_id, agency_id, route_short_name, route_long_name, route_desc, route_type, route_url, route_color, route_text_color, route_sort_order, continuous_pickup, continuous_drop_off)
      VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $req->execute(array(
        isset($opt['route_id']) ? $opt['route_id'] : '',
        isset($opt['agency_id']) ? $opt['agency_id'] : '',
        isset($opt['route_short_name']) ? $opt['route_short_name'] : '',
        isset($opt['route_long_name']) ? $opt['route_long_name'] : '',
        isset($opt['route_desc']) ? $opt['route_desc'] : '',
        isset($opt['route_type']) ? $opt['route_type'] : '',
        isset($opt['route_url']) ? $opt['route_url'] : '',
        isset($opt['route_color']) ? $opt['route_color'] : '',
        isset($opt['route_text_color']) ? $opt['route_text_color'] : '',
        isset($opt['route_sort_order']) ? $opt['route_sort_order'] : '',
        isset($opt['continuous_pickup']) ? $opt['continuous_pickup'] : '',
        isset($opt['continuous_drop_off']) ? $opt['continuous_drop_off'] : '',
    ));
    return $req;
}
