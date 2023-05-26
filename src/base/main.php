<?php

// Connexion à la base de données
//

$CONFIG = json_decode(file_get_contents('src/base/config.json'));

include_once('credential.php');
include_once('function.php');
include_once('gtfs_request.php');
include_once('request.php');

$bdd = $CONFIG->credentials->bdd;
$db = new pdo($bdd->dsn, $bdd->usr, $bdd->psw);

try {
    $conn = new PDO($bdd->dsn, $bdd->usr, $bdd->psw);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    print("Error connecting to SQL");
    die(print_r($e));
}
