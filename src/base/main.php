<?php

// Connexion à la base de données
//
include_once('credential.php');
include_once('function.php');
include_once('gbfs_request.php');
include_once('gtfs_request.php');
include_once('request.php');
include_once('src/data/http_code.php');
include_once('src/data/lines.php');

$db = new pdo($dsn, $usr, $psw);

try {
    $conn = new PDO($dsn, $usr, $psw);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    print("Error connecting to SQL");
    die(print_r($e));
}

?>

