<?php

include_once ('base/main.php');
include_once ('base/function.php');
include_once ('base/request.php');
include_once ('base/gtfs_request.php');

include_once ('data/sncf_forbidden_dept.php');

$dossier = '../data/file/gtfs/';

echo '> Import SNCF...'. PHP_EOL;

echo '   > https://ressources.data.sncf.com/explore/dataset/referentiel-gares-voyageurs/download/?format=csv&timezone=Europe/Berlin&lang=fr' . PHP_EOL;
    $sncf = file_get_contents('https://ressources.data.sncf.com/explore/dataset/referentiel-gares-voyageurs/download/?format=csv&timezone=Europe/Berlin&lang=fr');
    file_put_contents($dossier . 'sncf.csv', $sncf);

    $sncf = read_csv($dossier . 'sncf.csv');
    
    foreach ($sncf as $row) {
        if ($row[0] != 'code' && $row[1] != '' && $row[1] != false) {

            $stop = array(
                'route_id'          => 'SNCF',  
                'route_short_name'  => 'SNCF',  
                'route_long_name'   => 'Trains SNCF',  
                'route_type'        => '100',  
                'route_color'       => 'aaaaaa',  
                'route_text_color'  => '000000',  
                'stop_id'           => 'SNCF:' . substr($row[2], 2),   // CODE UIC
                'stop_name'         => $row[4],  
                'stop_query_name'   => $row[4],  
                'stop_lat'          => isset($row[11]) ? $row[11] : '',  
                'stop_lon'          => isset($row[10]) ? $row[10] : '',
                'town_id'           => $row[8] . $row[6],
                'town_name'         => $row[7],
                'town_query_name'   => $row[7],
                'departement'       => $row[8],
            );

            $allowed = true;
            if (in_array($stop['departement'], $SNCF_FORBIDDEN_DEPT)) {
                $allowed = false;
                // echo $stop_id . ' - ' . $stop['stop_name'] . ' - Departement non autorisé' . PHP_EOL;
            } 
            if (in_array($stop['stop_name'], $SNCF_FORBIDDEN)) {
                $allowed = false;
                // echo $stop_id . ' - ' . $stop['stop_name'] . ' - Nom non autorisé' . PHP_EOL;
            } 
            if (in_array($stop['stop_name'], $SNCF_FORCE))
                $allowed = true;

            if ($allowed == true) {
                try {
                    insertStopRoute($stop);
                } catch (Exception $e) {
                    echo $e;
                }
            }
        } 
    }

?>