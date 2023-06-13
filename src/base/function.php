<?php

// --- CURL ---
function curl_SNCF($url){
    $password = '';
    $user = $GLOBALS['SNCFKEY'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, []);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$password");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function curl_GARE($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            'ocp-apim-subscription-key: ' . $GLOBALS['SNCFGC']
        )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function curl_PRIM($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            'apiKey: ' . $GLOBALS['PRIMKEY']
        )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function curl($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, []);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// --- ERROR ---
function ErrorMessage($http_code, $details = ''){
    $config = $GLOBALS['CONFIG'];
    http_response_code($http_code);
    $json = array(
        'error' => array(
            'code'      =>  (int)       $http_code,
            'message'   =>  (string)    isset($config->http_code->$http_code) ? $config->http_code->$http_code : "",
            'details'   =>  (string)    $details == ''               ? "" : $details,
        )
    );
    echo json_encode($json);
    exit;
}

function checkRequiredParameter($parameters) {
    $missing = [];

    foreach ($parameters as $parametre) {
        if (!isset($_GET[$parametre]) || empty($_GET[$parametre])) {
            $missing[] = $parametre;
        }
    }

    if (!empty($missing)) {
        if(count($missing) > 1) {
            $message = "At least one required parameters are missing: " . implode(", ", $missing);
        } else {
            $message = "One required parameter is missing: " . implode(", ", $missing);
        }
        return $message;
    }
    return null;
}

function getTransportMode($l){
    $modes = array(
        0 => 'tram',
        1 => 'metro',
        2 => 'rail',
        3 => 'bus',
        4 => 'boat',
        5 => 'tram',
        6 => 'cable',
        7 => 'funicular',
        11 => 'bus',
        12 => 'monorail',

        99 => 'nationalrail',
        100 => 'rail',
        400 => 'metro',
        700 => 'bus',
        900 => 'tram',
        1400 => 'funicular',
    );

    return $modes[$l];
}

// --- Schedules ---
function prepareTime($dt){
    if ($dt == '') return '';
    $datetime = date_create($dt);
    
    if (is_bool($datetime)) {
        $timeArray = explode(':', $dt);

        $hours = intval($timeArray[0]);
        $minutes = intval($timeArray[1]);
        $seconds = intval($timeArray[2]);

        // 'cause GTFS time can be 25:00:00
        $hours %= 24;
        $minutes %= 60;
        $seconds %= 60;

        $datetime = new DateTime();
        $datetime->setTime($hours, $minutes, $seconds);
        $datetime->setTimezone(new DateTimeZone('Europe/Paris'));

        if ($timeArray[0] >= 24) {
            $datetime->modify('+1 day');
        }
    }

    return date_format($datetime, DATE_ISO8601);
}
function timeSort($a, $b){

    $ad = new DateTime($a['stop_date_time']->departure_date_time);
    $bd = new DateTime($b['stop_date_time']->departure_date_time);

    if ($ad == $bd) {
        return 0;
    }

    return $ad < $bd ? -1 : 1;
}
function timeSortSNCF($a, $b){

    $ad = new DateTime($a['stop_date_time']['base_departure_date_time']);
    $bd = new DateTime($b['stop_date_time']['base_departure_date_time']);

    if ($ad == $bd) {
        return 0;
    }

    return $ad < $bd ? -1 : 1;
}

// --- GetInfo ---
function getTownByAdministrativeRegions($administrative_regions){
    foreach ($administrative_regions as $region) {
        if ($region->level == 8) {
            return $region->name;
        }
    }
    return "";
}
function getZipByAdministrativeRegions($administrative_regions){
    foreach ($administrative_regions as $region) {
        if ($region->level == 8) {
            return substr($region->insee, 0, 2);
        }
    }
    return "";
}
function getPhysicalModes($physical_modes){
    $list = [];
    foreach ($physical_modes as $modes) {
        $list[] = $modes->id;
    }
    return $list;
}
function getAllLines($lines){
    $list = [];

    foreach ($lines as $line) {
        $list[] = array(
            "id"         =>  (string)    'IDFM:' . idfm_format($line->id),
            "code"       =>  (string)    $line->code,
            "name"       =>  (string)    $line->name,
            "mode"       =>  (string)    $line->commercial_mode->id,
            "color"      =>  (string)    $line->color,
            "text_color" =>  (string)    $line->text_color,
        );
    }
    usort($list, "order_line");
    return $list;
}


// --- Trafic message ---
function getReportsMesageTitle($messages){
    foreach ($messages as $message) {
        if ($message->channel->name == 'titre') {
            return $message->text;
        }
    }
    return '';
}
function getReportsMesageText($messages){
    $search = ['<br>', '</p>', "Plus d'informations sur le site ratp.fr", "Plus d’informations sur le site ratp.fr", "  "];
    $replace = [PHP_EOL, PHP_EOL, '', ' '];

    foreach ($messages as $message) {
        if ($message->channel->name == 'moteur') {
            $msg = str_replace($search, $replace, $message->text);
            $msg = strip_tags($msg);
            $msg = html_entity_decode($msg);
            return $msg;
        }
    }
    foreach ($messages as $message) {
        if ($message->channel->name == 'email') {
            $msg = str_replace($search, $replace, $message->text);
            $msg = strip_tags($msg);
            $msg = html_entity_decode($msg);
            return $msg;
        }
    }
    foreach ($messages as $message) {
        if ($message->channel->name != 'titre') {
            $msg = str_replace($search, $replace, $message->text);
            $msg = strip_tags($msg);
            $msg = html_entity_decode($msg);
            return $msg;
        }
    }
}
function getSeverity($effect, $cause, $status){
    if ($status == 'past') {
        return 0;
    } else if ($cause == 'information') {
        return 1;
    } else if ($status == 'future' && $cause == 'travaux') {
        return 2;
    } else if ($cause == 'travaux') {
        return 3;
    } else if ($status == 'future') {
        return 4;
    } else if (in_array($effect, array('REDUCED_SERVICE', 'SIGNIFICANT_DELAYS', 'DETOUR', 'ADDITIONAL_SERVICE', 'MODIFIED_SERVICE'))) {
        return 4;
    } else if (in_array($effect, array('NO_SERVICE', 'STOP_MOVED'))) {
        return 5;
    } else if (in_array($effect, array('UNKNOWN_EFFECT', 'OTHER_EFFECT', 'NO_EFFECT', 'ACCESSIBILITY_ISSUE'))) {
        return 1;
    } else {
        return 0;
    }
}

function clear_directory($dirPath){
    if (!is_dir($dirPath)) {
        echo ("$dirPath must be a directory");
        return;
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            clear_directory($file);
        } else {
            unlink($file);
        }
    }
}

function remove_directory($dirPath){
    if (!is_dir($dirPath)) {
        echo ("$dirPath must be a directory");
        return;
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            remove_directory($file);
            rmdir($file);
        } else {
            unlink($file);
        }
    }
}

function getDisruption($id, $disruptions){
    $echo = [];
    foreach ($disruptions as $disruption) {
        if ($disruption->id == $id) {
            $echo[] = array(
                "id"            => (string) $disruption->id,
                "status"        => (string) $disruption->status,
                "cause"            => (string) $disruption->cause,
                "category"        => "Incidents",
                "severity"        => getSeverityByEffect($disruption->severity->effect), // status non fourni pour eviter les réductions de severity
                "effect"        => (string) $disruption->severity->effect,
                "updated_at"    => (string) $disruption->updated_at,
                "impacted_stops" => $disruption->impacted_objects[0]->impacted_stops,
                "message"    => array(
                    "title"        => getTitleByEffect($disruption->severity->effect), // getReportsMesageTitle( $disruption->messages ),
                    "text"        => isset($disruption->messages[0]->text) ? $disruption->messages[0]->text : "", // getReportsMesageText( $disruption->messages ),
                ),
            );
        }
    }
    return $echo;
}

function listDisruption($disruptions){
    $echo = [];
    foreach ($disruptions as $disruption) {
        $echo[] = array(
            "id"            => (string) $disruption->id,
            "status"        => (string) $disruption->status,
            "cause"            => (string) $disruption->cause,
            "category"        => "Incidents",
            "severity"        => getSeverityByEffect($disruption->severity->effect), // status non fourni pour eviter les réductions de severity
            "effect"        => (string) $disruption->severity->effect,
            "updated_at"    => (string) $disruption->updated_at,
            "impacted_stops" => $disruption->impacted_objects[0]->impacted_stops,
            "message"    => array(
                "title"        => getTitleByEffect($disruption->severity->effect), // getReportsMesageTitle( $disruption->messages ),
                "text"        => isset($disruption->messages[0]->text) ? $disruption->messages[0]->text : "", // getReportsMesageText( $disruption->messages ),
            ),
        );
    }
    return $echo;
}

function getTitleByEffect($effect){
    switch ($effect) {
        case 'SIGNIFICANT_DELAYS':
            return 'Retardé';
        case 'REDUCED_SERVICE':
            return 'Trajet modifié';
        case 'NO_SERVICE':
            return 'Supprimé';
        case 'MODIFIED_SERVICE':
            return 'Trajet modifié';
        case 'ADDITIONAL_SERVICE':
            return 'Train supplémentaire';
        case 'DETOUR':
            return 'Trajet modifié';
        default: // UNKNOWN_EFFECT et OTHER_EFFECT
            return "Trajet Perturbé";
    }
}
function getSeverityByEffect($effect){
    switch ($effect) {
        case 'SIGNIFICANT_DELAYS':
            return 4;
        case 'REDUCED_SERVICE':
            return 4;
        case 'NO_SERVICE':
            return 5;
        case 'MODIFIED_SERVICE':
            return 1;
        case 'ADDITIONAL_SERVICE':
            return 1;
        case 'DETOUR':
            return 4;
        default: // UNKNOWN_EFFECT et OTHER_EFFECT
            return 4;
    }
}

function getSNCFState($status, $level, $traffic){
    if ($level == "Normal") {
        return "ontime";
    }
    if ($status == 'SUPPRESSION')
        return "cancelled";
    if ($status == 'MODIFICATION_LIMITATION') {
        return "modified";
    }

    return "theorical";
}

function getMessage($call){
    // terminus - origin
    if (!isset($call->ExpectedDepartureTime) && !isset($call->AimedDepartureTime))
        return "terminus";

    else if (!isset($call->ExpectedArrivalTime) && !isset($call->AimedArrivalTime))
        return "origin";

    else
        return "";
}
function getSNCFid($links){
    foreach ($links as $link) {
        if ($link->type == 'vehicle_journey') {
            return $link->id;
        }
    }
}
function getLineId($links){
    foreach ($links as $link) {
        if ($link->type == 'line') {
            return $link->id;
        }
    }
}
function getState($call){
    // theorical - ontime - delayed - cancelled - modified
    if (isset($call->DepartureStatus) && ($call->DepartureStatus == "cancelled" || $call->DepartureStatus == "delayed"))
        return $call->DepartureStatus;

    if (isset($call->ArrivalStatus) && ($call->ArrivalStatus == "cancelled" || $call->ArrivalStatus == "delayed"))
        return $call->ArrivalStatus;

    if ((isset($call->DepartureStatus) && $call->DepartureStatus == "onTime") || (isset($call->ArrivalStatus) && $call->ArrivalStatus == "onTime"))
        return "ontime";

    return "theorical";
}

// --- ORDER ---
function order_line($a, $b){
    $type_list = [
        'nationalrail',
        'commercial_mode:Train',
        'rail',
        'commercial_mode:RapidTransit',
        'commercial_mode:RailShuttle',
        'funicular',
        'commercial_mode:LocalTrain',
        'commercial_mode:LongDistanceTrain',
        'commercial_mode:Metro',
        'metro',
        'commercial_mode:RailShuttle',
        'commercial_mode:Tramway',
        'tram',
        'commercial_mode:Bus',
        'bus',
    ];

    $ta = array_search($a['mode'], $type_list);
    $tb = array_search($b['mode'], $type_list);

    if ($a['code'] == 'SNCF') return -1;
    else if ($b['code'] == 'SNCF') return +1;

    if ($ta != $tb) {
        return ($ta < $tb) ? -1 : 1;
    }

    $a = $a['code'];
    $b = $b['code'];

    if ($a == $b) {
        return 0;
    }

    if ($a == 'TER') return +1;
    else if ($b == 'TER') return -1;

    return ($a < $b) ? -1 : 1;
}
function order_departure($a, $b){
    $a = new DateTime($a['stop_date_time']['departure_date_time']);
    $b = new DateTime($b['stop_date_time']['departure_date_time']);

    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}
function order_reports($a, $b){
    $a = $a['severity'];
    $b = $b['severity'];

    if ($a == $b) {
        return 0;
    }
    return ($a > $b) ? -1 : 1;
}
function order_places($a, $b){
    $a_modes = count($a["modes"]);
    $b_modes = count($b["modes"]);

    if (in_array('nationalrail', $a["modes"])) {
        $a_modes++;
    }
    if (in_array('nationalrail', $b["modes"])) {
        $b_modes++;
    }

    if ($a_modes == $b_modes) {
        $a_lines = count($a["lines"]);
        $b_lines = count($b["lines"]);

        if ($a_lines == $b_lines) {
            return 0;
        }
        return ($a_lines < $b_lines) ? 1 : -1;
    }
    return ($a_modes < $b_modes) ? 1 : -1;
}

// --- FORMAT ---
function gare_format($id){
    $allowed_name = [
        "Gare de l'Est"
    ];

    if (in_array($id, $allowed_name))
        return $id;

    $id = str_replace('Gare de ', '', $id);
    $id = ucfirst($id);
    return $id;
}
function idfm_format($str){
    $search = [
        'stop_area',
        'stop_point',
        'stopArea',
        'StopPoint',
        'ADMIN:',
        'IDFM:',
        'STIF:',
        'SNCF:',
        'line',
        'Line',
        ':Q:',
        ':BP:',
        '::',
        ':'
    ];
    $replace = '';
    return str_replace($search, $replace, $str);
}
function journeys_line_format($str){
    $search = ['Train Transilien'];
    $replace = ['Transilien'];

    return str_replace($search, $replace, $str);
}

// -------------------------------------

function read_csv($csv, $sep = ';'){
    $file = fopen($csv, 'r');
    while (!feof($file)) {
        $line[] = fgetcsv($file, 0, $sep);
    }
    fclose($file);
    return $line;
}

function getGTFSlistFromApi($gtfs){
    $json = file_get_contents('https://transport.data.gouv.fr/api/datasets/' . $gtfs->url);
    $json = json_decode($json);

    $list = [];

    foreach ($json->history as $history) {
        if ($history->payload->format == 'GTFS') {
            return array(
                'provider_id'   => $gtfs->id,
                'slug'          => $json->aom->name,
                'title'         => $gtfs->name,
                'type'          => $history->payload->format,
                'url'           => $history->payload->resource_url,
                'filenames'     => $history->payload->filenames,
                'updated'       => date('Y-m-d H:i:s', strtotime($history->updated_at)),
                'flag'          => 0,
            );
        }
    }
}

// ---

function getGBFSstation($url, $provider_url, $id){

    $content = file_get_contents($url);
    $content = json_decode($content);

    foreach ($content->data->stations as $station) {
        insertStation(
            [
                'station_id'        =>  $id . ':' . $station->station_id,
                'station_name'      =>  $station->name,
                'station_lat'       =>  $station->lat,
                'station_lon'       =>  $station->lon,
                'station_capacity'  =>  $station->capacity,
            ],
            $provider_url
        );
    }
}

// ---

function unzip($zipname, $folder) {
    if (!is_file($zipname)) {
        throw new Exception('File not found');
    }
    if (!is_dir($folder)) {
        mkdir($folder);
    }

    $zip = new ZipArchive;
    try {
        $zip->open($zipname);
        $zip->extractTo($folder);
        $zip->close();

    } catch (ValueError $e) {
        echo '  ! Failed to unzip' . PHP_EOL;
    }
}