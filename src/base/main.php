<?php

// Connexion à la base de données
//
include('credential.php');

$db = new pdo($dsn, $usr, $psw);

$GLOBALS["db"] = $db;

// AutoLoad data
//
$autoload_files = glob("src/base/data/*.php");
foreach($autoload_files as $autoload_file) {
  include($autoload_file);
}

?>