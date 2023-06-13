<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=utf-8');
header("Content-type:application/json");

session_start();

function error_handle($errno, $errstr, $errfile, $errline)
{
    $report = '### Error ###' . PHP_EOL;
    $report .= "[$errno] $errstr" . PHP_EOL;
    $report .= "Error on line $errline in $errfile";
    $report .= PHP_EOL . PHP_EOL . '### SESSION ###' . PHP_EOL;
    $report .= print_r($_SESSION, true);
    $report .= PHP_EOL . PHP_EOL . '### SERVER ###' . PHP_EOL;
    $report .= print_r($_SERVER, true);
    $report .= PHP_EOL . PHP_EOL . '### POST ###' . PHP_EOL;
    $report .= print_r($_POST, true);
    $report .= PHP_EOL . PHP_EOL . '### GET ###' . PHP_EOL;
    $report .= print_r($_GET, true);
    $report .= PHP_EOL . PHP_EOL . '### COOKIE ###' . PHP_EOL;
    $report .= print_r($_COOKIE, true);

    $name = md5(print_r($ex, true));
    file_put_contents('../data/report/' . $name . '_exception.json', $report);
}

if (isset($_GET['debug']) && $_GET['debug'] == 'y') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    set_error_handler("error_handle");
}

//------------

$path = 'src/';

//------------

$uri = urldecode($_SERVER['REQUEST_URI']);

if (strpos($uri, "?"))
    $uri = substr($uri, 0, strpos($uri, "?"));

if ($uri == '' || $uri == "/" || $uri == "\\")
    $uri = 'index';

//------------

$file = $path . $uri;
$file_php = $file . '.php';

//------------

include('src/base/main.php');

//------------
try {
    if (strpos($file, "/base/") || strpos($file, "/data/") || strpos($file, "/back/") || strpos($file, "/auto/")) {
        ErrorMessage(403);
        // echo $file;

    } else if (is_file($file_php)) {
        chdir($path);
        include($file_php);
        exit;
    } else if (is_file($file)) {
        header('Content-Type: ' . mime_content_type($file));

        chdir($path);
        include($file);
        exit;
    } else {
        ErrorMessage(404);
    }
} catch (Exception $ex) {
    $report = '### Exception ###' . PHP_EOL;
    $report .= print_r($ex, true);
    $report .= PHP_EOL . PHP_EOL . '### SESSION ###' . PHP_EOL;
    $report .= print_r($_SESSION, true);
    $report .= PHP_EOL . PHP_EOL . '### SERVER ###' . PHP_EOL;
    $report .= print_r($_SERVER, true);
    $report .= PHP_EOL . PHP_EOL . '### POST ###' . PHP_EOL;
    $report .= print_r($_POST, true);
    $report .= PHP_EOL . PHP_EOL . '### GET ###' . PHP_EOL;
    $report .= print_r($_GET, true);
    $report .= PHP_EOL . PHP_EOL . '### COOKIE ###' . PHP_EOL;
    $report .= print_r($_COOKIE, true);

    $name = md5(print_r($ex, true));
    file_put_contents('../data/report/' . $name . '_exception.json', $report);

    ErrorMessage(500);
}