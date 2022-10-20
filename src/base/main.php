<?php

// Connexion à la base de données
//

include('base/credential.php');

$db = new pdo($dsn, $usr, $psw);

$GLOBALS["db"] = $db;

// Code d'erreur HTTP
//

$HTTP_CODE = [];
$HTTP_CODE[100] = array(
    'message' => 'Continue',
    'details' => '',
);
$HTTP_CODE[101] = array(
    'message' => 'Switching Protocols',
    'details' => '',
);
$HTTP_CODE[200] = array(
    'message' => 'OK',
    'details' => '',
);
$HTTP_CODE[201] = array(
    'message' => 'Created',
    'details' => '',
);
$HTTP_CODE[202] = array(
    'message' => 'Accepted',
    'details' => '',
);
$HTTP_CODE[203] = array(
    'message' => 'Non-Authoritative Information',
    'details' => '',
);
$HTTP_CODE[204] = array(
    'message' => 'No Content',
    'details' => '',
);
$HTTP_CODE[205] = array(
    'message' => 'Reset Content',
    'details' => '',
);
$HTTP_CODE[206] = array(
    'message' => 'Partial Content',
    'details' => '',
);
$HTTP_CODE[300] = array(
    'message' => 'Multiple Choices',
    'details' => '',
);
$HTTP_CODE[301] = array(
    'message' => 'Moved Permanently',
    'details' => 'The endpoint you requested has moved, refers to the documentation.',
);
$HTTP_CODE[302] = array(
    'message' => 'Moved Temporarily',
    'details' => 'The endpoint you requested has moved, refers to the documentation.',
);
$HTTP_CODE[303] = array(
    'message' => 'See Other',
    'details' => '',
);
$HTTP_CODE[304] = array(
    'message' => 'Not Modified',
    'details' => '',
);
$HTTP_CODE[305] = array(
    'message' => 'Use Proxy',
    'details' => '',
);
$HTTP_CODE[400] = array(
    'message' => 'Bad Request',
    'details' => '',
);
$HTTP_CODE[401] = array(
    'message' => 'Unauthorized',
    'details' => '',
);
$HTTP_CODE[402] = array(
    'message' => 'Payment Required',
    'details' => '',
);
$HTTP_CODE[403] = array(
    'message' => 'Forbidden',
    'details' => '',
);
$HTTP_CODE[404] = array(
    'message' => 'Not Found',
    'details' => '',
);
$HTTP_CODE[406] = array(
    'message' => 'Not Acceptable',
    'details' => '',
);
$HTTP_CODE[407] = array(
    'message' => 'Proxy Authentication Required',
    'details' => '',
);
$HTTP_CODE[408] = array(
    'message' => 'Request Time-out',
    'details' => '',
);
$HTTP_CODE[409] = array(
    'message' => 'Conflict',
    'details' => '',
);
$HTTP_CODE[410] = array(
    'message' => 'Gone',
    'details' => '',
);
$HTTP_CODE[411] = array(
    'message' => 'Length Required',
    'details' => '',
);
$HTTP_CODE[412] = array(
    'message' => 'Precondition Failed',
    'details' => '',
);
$HTTP_CODE[413] = array(
    'message' => 'Request Entity Too Large',
    'details' => '',
);
$HTTP_CODE[414] = array(
    'message' => 'Request-URI Too Large',
    'details' => '',
);
$HTTP_CODE[415] = array(
    'message' => 'Unsupported Media Type',
    'details' => '',
);
$HTTP_CODE[429] = array(
    'message' => 'Too Many Requests',
    'details' => '',
);
$HTTP_CODE[500] = array(
    'message' => 'Internal Server Error',
    'details' => '',
);
$HTTP_CODE[501] = array(
    'message' => 'Not Implemented',
    'details' => '',
);
$HTTP_CODE[502] = array(
    'message' => 'Bad Gateway',
    'details' => '',
);
$HTTP_CODE[503] = array(
    'message' => 'Service Unavailable',
    'details' => '',
);
$HTTP_CODE[504] = array(
    'message' => 'Gateway Time-out',
    'details' => '',
);
$HTTP_CODE[505] = array(
    'message' => 'HTTP Version not supported',
    'details' => '',
);

?>