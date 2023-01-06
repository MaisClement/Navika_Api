<?php

// Connexion à la base de données
//
include('credential.php');
include('data/http_code.php');
include('data/lines.php');
include('data/sncf_forbidden_dept.php');

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