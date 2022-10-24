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
            'message'   =>  (String)    $GLOBALS['HTTP_CODE'][$http_code]['message'],
            'details'   =>  (String)    $details == '' ? $GLOBALS['HTTP_CODE'][$http_code]['details'] : $details,
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
            "id"         =>  (String)    $line->id,
            "code"       =>  (String)    $line->code,
            "name"       =>  (String)    $line->name,
            "mode"       =>  (String)    $line->commercial_mode->id,
            "color"      =>  (String)    $line->color,
            "text_color" =>  (String)    $line->text_color,
        );
    }
    return $list;
}

?>