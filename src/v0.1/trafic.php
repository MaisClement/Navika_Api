<?php

$url = $CONFIG->prim_url . '/line_reports?count=100&forbidden_uris[]=commercial_mode:Bus';
$results = curl_PRIM( $url );
$results = json_decode( $results );

$reports = [];
foreach ( $results->disruptions as $disruption ) {
    if ( $disruption->status != 'past' ) {
        $reports[$disruption->id] = array(
            "id"            =>  (string)    $disruption->id,
            "status"        =>  (string)    $disruption->status,
            "cause"         =>  (string)    $disruption->cause,
            "category"      =>  (string)    $disruption->category,
            "severity"      =>  (int)       getSeverity( $disruption->severity->effect, $disruption->cause, $disruption->status ),
            "effect"        =>  (string)    $disruption->severity->effect,
            "updated_at"    =>  (string)    $disruption->updated_at,
            "message"       =>  array(
                "title"     =>      getReportsMesageTitle( $disruption->messages ),
                "text"      =>      trim( getReportsMesageText( $disruption->messages ) ),
            ),
        );
    }
}

$lines = [];
foreach ( $results->line_reports as $line ) {
    $current_trafic = [];
    $current_work = [];
    $future_work = [];
    $severity = 0;

    foreach ( $line->line->links as $link ) {
        $id = $link->id;
        if ( $link->type == "disruption" ) {

            if ( isset( $reports[$id] ) ) {

                $severity = $severity > $reports[$id]["severity"] ? $severity : $reports[$id]["severity"];

                if ( $reports[$id]["disruptions"] == 'future' ) {
                    $future_work[] = $reports[$id];

                } else if ( $reports[$id]["severity"] == 2 ) {
                    $future_work[] = $reports[$id];

                } else if ( $reports[$id]["severity"] == 3 ) {
                    $current_work[] = $reports[$id];
                    
                } else {
                    $current_trafic[] = $reports[$id];
                }
            }
        }
    }

    usort($current_trafic, "order_reports");
    usort($current_work, "order_reports");
    usort($future_work, "order_reports");

    $lines[] = array(
        "id"         =>  (string)    'IDFM:' . idfm_format($line->line->id),
        "code"       =>  (string)    $line->line->code,
        "name"       =>  (string)    $line->line->name,
        "mode"       =>  (string)    $line->line->commercial_mode->id,
        "color"      =>  (string)    $line->line->color,
        "text_color" =>  (string)    $line->line->text_color,
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
echo $echo;
