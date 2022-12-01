<?php

$fichier = '../data/cache/journeys/';

if ( isset($_GET['from']) && isset($_GET['to']) ){

    $from = $_GET['from'];
    $from = urlencode( trim($from) );

    $to = $_GET['to'];
    $to = urlencode( trim($to) );

    $url = $BASE_URL . '/journeys?from=' . $from . '&to=' . $to . '&depth=2';
    $fichier .= $from . '_' . $to . '.json';

} else {
    ErrorMessage(
        400,
        'Required parameter "from" and "to" is missing or null.'
    );
}

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 60)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------

$results = curl_PRIM($url);
$results = json_decode($results);

$journeys = [];
foreach($results->journeys as $result){
    echo json_encode($result);
    exit;
    
}

$json = [];
$echo = json_encode($json);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>