<?php

function curl_PRIM( $url ) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, 
        array(
            'apiKey: FxOUH4z0kwaBwtBDDVCJYfhKOADOk1CG'
        )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function curl( $url ) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, []);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function createReportMessage($title, $text) {
    $json = array(
        'title'         =>  (String)    $title,
        'text'          =>  (String)    $text,
    );
    return $json;
}

function ErrorMessage($http_code, $details = ''){
    http_response_code($http_code);
    $json = array(
        'error' => array(
            'code'      =>  (int)       $http_code,
            'message'   =>  (String)    isset($GLOBALS['HTTP_CODE']) ? $GLOBALS['HTTP_CODE'] : "",
            'details'   =>  (String)    $details == ''               ? $GLOBALS['HTTP_CODE'] : "",
        )
    );
    echo json_encode($json);
    exit;
}

function getTownByAdministrativeRegions($administrative_regions) {
    foreach($administrative_regions as $region){
        if ($region->level == 8){
            return $region->name;
        }
    }
    return "";
}

function getZipByAdministrativeRegions($administrative_regions) {
    foreach($administrative_regions as $region){
        if ($region->level == 8){
            return $region->insee;
        }
    }
    return "";
}

function getPhysicalModes($physical_modes) {
    $list = [];
    foreach($physical_modes as $modes){
        $list[] = $modes->id;
    }
    return $list;
}

function getAllLines ($lines){
    $list = [];

    foreach($lines as $line){
        $list[] = array(
            "id"         =>  (String)    idfm_format( $line->id ),
            "code"       =>  (String)    $line->code,
            "name"       =>  (String)    $line->name,
            "mode"       =>  (String)    $line->commercial_mode->id,
            "color"      =>  (String)    $line->color,
            "text_color" =>  (String)    $line->text_color,
        );
    }
    usort($list, "order_line");
    return $list;
}

function getReportsMesageTitle( $messages ) {
    foreach($messages as $message) {
        if ($message->channel->name == 'titre') {
            return $message->text;
        }
    }
    return '';
}
function getReportsMesageText( $messages ) {
    $search = ['<br>', '</p>'];
    $replace = [PHP_EOL, PHP_EOL];

    foreach($messages as $message) {
        if ($message->channel->name == 'moteur') {
            $msg = str_replace($search, $replace, $message->text);
            $msg = strip_tags($msg);
            $msg = html_entity_decode($msg);
            return $msg;

        } else if ($message->channel->name == 'email') {
            $msg = str_replace($search, $replace, $message->text);
            $msg = strip_tags($msg);
            $msg = html_entity_decode($msg);
            return $msg;

        } else if ($message->channel->name != 'titre') {
            $msg = str_replace($search, $replace, $message->text);
            $msg = strip_tags($msg);
            $msg = html_entity_decode($msg);
            return $msg;
            
        }
    }
}

function getSeverity( $effect, $cause, $status ) {
    if ($status == 'past'){
        return 0;

    } else if ($status == 'future'){
        return 2;

    } else if ($cause == 'travaux') {
        return 3;

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

function remove_directory($dirPath){
    if (!is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            remove_directory($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

function clear_directory($dirPath){
    if (!is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
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

function ifisset($a, $b) {
    if ( isset($a) )
        return $a;
    
    else if ( isset($b) )
        return $b;
        
    else return "";
    
}

function getMessage($call) {
    // terminus - origin
    if (!isset($call->ExpectedDepartureTime) && !isset($call->AimedDepartureTime))
        return "terminus";

    else if (!isset($call->ExpectedArrivalTime) && !isset($call->AimedArrivalTime))
        return "origin";

    else
        return "";
}

function getState($call) {
    // theorical - ontime - delayed - cancelled - modified
    if (isset($call->DepartureStatus) && ($call->DepartureStatus == "cancelled" || $call->DepartureStatus == "delayed"))
        return $call->DepartureStatus;

    if (isset($call->ArrivalStatus) && ($call->ArrivalStatus == "cancelled" || $call->ArrivalStatus == "delayed"))
        return $call->ArrivalStatus;

    if ((isset($call->DepartureStatus) && $call->DepartureStatus == "onTime") || (isset($call->ArrivalStatus) && $call->ArrivalStatus == "onTime"))
        return "ontime";
    
    return "theorical";
}

// ------

function order_line($a, $b) {
    //type
    $type_list = [
        'commercial_mode:Train' ,
        'commercial_mode:RapidTransit' ,
        'commercial_mode:RailShuttle' ,
        'commercial_mode:LocalTrain' ,
        'commercial_mode:LongDistanceTrain' ,
        'commercial_mode:Metro' ,
        'commercial_mode:RailShuttle' ,
        'commercial_mode:Tramway' ,
        'commercial_mode:Bus' ,
    ];

    $ta = array_search($a['mode'], $type_list);
    $tb = array_search($b['mode'], $type_list);

    if ($ta != $tb) {
        return ($ta < $tb) ? -1 : 1;
    }
    
    $a = $a['code'];
    $b = $b['code'];

    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}
function order_departure($a, $b) {
    $a = new DateTime($a['stop_date_time']['departure_date_time']);
    $b = new DateTime($b['stop_date_time']['departure_date_time']);

    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}

function gare_format($id) {
    $allowed_name = [
        "Gare de l'Est"
    ];

    if (in_array($id, $allowed_name))
        return $id;

    $id = str_replace('Gare de ', '', $id);
    $id = ucfirst($id);
    return $id;
}

function idfm_format($str) {
    $search = [
        'stop_area', 
        'stop_point', 
        'IDFM', 
        'STIF', 
        'line', 
        'Line',
        '::',
        ':'
    ];
    $replace = '';
    return str_replace($search, $replace, $str);
}

?>