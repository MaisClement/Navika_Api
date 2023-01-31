<?php

$search = [" ", "-", "À", "Á", "Â", "Ã", "Ä", "Å", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ù", "Ú", "Û", "Ü", "Ý", "ß", "à", "á", "â", "ã", "ä", "å", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ñ", "ò", "ó", "ô", "õ", "ö", "ù", "ú", "û", "ü", "ý", "ÿ", "Ā", "ā", "Ă", "ă", "Ą", "ą", "Ć", "ć", "Ĉ", "ĉ", "Ċ", "ċ", "Č", "č", "Ď", "ď", "Đ", "đ", "Ē", "ē", "Ĕ", "ĕ", "Ė", "ė", "Ę", "ę", "Ě", "ě", "Ĝ", "ĝ", "Ğ", "ğ", "Ġ", "ġ", "Ģ", "ģ", "Ĥ", "ĥ", "Ħ", "ħ", "Ĩ", "ĩ", "Ī", "ī", "Ĭ", "ĭ", "Į", "į", "İ", "ı", "Ĵ", "ĵ", "Ķ", "ķ", "ĸ", "Ĺ", "ĺ", "Ļ", "ļ", "Ľ", "ľ", "Ŀ", "ŀ", "Ł", "ł", "Ń", "ń", "Ņ", "ņ", "Ň", "ň", "ŉ", "Ŋ", "ŋ", "Ō", "ō", "Ŏ", "ŏ", "Ő", "ő", "Œ", "œ", "Ŕ", "ŕ", "Ŗ", "ŗ", "Ř", "ř", "Ś", "ś", "Ŝ", "ŝ", "Ş", "ş", "Š", "š", "Ţ", "ţ", "Ť", "ť", "Ŧ", "ŧ", "Ũ", "ũ", "Ū", "ū", "Ŭ", "ŭ", "Ů", "ů", "Ű", "ű", "Ų", "ų", "Ŵ", "ŵ", "Ŷ", "ŷ", "Ÿ", "Ź", "ź", "Ż", "ż", "Ž", "ž", "ſ"];
$replace = ['', '', "A", "A", "A", "A", "A", "A", "C", "E", "E", "E", "E", "I", "I", "I", "I", "N", "O", "O", "O", "O", "O", "U", "U", "U", "U", "Y", "s", "a", "a", "a", "a", "a", "a", "c", "e", "e", "e", "e", "i", "i", "i", "i", "n", "o", "o", "o", "o", "o", "u", "u", "u", "u", "y", "y", "A", "a", "A", "a", "A", "a", "C", "c", "C", "c", "C", "c", "C", "c", "D", "d", "D", "d", "E", "e", "E", "e", "E", "e", "E", "e", "E", "e", "G", "g", "G", "g", "G", "g", "G", "g", "H", "h", "H", "h", "I", "i", "I", "i", "I", "i", "I", "i", "I", "i", "J", "j", "K", "k", "k", "L", "l", "L", "l", "L", "l", "L", "l", "L", "l", "N", "n", "N", "n", "N", "n", "N", "n", "N", "O", "o", "O", "o", "O", "o", "OE", "oe", "R", "r", "R", "r", "R", "r", "S", "s", "S", "s", "S", "s", "S", "s", "T", "t", "T", "t", "T", "t", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "W", "w", "Y", "y", "Y", "Z", "z", "Z", "z", "Z", "z", "s"];

function getGTFSHeader($row) {
    foreach($row as $tag){
        $col[] = trim($tag);
    }
    return $col;
}

function getCSVHeader($csv, $sep = ';'){
    $file = fopen($csv, 'r');
    while (!feof($file)) {
        $line[] = fgetcsv($file, 0, $sep);
        break;
    }
    fclose($file);
    return $line;
}

function insertGTFS ($type, $opt, $provider) {
    if ($type == 'agency.txt') {
        insertAgency($opt, $provider);
    } else if ($type == 'stops.txt') {
        insertStops($opt, $provider);
    } else if ($type == 'routes.txt') {
        insertRoutes($opt, $provider);
    } else if ($type == 'trips.txt') {
        insertTrips($opt, $provider);
    } else if ($type == 'stop_times.txt') {
        insertStopTimes($opt, $provider);
    } else if ($type == 'calendar.txt') {
        insertCalendar($opt, $provider);
    } else if ($type == 'calendar_dates.txt') {
        insertCalendarDates($opt, $provider);
    } else if ($type == 'fare_attributes.txt') {
        insertFareAttributes($opt, $provider);
    } else if ($type == 'fare_rules.txt') {
        insertFareRules($opt, $provider);
    } else if ($type == 'shapes.txt') {
        insertShapes($opt, $provider);
    } else if ($type == 'frequencies.txt') {
        insertFrequencies($opt, $provider);
    } else if ($type == 'transfers.txt') {
        insertTransfers($opt, $provider);
    } else if ($type == 'pathways.txt') {
        insertPathways($opt, $provider);
    } else if ($type == 'levels.txt') {
        insertLevels($opt, $provider);
    } else if ($type == 'feed_info.txt') {
        insertFeedInfo($opt, $provider);
    } else if ($type == 'translations.txt') {
        insertTranslations($opt, $provider);
    } else if ($type == 'attributions.txt') {
        insertAttributions($opt, $provider);
    } else {
        echo 'Unknow';
    }
}

function insertFile($type, $path, $header, $sep = ',', $provider = 'TEST') {
    $db = $GLOBALS["db"];

    $table = str_replace('.txt', '', $type);
    $path = realpath($path);

    $req = $db->prepare("
        LOAD DATA INFILE ? IGNORE
        INTO TABLE $table
        FIELDS
            TERMINATED BY ?
            ENCLOSED BY '\"'
        LINES
            TERMINATED BY  '\n'
        IGNORE 1 ROWS
        ($header)
        SET provider_id = ?
    ");
    $req->execute(array(
        $path,
        $sep,
        $provider,
    ));
    return $req;
}

function truncateStopRoute() {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        TRUNCATE stop_route;
    ");
    $req->execute(array( ));
    return $req;
}

function generateStopRoute() {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO stop_route
        (route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon)

        SELECT DISTINCT 
        R.route_id, R.route_short_name, R.route_long_name, R.route_type, R.route_color, R.route_text_color,
        S2.stop_id, S2.stop_name, S2.stop_name, S2.stop_lat, S2.stop_lon
        FROM routes R

        INNER JOIN trips T
        ON R.route_id = T.route_id

        INNER JOIN stop_times ST
        ON T.trip_id = ST.trip_id

        INNER JOIN stops S
        ON ST.stop_id = S.stop_id

        INNER JOIN stops S2
        ON S.parent_station = S2.stop_id;
    ");
    $req->execute(array( ));
    return $req;
}

function generateQueryRoute() {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        SET NAMES 'utf8' COLLATE 'utf8_unicode_ci';
        UPDATE stop_route SET stop_query_name = REPLACE(stop_query_name, '-', '');
        UPDATE stop_route SET stop_query_name = REPLACE(stop_query_name, ' ', '');
        UPDATE stop_route SET stop_query_name = REPLACE(stop_query_name, '\'', '');
        
        UPDATE stop_route SET town_query_name = REPLACE(town_query_name, '-', '');
        UPDATE stop_route SET town_query_name = REPLACE(town_query_name, ' ', '');
        UPDATE stop_route SET town_query_name = REPLACE(town_query_name, '\'', '');
    ");
    $req->execute(array( ));
    return $req;
}

function generateTownInStopRoute() {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        UPDATE stop_route SR 

        INNER JOIN town T
        ON ST_Contains(
            T.town_polygon,
            point(SR.stop_lat, SR.stop_lon)
        )
        
        SET SR.town_id = T.town_id,
            SR.town_name = T.town_name,
            SR.town_query_name = T.town_name;
    ");
    $req->execute(array( ));
    return $req;
}

function getStopsNotInArea() {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        SELECT *
        FROM stops S
        
        WHERE S.location_type = '0'
        AND S.parent_station = '';
    ");
    $req->execute(array( ));
    return $req;
}

function clearProviderData($provider_id){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        DELETE FROM agency           WHERE provider_id = ?;
        DELETE FROM stops            WHERE provider_id = ?;
        DELETE FROM routes           WHERE provider_id = ?;
        DELETE FROM trips            WHERE provider_id = ?;
        DELETE FROM stop_times       WHERE provider_id = ?;
        DELETE FROM calendar         WHERE provider_id = ?;
        DELETE FROM calendar_dates   WHERE provider_id = ?;
        DELETE FROM fare_attributes  WHERE provider_id = ?;
        DELETE FROM fare_rules       WHERE provider_id = ?;
        DELETE FROM frequencies      WHERE provider_id = ?;
        DELETE FROM transfers        WHERE provider_id = ?;
        DELETE FROM pathways         WHERE provider_id = ?;
        DELETE FROM levels           WHERE provider_id = ?;
        DELETE FROM feed_info        WHERE provider_id = ?;
        DELETE FROM translations     WHERE provider_id = ?;
        DELETE FROM attributions     WHERE provider_id = ?;
        TRUNCATE stop_route;
	");
    $req->execute(array($provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id));
    return $req;
}

function insertAgency($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	  INSERT INTO agency
	  (provider_id, agency_id, agency_name, agency_url, agency_timezone, agency_lang, agency_phone, agency_fare_url, agency_email)
	  VALUES
	  (?, ?, ?, ?, ?, ?, ?, ?, ?)
	  ");
    $req->execute(array(
        $provider,
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

function insertStops($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	  INSERT INTO stops
	  (provider_id, stop_id, stop_code, stop_name, stop_desc, stop_lat, stop_lon, zone_id, stop_url, location_type, parent_station, stop_timezone, wheelchair_boarding, level_id, platform_code)
	  VALUES
	  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
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
        isset($opt['wheelchair_boarding']) ? $opt['wheelchair_boarding'] : 0,
        isset($opt['level_id']) ? $opt['level_id'] : '',
        isset($opt['platform_code']) ? $opt['platform_code'] : '',
    ));
    return $req;
}

function insertRoutes($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	  INSERT INTO routes
	  (provider_id, route_id, agency_id, route_short_name, route_long_name, route_desc, route_type, route_url, route_color, route_text_color, route_sort_order, continuous_pickup, continuous_drop_off)
	  VALUES
	  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['route_id']) ? $opt['route_id'] : '',
        isset($opt['agency_id']) ? $opt['agency_id'] : '',
        isset($opt['route_short_name']) ? $opt['route_short_name'] : '',
        isset($opt['route_long_name']) ? $opt['route_long_name'] : '',
        isset($opt['route_desc']) ? $opt['route_desc'] : '',
        isset($opt['route_type']) ? $opt['route_type'] : '',
        isset($opt['route_url']) ? $opt['route_url'] : '',
        isset($opt['route_color']) ? $opt['route_color'] : '',
        isset($opt['route_text_color']) ? $opt['route_text_color'] : '',
        0, // route_sort_order
        isset($opt['continuous_pickup']) ? $opt['continuous_pickup'] : 1,
        isset($opt['continuous_drop_off']) ? $opt['continuous_drop_off'] : 1,
    ));
    return $req;
}

function insertTrips($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO trips
	(provider_id, route_id, service_id, trip_id, trip_headsign, trip_short_name, direction_id, block_id, shape_id, wheelchair_accessible, bikes_allowed)
	VALUES
	(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['route_id']) ? $opt['route_id'] : '',
        isset($opt['service_id']) ? $opt['service_id'] : '',
        isset($opt['trip_id']) ? $opt['trip_id'] : '',
        isset($opt['trip_headsign']) ? $opt['trip_headsign'] : '',
        isset($opt['trip_short_name']) ? $opt['trip_short_name'] : '',
        (string) isset($opt['direction_id']) ? $opt['direction_id'] : '',
        isset($opt['block_id']) ? $opt['block_id'] : '',
        isset($opt['shape_id']) ? $opt['shape_id'] : '',
        isset($opt['wheelchair_accessible']) ? $opt['wheelchair_accessible'] : 0,
        isset($opt['bikes_allowed']) ? $opt['bikes_allowed'] : 0,
    ));
    return $req;
}

function insertStopTimes($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO stop_times
	(provider_id, trip_id, arrival_time, departure_time, stop_id, stop_sequence, stop_headsign, pickup_type, drop_off_type, continuous_pickup, continuous_drop_off, shape_dist_traveled, timepoint)
	VALUES
	(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['trip_id']) ? $opt['trip_id'] : '',
        isset($opt['arrival_time']) ? $opt['arrival_time'] : '',
        isset($opt['departure_time']) ? $opt['departure_time'] : '',
        isset($opt['stop_id']) ? $opt['stop_id'] : '',
        isset($opt['stop_sequence']) ? $opt['stop_sequence'] : '',
        isset($opt['stop_headsign']) ? $opt['stop_headsign'] : '',
        isset($opt['pickup_type']) ? $opt['pickup_type'] : 0,
        isset($opt['drop_off_type']) ? $opt['drop_off_type'] : 0,
        isset($opt['continuous_pickup']) ? $opt['continuous_pickup'] : 1,
        isset($opt['continuous_drop_off']) ? $opt['continuous_drop_off'] : 1,
        isset($opt['shape_dist_traveled']) ? $opt['shape_dist_traveled'] : 0,
        isset($opt['timepoint']) ? $opt['timepoint'] : 1,
    ));
    return $req;
}

function insertCalendar($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO calendar
	(provider_id, service_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, start_date, end_date)
	VALUES
	(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['service_id']) ? $opt['service_id'] : '',
        isset($opt['monday']) ? $opt['monday'] : '',
        isset($opt['tuesday']) ? $opt['tuesday'] : '',
        isset($opt['wednesday']) ? $opt['wednesday'] : '',
        isset($opt['thursday']) ? $opt['thursday'] : '',
        isset($opt['friday']) ? $opt['friday'] : '',
        isset($opt['saturday']) ? $opt['saturday'] : '',
        isset($opt['sunday']) ? $opt['sunday'] : '',
        isset($opt['start_date']) ? $opt['start_date'] : '',
        isset($opt['end_date']) ? $opt['end_date'] : '',
    ));
    return $req;
}

function insertCalendarDates($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO calendar_dates
	(provider_id, service_id, `date`, exception_type)
	VALUES
	(?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['service_id']) ? $opt['service_id'] : '',
        isset($opt['date']) ? $opt['date'] : '',
        isset($opt['exception_type']) ? $opt['exception_type'] : '',
    ));
    return $req;
}

function insertFareAttributes($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO fare_attributes
	(provider_id, fare_id, price, currency_type, payment_method, transfers, agency_id, transfer_duration)
	VALUES
	(?, ?, ?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['fare_id']) ? $opt['fare_id'] : '',
        isset($opt['price']) ? $opt['price'] : '',
        isset($opt['currency_type']) ? $opt['currency_type'] : '',
        isset($opt['payment_method']) ? $opt['payment_method'] : '',
        isset($opt['transfers']) ? $opt['transfers'] : '',
        isset($opt['agency_id']) ? $opt['agency_id'] : '',
        isset($opt['transfer_duration']) ? $opt['transfer_duration'] : '',
    ));
    return $req;
}

function insertFareRules($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO fare_rules
	(provider_id, fare_id, route_id, origin_id, destination_id, contains_id)
	VALUES
	(?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['fare_id']) ? $opt['fare_id'] : '',
        isset($opt['route_id']) ? $opt['route_id'] : '',
        isset($opt['origin_id']) ? $opt['origin_id'] : '',
        isset($opt['destination_id']) ? $opt['destination_id'] : '',
        isset($opt['contains_id']) ? $opt['contains_id'] : '',
    ));
    return $req;
}

function insertShapes($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO shapes
	(provider_id, shape_id, shape_pt_lat, shape_pt_lon, shape_pt_sequence, shape_dist_traveled)
	VALUES
	(?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['shape_id']) ? $opt['shape_id'] : '',
        isset($opt['shape_pt_lat']) ? $opt['shape_pt_lat'] : '',
        isset($opt['shape_pt_lon']) ? $opt['shape_pt_lon'] : '',
        isset($opt['shape_pt_sequence']) ? $opt['shape_pt_sequence'] : '',
        isset($opt['shape_dist_traveled']) ? $opt['shape_dist_traveled'] : '',
    ));
    return $req;
}

function insertFrequencies($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO frequencies
	(provider_id, trip_id, start_time, end_time, headway_secs, exact_times)
	VALUES
	(?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['trip_id']) ? $opt['trip_id'] : '',
        isset($opt['start_time']) ? $opt['start_time'] : '',
        isset($opt['end_time']) ? $opt['end_time'] : '',
        isset($opt['headway_secs']) ? $opt['headway_secs'] : '',
        isset($opt['exact_times']) ? $opt['exact_times'] : '',
    ));
    return $req;
}

function insertTransfers($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO transfers
	(provider_id, from_stop_id, to_stop_id, transfer_type, min_transfer_time)
	VALUES
	(?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['from_stop_id']) ? $opt['from_stop_id'] : '',
        isset($opt['to_stop_id']) ? $opt['to_stop_id'] : '',
        isset($opt['transfer_type']) ? $opt['transfer_type'] : '',
        isset($opt['min_transfer_time']) ? $opt['min_transfer_time'] : '',
    ));
    return $req;
}

function insertPathways($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO pathways
	(provider_id, pathway_id, from_stop_id, to_stop_id, pathway_mode, is_bidirectional, length, traversal_time, stair_count, max_slope, min_width, signposted_as, reversed_signposted_as)
	VALUES
	(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['pathway_id']) ? $opt['pathway_id'] : '',
        isset($opt['from_stop_id']) ? $opt['from_stop_id'] : '',
        isset($opt['to_stop_id']) ? $opt['to_stop_id'] : '',
        isset($opt['pathway_mode']) ? $opt['pathway_mode'] : '',
        isset($opt['is_bidirectional']) ? $opt['is_bidirectional'] : '',
        isset($opt['length']) ? $opt['length'] : '',
        isset($opt['traversal_time']) ? $opt['traversal_time'] : '',
        isset($opt['stair_count']) ? $opt['stair_count'] : '',
        isset($opt['max_slope']) ? $opt['max_slope'] : '',
        isset($opt['min_width']) ? $opt['min_width'] : '',
        isset($opt['signposted_as']) ? $opt['signposted_as'] : '',
        isset($opt['reversed_signposted_as']) ? $opt['reversed_signposted_as'] : '',
    ));
    return $req;
}

function insertLevels($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO levels
	(provider_id, level_id, level_index, level_name)
	VALUES
	(?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['level_id']) ? $opt['level_id'] : '',
        isset($opt['level_index']) ? $opt['level_index'] : '',
        isset($opt['level_name']) ? $opt['level_name'] : '',
    ));
    return $req;
}

function insertFeedInfo($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO feed_info
	(provider_id, feed_publisher_name, feed_publisher_url, feed_lang, default_lang, feed_start_date, feed_end_date, feed_version, feed_contact_email, feed_contact_url)
	VALUES
	(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['feed_publisher_name']) ? $opt['feed_publisher_name'] : '',
        isset($opt['feed_publisher_url']) ? $opt['feed_publisher_url'] : '',
        isset($opt['feed_lang']) ? $opt['feed_lang'] : '',
        isset($opt['default_lang']) ? $opt['default_lang'] : '',
        isset($opt['feed_start_date']) ? $opt['feed_start_date'] : '',
        isset($opt['feed_end_date']) ? $opt['feed_end_date'] : '',
        isset($opt['feed_version']) ? $opt['feed_version'] : '',
        isset($opt['feed_contact_email']) ? $opt['feed_contact_email'] : '',
        isset($opt['feed_contact_url']) ? $opt['feed_contact_url'] : '',
    ));
    return $req;
}

function insertTranslations($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO translations
	(provider_id, table_name, field_name, language, translation, record_id, record_sub_id, field_value)
	VALUES
	(?, ?, ?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['table_name']) ? $opt['table_name'] : '',
        isset($opt['field_name']) ? $opt['field_name'] : '',
        isset($opt['language']) ? $opt['language'] : '',
        isset($opt['translation']) ? $opt['translation'] : '',
        isset($opt['record_id']) ? $opt['record_id'] : '',
        isset($opt['record_sub_id']) ? $opt['record_sub_id'] : '',
        isset($opt['field_value']) ? $opt['field_value'] : '',
    ));
    return $req;
}

function insertAttributions($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("
	INSERT INTO attributions
	(provider_id, attribution_id, agency_id, route_id, trip_id, organization_name, is_producer, is_operator, is_authority, attribution_url, attribution_email, attribution_phone)
	VALUES
	(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	");
    $req->execute(array(
        $provider,
        isset($opt['attribution_id']) ? $opt['attribution_id'] : '',
        isset($opt['agency_id']) ? $opt['agency_id'] : '',
        isset($opt['route_id']) ? $opt['route_id'] : '',
        isset($opt['trip_id']) ? $opt['trip_id'] : '',
        isset($opt['organization_name']) ? $opt['organization_name'] : '',
        isset($opt['is_producer']) ? $opt['is_producer'] : '',
        isset($opt['is_operator']) ? $opt['is_operator'] : '',
        isset($opt['is_authority']) ? $opt['is_authority'] : '',
        isset($opt['attribution_url']) ? $opt['attribution_url'] : '',
        isset($opt['attribution_email']) ? $opt['attribution_email'] : '',
        isset($opt['attribution_phone']) ? $opt['attribution_phone'] : '',
    ));
    return $req;
}

function insertStopRoute($opt) {
    $db = $GLOBALS["db"];

    $req = $db->prepare("
        INSERT INTO stop_route
        (route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, town_id, town_name, town_query_name)

        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $req->execute(array(
        isset($opt['route_id'])         ? $opt['route_id'] : '',
        isset($opt['route_short_name']) ? $opt['route_short_name'] : '',
        isset($opt['route_long_name'])  ? $opt['route_long_name'] : '',
        isset($opt['route_type'])       ? $opt['route_type'] : '',
        isset($opt['route_color'])      ? $opt['route_color'] : '',
        isset($opt['route_text_color']) ? $opt['route_text_color'] : '',
        isset($opt['stop_id'])          ? $opt['stop_id'] : '',
        isset($opt['stop_name'])        ? $opt['stop_name'] : '',
        isset($opt['stop_query_name'])  ? $opt['stop_query_name'] : '',
        isset($opt['stop_lat'])         ? $opt['stop_lat'] : '',
        isset($opt['stop_lon'])         ? $opt['stop_lon'] : '',
        
        isset($opt['town_id'])          ? $opt['town_id'] : '',
        isset($opt['town_name'])        ? $opt['town_name'] : '',
        isset($opt['town_query_name'])  ? $opt['town_query_name'] : '',
    ));
    return $req;
}

function deleteTable($opt, $provider){
    $db = $GLOBALS["db"];

    $req = $db->prepare("DELETE FROM $opt");
    $req->execute();
    return $req;
}
