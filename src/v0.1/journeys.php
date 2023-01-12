<?php

$fichier = '../data/cache/journeys/';

if ( isset($_GET['from']) && isset($_GET['to']) ){

    $from = $_GET['from'];
    $from = urlencode( trim($from) );

    $to = $_GET['to'];
    $to = urlencode( trim($to) );

    $url = $BASE_URL . '/journeys?from=' . $from . '&to=' . $to . '&depth=3&data_freshness=realtime';
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
    
    $sections = [];
    foreach($result->sections as $section) {
        if (isset($section->display_informations)) {
            $informations = array(
                "direction" => array(
                    "id"        =>  (String)    $section->display_informations->direction,
                    "name"      =>  (String)    "",
                ),
                "id"            =>  (String)    $result->ItemIdentifier,
                "name"          =>  (String)    $section->display_informations->name,
                "mode"          =>  (String)    $section->display_informations->physical_mode,
                "trip_name"     =>  (String)    $section->display_informations->trip_short_name,
                "headsign"      =>  (String)    $section->display_informations->headsign,
                "description"   =>  (String)    $section->display_informations->description,
                "message"       =>  (String)    "",
                "line"     => array(
                    "id"         =>  (String)   $LINES[journeys_line_format($section->display_informations->physical_mode) . ' ' . $section->display_informations->code],
                    "code"       =>  (String)   $section->display_informations->code,
                    "name"       =>  (String)   $section->display_informations->network . ' ' . $section->display_informations->name,
                    "mode"       =>  (String)   journeys_line_format($section->display_informations->physical_mode),
                    "color"      =>  (String)   $section->display_informations->color,
                    "text_color" =>  (String)   $section->display_informations->text_color,
                ),
            );
        }
        $sections[] = array(
            "id"            =>  (String) $section->id,
            "type"          =>  (String) $section->type,
            "mode"          =>  (String) isset($section->mode) ? $section->mode : $section->type,
            "arrival_date_time"     =>  (String) $section->arrival_date_time,
            "departure_date_time"   =>  (String) $section->departure_date_time,
            "duration"      =>  (int) $section->duration,
            "informations"  => isset($section->display_informations) ? $informations : null,
            "from" => array(
                "id"        =>  (String)    $section->from->id,
                "name"      =>  (String)    $section->from->{$section->from->embedded_type}->name,
                "type"      =>  (String)    $section->from->embedded_type,
                "distance"  =>  (int)       isset($section->from->distance) ? $section->from->distance : 0,
                "zone"      =>  (int)       0,
                "town"      =>  (String)    getTownByAdministrativeRegions( $section->from->{$section->from->embedded_type}->administrative_regions ),
                "zip_code"  =>  (String)    getZipCodeByInsee( getZipByAdministrativeRegions( $section->from->{$section->from->embedded_type}->administrative_regions ) )->fetch()['zip_code'],
                "coord"     => array(
                    "lat"           =>  floatval( $section->from->{$section->from->embedded_type}->coord->lat ),
                    "lon"           =>  floatval( $section->from->{$section->from->embedded_type}->coord->lon ),
                ),
            ),
            "to" => array(
                "id"        =>  (String)    $section->to->id,
                "name"      =>  (String)    $section->to->{$section->to->embedded_type}->name,
                "type"      =>  (String)    $section->to->embedded_type,
                // "distance"  =>  (int)       isset($section->to->distance) ? $section->to->distance : 0,
                "zone"      =>  (int)       0,
                "town"      =>  (String)    getTownByAdministrativeRegions( $section->to->{$section->to->embedded_type}->administrative_regions ),
                "zip_code"  =>  (String)    getZipCodeByInsee( getZipByAdministrativeRegions( $section->to->{$section->to->embedded_type}->administrative_regions ) )->fetch()['zip_code'],
                "coord"     => array(
                    "lat"       =>  floatval( $section->to->{$section->to->embedded_type}->coord->lat ),
                    "lon"       =>  floatval( $section->to->{$section->to->embedded_type}->coord->lon ),
                ),
            ),
            "geojson"       => isset($section->geojson) ? $section->geojson : null,
        );
    }

    $journeys[] = array(
        "type"                  =>  (String) $result->type,
        "duration"              =>  (int) $result->duration,

        "requested_date_time"   => $result->requested_date_time,
        "departure_date_time"   => $result->departure_date_time,
        "arrival_date_time"     => $result->arrival_date_time,
        
        "nb_transfers"          =>  (int)    floatval( $result->type ),
        "co2_emission"          => $result->co2_emission,
        "distances"             => $result->distances,
        "sections"              => $sections
    );
}

$json = [];
$json['journeys'] = $journeys;
$echo = json_encode($json);
file_put_contents($fichier, $echo);
echo $echo;
exit;

?>