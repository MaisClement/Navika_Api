<?php

// Connexion à la base de données
//
include('credential.php');

$db = new pdo($dsn, $usr, $psw);

$GLOBALS["db"] = $db;

// Connexion MySqli
//
function sql_connect(){
  $servername = "127.0.0.1";

  $usr = $GLOBALS['usr'];
  $psw = $GLOBALS['psw'];

  if (!mysqli_connect($servername, $usr, $psw, 'Navika')) {
    ErrorMessage(500);
  } else {
    $mysqli = mysqli_connect($servername, $usr, $psw, 'Navika');
  }

  if ($mysqli->connect_error) {
    ErrorMessage(500);
  } else {
    mysqli_set_charset($mysqli, 'utf8mb4');
    return $mysqli;
  }
}

$mysqli = sql_connect();

?>