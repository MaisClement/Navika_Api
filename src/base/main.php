<?php

// Connexion à la base de données
//
include('credential.php');
include('data/http_code.php');

$db = new pdo($dsn, $usr, $psw);

$GLOBALS["db"] = $db;

?>