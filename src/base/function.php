<?php

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


function getTown($lat, $lon){
    getTownByGeoPoint($lat, $lon);
}

?>