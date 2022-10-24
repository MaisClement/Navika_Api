<?php

$json = [];
//Object:ReportMessage
$json['message'] = createReportMessage("Navika !", "Bienvenue sur l'api Navika !");


echo json_encode($json);

?>