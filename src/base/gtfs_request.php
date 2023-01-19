<?php

$search = [" ", "-", "À", "Á", "Â", "Ã", "Ä", "Å", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ù", "Ú", "Û", "Ü", "Ý", "ß", "à", "á", "â", "ã", "ä", "å", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ñ", "ò", "ó", "ô", "õ", "ö", "ù", "ú", "û", "ü", "ý", "ÿ", "Ā", "ā", "Ă", "ă", "Ą", "ą", "Ć", "ć", "Ĉ", "ĉ", "Ċ", "ċ", "Č", "č", "Ď", "ď", "Đ", "đ", "Ē", "ē", "Ĕ", "ĕ", "Ė", "ė", "Ę", "ę", "Ě", "ě", "Ĝ", "ĝ", "Ğ", "ğ", "Ġ", "ġ", "Ģ", "ģ", "Ĥ", "ĥ", "Ħ", "ħ", "Ĩ", "ĩ", "Ī", "ī", "Ĭ", "ĭ", "Į", "į", "İ", "ı", "Ĵ", "ĵ", "Ķ", "ķ", "ĸ", "Ĺ", "ĺ", "Ļ", "ļ", "Ľ", "ľ", "Ŀ", "ŀ", "Ł", "ł", "Ń", "ń", "Ņ", "ņ", "Ň", "ň", "ŉ", "Ŋ", "ŋ", "Ō", "ō", "Ŏ", "ŏ", "Ő", "ő", "Œ", "œ", "Ŕ", "ŕ", "Ŗ", "ŗ", "Ř", "ř", "Ś", "ś", "Ŝ", "ŝ", "Ş", "ş", "Š", "š", "Ţ", "ţ", "Ť", "ť", "Ŧ", "ŧ", "Ũ", "ũ", "Ū", "ū", "Ŭ", "ŭ", "Ů", "ů", "Ű", "ű", "Ų", "ų", "Ŵ", "ŵ", "Ŷ", "ŷ", "Ÿ", "Ź", "ź", "Ż", "ż", "Ž", "ž", "ſ"];
$replace = ["", "", "A", "A", "A", "A", "A", "A", "C", "E", "E", "E", "E", "I", "I", "I", "I", "N", "O", "O", "O", "O", "O", "U", "U", "U", "U", "Y", "s", "a", "a", "a", "a", "a", "a", "c", "e", "e", "e", "e", "i", "i", "i", "i", "n", "o", "o", "o", "o", "o", "u", "u", "u", "u", "y", "y", "A", "a", "A", "a", "A", "a", "C", "c", "C", "c", "C", "c", "C", "c", "D", "d", "D", "d", "E", "e", "E", "e", "E", "e", "E", "e", "E", "e", "G", "g", "G", "g", "G", "g", "G", "g", "H", "h", "H", "h", "I", "i", "I", "i", "I", "i", "I", "i", "I", "i", "J", "j", "K", "k", "k", "L", "l", "L", "l", "L", "l", "L", "l", "L", "l", "N", "n", "N", "n", "N", "n", "N", "n", "N", "O", "o", "O", "o", "O", "o", "OE", "oe", "R", "r", "R", "r", "R", "r", "S", "s", "S", "s", "S", "s", "S", "s", "T", "t", "T", "t", "T", "t", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "W", "w", "Y", "y", "Y", "Z", "z", "Z", "z", "Z", "z", "s"];

function addDatasets($opt) {
    // create a function to add datasets in the database with parameters in $opt associative array :
    // id, title, type, updated, url, flag
    // where all parameter are required. Trow an exception if required parameter are missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt['id'])){
        throw new Exception("id is required");
    }

    if (!isset($opt['title'])){
        throw new Exception("title is required");
    }

    if (!isset($opt['type'])){
        throw new Exception("type is required");
    }

    if (!isset($opt['updated'])){
        throw new Exception("updated is required");
    }

    if (!isset($opt['url'])){
        throw new Exception("url is required");
    }

    if (!isset($opt['flag'])){
        throw new Exception("flag is required");
    }

    $id = ['id'];
    $title = ['title'];
    $type = ['type'];
    $updated = ['updated'];
    $url = ['url'];
    $flag = ['flag'];
    
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO datasets
        (id, title, type, updated, url, flag)
        VALUES
        (?,?,?,?,?,?)
        ");
    $req->execute(array($id, $title, $type, $updated, $url, $flag));

    return $req;
}

function addAgency($opt){
    // create a function to add an agency in the database with parameters in $opt associative array :
    // agency_id, agency_name, agency_url, agency_timezone, agency_phone, agency_lang
    // where agency_id is required. Trow an exception if agency_id is missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["agency_id"])) {
        throw new Exception("agency_id is required");
    }

    $agency_id = $opt["agency_id"];
    $agency_name = isset($opt['agency_name']) ? $opt['agency_name'] : '';
    $agency_url = isset($opt['agency_url']) ? $opt['agency_url'] : '';
    $agency_timezone = isset($opt['agency_timezone']) ? $opt['agency_timezone'] : '';
    $agency_phone = isset($opt['agency_phone']) ? $opt['agency_phone'] : '';
    $agency_lang = isset($opt['agency_lang']) ? $opt['agency_lang'] : '';

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO agencies
        (agency_id, agency_name, agency_url, agency_timezone, agency_phone, agency_lang)
        VALUES
        (?,?,?,?,?,?)
        ");
    $req->execute(array($agency_id, $agency_name, $agency_url, $agency_timezone, $agency_phone, $agency_lang));

    return $req;
}

function addStop($opt){
    // create a function to add stops in the database with parameters in $opt associative array :
    // stop_id, stop_code, stop_name, stop_desc, stop_lon, stop_lat, zone_id, location_type, parent_station, wheelchair_boarding
    // where stop_id, stop_name is required. Trow an exception if stop_id is missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["stop_id"])) {
        throw new Exception("stop_id is required");
    }

    if (!isset($opt["stop_name"])) {
        throw new Exception("stop_name is required");
    }

    $stop_id = $opt["stop_id"];
    $stop_code = isset($opt['stop_code']) ? $opt['stop_code'] : '';
    $stop_name = $opt['stop_name'];
    $stop_desc = isset($opt['stop_desc']) ? $opt['stop_desc'] : '';
    $stop_lon = isset($opt['stop_lon']) ? $opt['stop_lon'] : '';
    $stop_lat = isset($opt['stop_lat']) ? $opt['stop_lat'] : '';
    $zone_id = isset($opt['zone_id']) ? $opt['zone_id'] : '';
    $location_type = isset($opt['location_type']) ? $opt['location_type'] : '';
    $parent_station = isset($opt['parent_station']) ? $opt['parent_station'] : '';
    $wheelchair_boarding = isset($opt['wheelchair_boarding']) ? $opt['wheelchair_boarding'] : '';

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO stops
        (stop_id, stop_code, stop_name, stop_desc, stop_lon, stop_lat, zone_id, location_type, parent_station, wheelchair_boarding)
        VALUES
        (?,?,?,?,?,?,?,?,?,?,?)
        ");
    $req->execute(array($stop_id, $stop_code, $stop_name, $stop_desc, $stop_lon, $stop_lat, $zone_id, $location_type, $parent_station, $wheelchair_boarding));
    return $req;
}

function addRoute($opt){
    // create a function to add routes in the database with parameters in $opt associative array :
    // route_id, agency_id, route_short_name, route_long_name, route_type, route_color, route_text_color
    // where stop_id and agency_id are required. Trow an exception if stop_id or agency_id is missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["route_id"])) {
        throw new Exception("route_id is required");
    }

    if (!isset($opt["agency_id"])) {
        throw new Exception("agency_id is required");
    }

    $route_id = $opt["route_id"];
    $agency_id = $opt["agency_id"];
    $route_short_name = isset($opt['route_short_name']) ? $opt['route_short_name'] : '';
    $route_long_name = isset($opt['route_long_name']) ? $opt['route_long_name'] : '';
    $route_type = isset($opt['route_type']) ? $opt['route_type'] : '';
    $route_color = isset($opt['route_color']) ? $opt['route_color'] : '';
    $route_text_color = isset($opt['route_text_color']) ? $opt['route_text_color'] : '';

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO routes
        (route_id, agency_id, route_short_name, route_long_name, route_type, route_color, route_text_color)
        VALUES
        (?,?,?,?,?,?,?)
        ");
    $req->execute(array($route_id, $agency_id, $route_short_name, $route_long_name, $route_type, $route_color, $route_text_color));
    return $req;
}

function addTrip($opt){
    // create a function to add trips in the database with parameters in $opt associative array :
    // route_id, service_id, trip_id, trip_headsign, trip_short_name, direction_id, wheelchair_accessible, bikes_allowed
    // where route_id, service_id and trip_id are required. Trow an exception if route_id, service_id and trip_id is missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["route_id"])) {
        throw new Exception("route_id is required");
    }

    if (!isset($opt["service_id"])) {
        throw new Exception("service_id is required");
    }

    if (!isset($opt["trip_id"])) {
        throw new Exception("trip_id is required");
    }

    $route_id = $opt["route_id"];
    $service_id = $opt["service_id"];
    $trip_id = $opt["trip_id"];
    $trip_headsign = isset($opt['trip_headsign']) ? $opt['trip_headsign'] : '';
    $trip_short_name = isset($opt['trip_short_name']) ? $opt['trip_short_name'] : '';
    $direction_id = isset($opt['direction_id']) ? $opt['direction_id'] : '';
    $wheelchair_accessible = isset($opt['wheelchair_accessible']) ? $opt['wheelchair_accessible'] : '';
    $bikes_allowed = isset($opt['bikes_allowed']) ? $opt['bikes_allowed'] : '';

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO trips
        (route_id, service_id, trip_id, trip_headsign, trip_short_name, direction_id, wheelchair_accessible, bikes_allowed)
        VALUES
        (?,?,?,?,?,?,?,?)
        ");
    $req->execute(array($route_id, $service_id, $trip_short_name, $direction_id, $wheelchair_accessible, $bikes_allowed));
    return $req;
}

function addStopTimes($opt) {
    // create a function to add trips in the database with parameters in $opt associative array :
    // trip_id, stop_id, stop_sequence, stop_headsign, arrival_time, departure_time, pickup_type, drop_off_type, shape_dist_traveled, timepoint
    // where trip_id, stop_id, stop_sequence, arrival_time, departure_time are required. Trow an exception if required parameter are missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["trip_id"])) {
        throw new Exception("trip_id is required");
    }

    if (!isset($opt["stop_id"])) {
        throw new Exception("stop_id is required");
    }

    if (!isset($opt["stop_sequence"])) {
        throw new Exception("stop_sequence is required");
    }

    if (!isset($opt["arrival_time"])) {
        throw new Exception("arrival_time is required");
    }
    
    if (!isset($opt["departure_time"])) {
        throw new Exception("departure_time is required");
    }

    $trip_id                = $opt["trip_id"];
    $stop_id                = $opt["stop_id"];
    $stop_sequence          = $opt["stop_sequence"];
    $stop_headsign          = isset($opt['stop_headsign'])          ? $opt['stop_headsign']         : '';
    $arrival_time           = $opt["arrival_time"]; 
    $departure_time         = $opt["departure_time"];   
    $pickup_type            = isset($opt['pickup_type'])            ? $opt['pickup_type']           : '';
    $drop_off_type          = isset($opt['drop_off_type'])          ? $opt['drop_off_type']         : '';
    $shape_dist_traveled    = isset($opt['shape_dist_traveled'])    ? $opt['shape_dist_traveled']   : '';
    $timepoint              = isset($opt['timepoint'])              ? $opt['timepoint']             : '';

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO trips_stop_times
        (trip_id, stop_id, stop_sequence, stop_headsign, arrival_time, departure_time, pickup_type, drop_off_type, shape_dist_traveled, timepoint)
        VALUES
        (?,?,?,?,?,?,?,?,?)
        ");
    $req->execute(array($trip_id, $stop_id, $stop_sequence, $stop_headsign, $arrival_time, $departure_time, $pickup_type, $drop_off_type, $shape_dist_traveled, $timepoint));
    return $req;
}

function addCalendar() {
    // create a function to add calendar in the database with parameters in $opt associative array :
    // service_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, start_day, end_date
    // where all parameters are required. Trow an exception if required parameter are missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["service_id"])) {
        throw new Exception("service_id is required");
    }

    if (!isset($opt["monday"])) {
        throw new Exception("monday is required");
    }
    
    if (!isset($opt["tuesday"])) {
        throw new Exception("tuesday is required");
    }
    
    if (!isset($opt["wednesday"])) {
        throw new Exception("wednesday is required");
    }

    if (!isset($opt["thursday"])) {
        throw new Exception("thursday is required");
    }
    
    if (!isset($opt["friday"])) {
        throw new Exception("friday is required");
    }

    if (!isset($opt["saturday"])) {
        throw new Exception("saturday is required");
    }

    if (!isset($opt["sunday"])) {
        throw new Exception("sunday is required");
    }

    if (!isset($opt["start_day"])) {
        throw new Exception("start_day is required");
    }

    if (!isset($opt["end_date"])) {
        throw new Exception("end_date is required");
    }

    $service_id     = $opt['service_id'];
    $monday         = $opt['monday'];
    $tuesday        = $opt['tuesday'];
    $wednesday      = $opt['wednesday'];
    $thursday       = $opt['thursday'];
    $friday         = $opt['friday'];
    $saturday       = $opt['saturday'];
    $sunday         = $opt['sunday'];
    $start_day      = $opt['start_day'];
    $end_date       = $opt['end_date'];

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO calendar
        (service_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, start_day, end_date)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
    $req->execute(array($service_id, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, $start_day, $end_date));
    return $req;
}

function addCalendarDate() {
    // create a function to add calendar date in the database with parameters in $opt associative array :
    // service_id, date, exception_type
    // where all parameters are required. Trow an exception if required parameter are missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["service_id"])) {
        throw new Exception("service_id is required");
    }

    if (!isset($opt["date"])) {
        throw new Exception("date is required");
    }

    if (!isset($opt["exception_type"])) {
        throw new Exception("exception_type is required");
    }

    $service_id     = $opt['service_id'];
    $date           = $opt['date'];
    $exception_type = $opt['exception_type'];

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO calendar_date
        (service_id, date, exception_type)
        VALUES
        (?,?,?)
        ");
    $req->execute(array($service_id, $date, $exception_type));
    return $req;
}

function addShapes() {
    // create a function to add shapes in the database with parameters in $opt associative array :
    // shape_id, shape_pt_lat, shape_pt_lon, shape_pt_sequence, shape_dist_traveled
    // where all parameters are required. Trow an exception if required parameter are missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["shape_id"])) {
        throw new Exception("shape_id is required");
    }

    if (!isset($opt["shape_pt_lat"])) {
        throw new Exception("shape_pt_lat is required");
    }

    if (!isset($opt["shape_pt_lon"])) {
        throw new Exception("shape_pt_lon is required");
    }

    if (!isset($opt["shape_pt_sequence"])) {
        throw new Exception("shape_pt_sequence is required");
    }

    if (!isset($opt["shape_dist_traveled"])) {
        throw new Exception("shape_dist_traveled is required");
    }

    $shape_id = $opt["shape_id"];
    $shape_pt_lat = $opt["shape_pt_lat"];
    $shape_pt_lon = $opt['shape_pt_lon'];
    $shape_pt_sequence = $opt['shape_pt_sequence'];
    $shape_dist_traveled = $opt['shape_dist_traveled'];

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO shapes
        (shape_id, shape_pt_lat, shape_pt_lon, shape_pt_sequence, shape_dist_traveled)
        VALUES
        (?,?,?,?,?)
        ");
    $req->execute(array($shape_id, $shape_pt_lat, $shape_pt_lon, $shape_pt_sequence, $shape_dist_traveled));
    return $req;
}

function addFrequencies($opt) {
    // create a function to add frequencies in the database with parameters in $opt associative array :
    // trip_id, start_time, end_time, headway_secs, exact_times
    // where all parameters are required except for exact_times who is optional. Trow an exception if required parameter are missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["trip_id"])) {
        throw new Exception("trip_id is required");
    }

    if (!isset($opt["start_time"])) {
        throw new Exception("start_time is required");
    }

    if (!isset($opt["end_time"])) {
        throw new Exception("end_time is required");
    }

    if (!isset($opt["headway_secs"])) {
        throw new Exception("headway_secs is required");
    }

    $trip_id        = $opt["trip_id"];
    $start_time     = $opt["start_time"];
    $end_time       = $opt["end_time"];
    $headway_secs   = $opt["headway_secs"];
    $exact_times    = isset($opt["exact_times"])    ?   $opt["exact_times"] : '';

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO frequencies
        (trip_id, start_time, end_time, headway_secs, exact_times)
        VALUES
        (?,?,?,?,?)
        ");
    $req->execute(array($trip_id, $start_time, $end_time, $headway_secs, $exact_times));
    return $req;
}

function addTransfers($opt) {
    // create a function to add frequencies in the database with parameters in $opt associative array :
    // from_stop_id, to_stop_id, transfer_type, min_transfer_time
    // where from_stop_id, to_stop_id and transfer_type required. Trow an exception if required parameter are missing.
    // other parameters are optional and defined by default to ''
    
    if (!isset($opt["from_stop_id"])) {
        throw new Exception("from_stop_id is required");
    }

    if (!isset($opt["to_stop_id"])) {
        throw new Exception("to_stop_id is required");
    }

    if (!isset($opt["transfer_type"])) {
        throw new Exception("transfer_type is required");
    }

    if (!isset($opt["min_transfer_time"])) {
        throw new Exception("min_transfer_time is required");
    }

    $from_stop_id = $opt["from_stop_id"];
    $to_stop_id   = $opt["to_stop_id"];
    $transfer_type = $opt["transfer_type"];
    $min_transfer_time = isset($opt["min_transfer_time"]) ? $opt["min_transfer_time"] : "";

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO transfers
        (from_stop_id, to_stop_id, transfer_type, min_transfer_time)
        VALUES
        (?,?,?,?)
        ");
    $req->execute(array($from_stop_id, $to_stop_id, $transfer_type, $min_transfer_time));
    return $req;
}

function addPathways($opt) {
    // create a function to add pathways in the database with parameters in $opt associative array :
    // pathway_id, from_stop_id, to_stop_id, pathway_mode, is_bidirectional, length, traversal_time, stair_count, max_slope, min_width, signposted_as, reversed_signposted_as
    // where pathway_id, from_stop_id, to_stop_id, pathway_mode and is_bidirectional required. Trow an exception if required parameter are missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["pathway_id"])) {
        throw new Exception("pathway_id is required");
    }

    if (!isset($opt["from_stop_id"])) {
        throw new Exception("from_stop_id is required");
    }

    if (!isset($opt["to_stop_id"])) {
        throw new Exception("to_stop_id is required");
    }

    if (!isset($opt["pathway_mode"])) {
        throw new Exception("pathway_mode is required");
    }

    if (!isset($opt["is_bidirectional"])) {
        throw new Exception("is_bidirectional is required");
    }
    
    $pathway_id = $opt['pathway_id'];
    $from_stop_id = $opt['from_stop_id'];
    $to_stop_id = $opt['to_stop_id'];
    $pathway_mode = $opt['pathway_mode'];
    $is_bidirectional = $opt['is_bidirectional'];
    $length = isset($opt["length"])? $opt["length"] : "";
    $traversal_time = isset($opt["traversal_time"])? $opt["traversal_time"] : "";
    $stair_count = isset($opt["stair_count"])? $opt["stair_count"] : '';
    $max_slope = isset($opt["max_slope"])? $opt["max_slope"] : "";
    $min_width = isset($opt["min_width"])? $opt["min_widht"] : "";
    $signposted_as = isset($opt["signposted_as"])? $opt["signposted_as"] : "";
    $reversed_signposted_as = isset($opt["reversed_signposted_as"]) ? $opt["reversed_signposted_as"] : "";

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO pathways
        (pathway_id, from_stop_id, to_stop_id, pathway_mode, is_bidirectional, length, traversal_time, stair_count, max_slope, min_width, signposted_as, reversed_signposted_as)
        VALUES
        (?,?,?,?,?,?,?,?,?,?,?,?)
        ");
    $req->execute(array($pathway_id, $from_stop_id, $to_stop_id, $pathway_mode, $is_bidirectional, $length, $traversal_time, $stair_count, $max_slope, $min_width, $signposted_as, $reversed_signposted_as));
    return $req;
}

function addLevels($opt) {
    // create a function to add levels in the database with parameters in $opt associative array :
    // level_id, level_index, level_name
    // where level_id and level_index required. Trow an exception if required parameter are missing.
    // other parameters are optional and defined by default to ''

    if (!isset($opt["level_id"])) {
        throw new Exception("level_id is required");
    }

    if (!isset($opt["level_index"])) {
        throw new Exception("level_index is required");
    }

    $level_id = $opt["level_id"];
    $level_index = $opt["level_index"];
    $level_name = isset($opt["level_name"]) ? $opt["level_name"] : '';

    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO levels
        (level_id, level_index, level_name)
        VALUES
        (?,?,?)
        ");
    $req->execute(array($level_id, $level_index, $level_name));
    return $req;
}
