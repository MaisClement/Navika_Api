<?php

chdir('/var/www/navika/src');

include_once ('base/main.php');

$dossier = '../data/file/gtfs/';

$SNCF_FORBIDDEN_DEPT = array("75", "92", "93", "94", "77", "78", "91", "95");
$SNCF_FORCE = array("Bréval" ,"Gazeran" ,"Angerville" ,"Monnerville" ,"Guillerval");
$SNCF_FORBIDDEN = array("Crépy-en-Valois", "Château-Thierry", "Montargis", "Malesherbes", "Dreux", "Gisors", "Creil", "Le Plessis-Belleville", "Nanteuil-le-Haudouin ", "Ormoy-Villers", "Mareuil-sur-Ourcq", "La Ferté-Milon", "Nogent-l'Artaud - Charly", "Dordives", "Ferrières - Fontenay", "Marchezais - Broué", "Vernon - Giverny", "Trie-Château", "Chaumont-en-Vexin", "Liancourt-Saint-Pierre", "Lavilletertre", "Boran-sur-Oise", "Précy-sur-Oise", "Saint-Leu-d'Esserent", "Chantilly - Gouvieux", "Orry-la-Ville - Coye", "La Borne Blanche");


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
                'route_type'        => '99',  
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
            } 
            if (in_array($stop['stop_name'], $SNCF_FORBIDDEN)) {
                $allowed = false;
            } 
            if (in_array($stop['stop_name'], $SNCF_FORCE))
                $allowed = true;

            if ($allowed == true) {
                try {
                    insertTempStopRoute($stop);
                } catch (Exception $e) {
                    // echo $e;
                }
            }
        } 
    }

?>