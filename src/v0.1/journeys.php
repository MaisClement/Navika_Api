<?php

$fichier = '../data/cache/journeys_';

if (isset($_GET['from']) && isset($_GET['to'])) {
    $from = $_GET['from'];
    $from = urlencode(trim($from));

    $to = $_GET['to'];
    $to = urlencode(trim($to));

    $datetime = $_GET['datetime'] ?? date("c");
    $datetime = urlencode(trim($datetime));

    // echo $datetime; exit;

    $url = $BASE_URL . '/journeys?from=' . $from . '&to=' . $to . '&datetime=' . $datetime . '&depth=3&data_freshness=realtime';
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
foreach ($results->journeys as $result) {

    $sections = [];
    foreach ($result->sections as $section) {
        if (isset($section->display_informations)) {
            $informations = array(
                "direction" => array(
                    "id"        =>  (string)    $section->display_informations->direction,
                    "name"      =>  (string)    "",
                ),
                "id"            =>  (string)    $result->ItemIdentifier,
                "name"          =>  (string)    $section->display_informations->name,
                "mode"          =>  (string)    $section->display_informations->physical_mode,
                "trip_name"     =>  (string)    $section->display_informations->trip_short_name,
                "headsign"      =>  (string)    $section->display_informations->headsign,
                "description"   =>  (string)    $section->display_informations->description,
                "message"       =>  (string)    "",
                "line"     => array(
                    "id"         =>  (string)   idfm_format( getLineId($section->links) ), // $LINES[journeys_line_format($section->display_informations->physical_mode) . ' ' . $section->display_informations->code],
                    "code"       =>  (string)   $section->display_informations->code,
                    "name"       =>  (string)   $section->display_informations->network . ' ' . $section->display_informations->name,
                    "mode"       =>  (string)   journeys_line_format($section->display_informations->physical_mode),
                    "color"      =>  (string)   $section->display_informations->color,
                    "text_color" =>  (string)   $section->display_informations->text_color,
                ),
            );
        }
        $sections[] = array(
            "id"            =>  (string) $section->id,
            "type"          =>  (string) $section->type,
            "mode"          =>  (string) isset($section->mode) ? $section->mode : $section->type,
            "arrival_date_time"     =>  (string) $section->arrival_date_time,
            "departure_date_time"   =>  (string) $section->departure_date_time,
            "duration"      =>  (int) $section->duration,
            "informations"  => isset($section->display_informations) ? $informations : null,
            "from" => array(
                "id"        =>  (string)    $section->from->id,
                "name"      =>  (string)    $section->from->{$section->from->embedded_type}->name,
                "type"      =>  (string)    $section->from->embedded_type,
                "distance"  =>  (int)       isset($section->from->distance) ? $section->from->distance : 0,
                "zone"      =>  (int)       0,
                "town"      =>  (string)    getTownByAdministrativeRegions($section->from->{$section->from->embedded_type}->administrative_regions),
                "zip_code"  =>  (string)    substr(getZipByAdministrativeRegions($section->from->{$section->from->embedded_type}->administrative_regions), 0, 2),
                "coord"     => array(
                    "lat"           =>  floatval($section->from->{$section->from->embedded_type}->coord->lat),
                    "lon"           =>  floatval($section->from->{$section->from->embedded_type}->coord->lon),
                ),
            ),
            "to" => array(
                "id"        =>  (string)    $section->to->id,
                "name"      =>  (string)    $section->to->{$section->to->embedded_type}->name,
                "type"      =>  (string)    $section->to->embedded_type,
                "zone"      =>  (int)       0,
                "town"      =>  (string)    getTownByAdministrativeRegions($section->to->{$section->to->embedded_type}->administrative_regions),
                "zip_code"  =>  (string)    substr(getZipByAdministrativeRegions($section->to->{$section->to->embedded_type}->administrative_regions), 0, 2),
                "coord"     => array(
                    "lat"       =>  floatval($section->to->{$section->to->embedded_type}->coord->lat),
                    "lon"       =>  floatval($section->to->{$section->to->embedded_type}->coord->lon),
                ),
            ),
            "stop_date_times"   => isset($section->stop_date_times) ? $section->stop_date_times : null,
            "geojson"           => isset($section->geojson)         ? $section->geojson         : null,
        );
    }

    $journeys[] = array(
        "type"                  =>  (string) $result->type,
        "duration"              =>  (int) $result->duration,

        "requested_date_time"   => $result->requested_date_time,
        "departure_date_time"   => $result->departure_date_time,
        "arrival_date_time"     => $result->arrival_date_time,

        "nb_transfers"          =>  (int)    floatval($result->type),
        "co2_emission"          => $result->co2_emission,
        "fare"                  => $result->fare,
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
