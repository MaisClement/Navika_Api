<?php

$json = [];
//Object:ReportMessage
$json['message'] = createReportMessage("Navika !", "Bienvenu sur l'api Navika !");


echo json_encode($json);

?>