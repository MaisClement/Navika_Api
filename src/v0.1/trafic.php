<?php

$fichier = '../data/cache/trafic.json';

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 30)) {
    echo file_get_contents($fichier);
    exit;
}

// ------------

$url = 'https://prim.iledefrance-mobilites.fr/marketplace/navitia/coverage/fr-idf/line_reports?forbidden_uris[]=commercial_mode:Bus';
$results = curl_Navitia($url);
$results = json_decode($results);

$reports = [];
foreach($results->disruptions as $disruption) {
    if ($disruption->status == 'past'){
        // On en veut pas on est raciste :D
    } else {
        $reports[$disruption->id] = array(
            "id"            =>  (String)    $disruption->id,
            "status"        =>  (String)    $disruption->status,
            "cause"         =>  (String)    $disruption->cause,
            "category"      =>  (String)    $disruption->category,
            "severity"      =>  (int)       getSeverity( $disruption->severity->effect, $disruption->cause, $disruption->status ),
            "effect"        =>  (String)    $disruption->severity->effect,
            "updated_at"    =>  (String)    $disruption->updated_at,
            "message"       =>  array(
                "title"     =>      getReportsMesageTitle( $disruption->messages ),
                "text"      =>      getReportsMesageText( $disruption->messages ),
            ),
        );
    }
}

$lines = [];
foreach($results->line_reports as $line) {
    $current_trafic = [];
    $current_work = [];
    $future_work = [];
    $severity = 0;

    foreach($line->line->links as $link) {
        $id = $link->id;
        if ($link->type == "disruption") {
            if (isset($reports[$id])) {
                if ($reports[$id]["severity"] == 2) {
                    $severity = $severity > $reports[$id]["severity"] ? $severity : $reports[$id]["severity"];
                    $future_work[] = $reports[$id];
                    
                } else if ($reports[$id]["severity"] == 3) {
                    $severity = $severity > $reports[$id]["severity"] ? $severity : $reports[$id]["severity"];
                    $current_work[] = $reports[$id];
                    
                } else {
                    $severity = $severity > $reports[$id]["severity"] ? $severity : $reports[$id]["severity"];
                    $current_trafic[] = $reports[$id];

                }
            }
        }
    }
    
    $lines[] = array(
        "id"         =>  (String)    str_replace('line:', '', $line->line->id),
        "code"       =>  (String)    $line->line->code,
        "name"       =>  (String)    $line->line->name,
        "mode"       =>  (String)    $line->line->commercial_mode->id,
        "color"      =>  (String)    $line->line->color,
        "text_color" =>  (String)    $line->line->text_color,
        "severity"   =>  (int)       $severity,
        "reports"    =>  array(
            "current_trafic"    => $current_trafic,
            "current_work"      => $current_work,
            "future_work"       => $future_work
        )
    );
}

$echo["trafic"] = $lines;

$echo = json_encode($echo);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>