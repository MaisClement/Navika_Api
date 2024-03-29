<?php

namespace App\Controller;
use DateTime;
use DateTimeZone;

class Functions
{
    public static function ErrorMessage($http_code, $details = ''){
        return array(
            'error' => array(
                'code'      =>  (int)       $http_code,
                'message'   =>  (string)    null, // isset($config->http_code->$http_code) ? $config->http_code->$http_code : "",
                'details'   =>  (string)    $details == ''               ? "" : $details,
            )
        );
    }

    public static function SuccessMessage($http_code, $details = ''){
        return array(
            'succes' => array(
                'code'      =>  (int)       $http_code,
                'message'   =>  (string)    null, // isset($config->http_code->$http_code) ? $config->http_code->$http_code : "",
                'details'   =>  (string)    $details == ''               ? "" : $details,
            )
        );
    }

    public static function getTransportMode($l){
        $modes = array(
            0 => 'tram',
            1 => 'metro',
            2 => 'rail',
            3 => 'bus',
            4 => 'boat',
            5 => 'tram',
            6 => 'cable',
            7 => 'funicular',
            11 => 'bus',
            12 => 'monorail',
    
            99 => 'nationalrail',
            100 => 'rail',
            400 => 'metro',
            700 => 'bus',
            900 => 'tram',
            1400 => 'funicular',
        );
    
        return $modes[$l];
    }

    public static function getTypeFromPelias($el){
        $search = [
            'venue',
        ];
        $replace = [
            'poi',
        ];

        return str_replace($search, $replace, $el);

    }

    public static function getTownByAdministrativeRegions($administrative_regions){
        foreach ($administrative_regions as $region) {
            if ($region->level == 8) {
                return $region->name;
            }
        }
        return "";
    }

    public static function getZipByAdministrativeRegions($administrative_regions){
        foreach ($administrative_regions as $region) {
            if ($region->level == 8) {
                return substr($region->insee, 0, 2);
            }
        }
        return "";
    }

    public static function getLineId($links){
        foreach ($links as $link) {
            if ($link->type == 'line') {
                return $link->id;
            }
        }
    }

    public static function getJourneyId($links){
        foreach ($links as $link) {
            if ($link->rel == 'this_journey') {
                return Functions::base64url_encode(substr( $link->href , strpos( $link->href , 'journeys')));
            }
        }
    }

    public static function base64url_encode($s) {
        return str_replace(array('+', '/'), array('-', '_'), base64_encode($s));
    }
    
    public static function base64url_decode($s) {
        return base64_decode(str_replace(array('-', '_'), array('+', '/'), $s));
    }

    public static function order_reports($array) {
        usort ($array, function($a, $b) {

            if ($a["severity"] != $b["severity"]) {
                return ($a["severity"] < $b["severity"]) ? 1 : -1;
            }
            
            return ($a["updated_at"] < $b["updated_at"]) ? 1 : -1;
        });
        return $array;
    }

    public static function order_line($array) {
        usort ($array, function($a, $b) {
            $type_list = [
                'nationalrail',
                'commercial_mode:Train',
                'rail',
                'commercial_mode:RapidTransit',
                'commercial_mode:RailShuttle',
                'funicular',
                'commercial_mode:LocalTrain',
                'commercial_mode:LongDistanceTrain',
                'commercial_mode:Metro',
                'metro',
                'commercial_mode:RailShuttle',
                'commercial_mode:Tramway',
                'tram',
                'commercial_mode:Bus',
                'bus',
            ];
        
            $ta = array_search($a['mode'], $type_list);
            $tb = array_search($b['mode'], $type_list);
        
            if ($a['code'] == 'SNCF') {
                return -1;
            } elseif ($b['code'] == 'SNCF') {
                return +1;
            }
        
            if ($a['name'] == 'TER') {
                return +1;
            } elseif ($b['name'] == 'TER') {
                return -1;
            }
        
            if ($ta != $tb) {
                return ($ta < $tb) ? -1 : 1;
            }
        
            $a = $a['code'];
            $b = $b['code'];
        
            if ($a == $b) {
                return 0;
            }
        
            return ($a < $b) ? -1 : 1;
        });
        return $array;
    }

    public static function order_routes($array, $query) {
        usort ($array, function($a, $b) use ($query) {
            $type_list = [
                'nationalrail',
                'commercial_mode:Train',
                'rail',
                'commercial_mode:RapidTransit',
                'commercial_mode:RailShuttle',
                'funicular',
                'commercial_mode:LocalTrain',
                'commercial_mode:LongDistanceTrain',
                'commercial_mode:Metro',
                'metro',
                'commercial_mode:Shuttle',
                'commercial_mode:Tramway',
                'tram',
                'commercial_mode:Bus',
                'bus',
            ];
        
            $ta = array_search($a['mode'], $type_list);
            $tb = array_search($b['mode'], $type_list);

            similar_text($query, $a['code'], $a_perc_code);
            similar_text($query, $a['name'], $a_perc_name);

            similar_text($query, $b['code'], $b_perc_code);
            similar_text($query, $b['name'], $b_perc_name);
    
        
            if ($a['code'] == 'SNCF') {
                return -1;
            } elseif ($b['code'] == 'SNCF') {
                return +1;
            }
        
            if ($ta != $tb) {
                return ($ta < $tb) ? -1 : 1;
            }
        
            $a = $a['code'];
            $b = $b['code'];
        
            if ($a_perc_code == $b_perc_code) {
                return 0;
            }
        
            return ($a_perc_code > $b_perc_code) ? -1 : 1;
        });
        return $array;
    }

    public static function orderPlaces($array) {
        usort ($array, function($a, $b){
            $a_modes = count($a["modes"]);
            $b_modes = count($b["modes"]);
        
            if (in_array('nationalrail', $a["modes"])) {
                $a_modes++;
            }
            if (in_array('nationalrail', $b["modes"])) {
                $b_modes++;
            }
        
            if ($a_modes === $b_modes) {
                $a_lines = count($a["lines"]);
                $b_lines = count($b["lines"]);
        
                if ($a_lines === $b_lines) {
                    return 0;
                }
                return ($a_lines < $b_lines) ? 1 : -1;
            }
            return ($a_modes < $b_modes) ? 1 : -1;
        });
        return $array;
    }

    public static function orderByDistance($array, $latitudeTo, $longitudeTo) {
        usort ($array, function($a, $b) use ($latitudeTo, $longitudeTo) {
            
            $a = Functions::getDistanceBeetwenPoints(
                (float) $a["coord"]['lat'], 
                (float) $a["coord"]['lon'], 
                (float) $latitudeTo, 
                (float) $longitudeTo
            );
            $b = Functions::getDistanceBeetwenPoints(
                (float) $b["coord"]['lat'], 
                (float) $b["coord"]['lon'], 
                (float) $latitudeTo, 
                (float) $longitudeTo
            );
        
            return ($a > $b) ? 1 : -1;
        });
        return $array;
    }

    public static function levenshteinDistance($str1, $str2) {
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        $matrix = array();

        for ($i = 0; $i <= $len1; $i++) {
            $matrix[$i] = array();
            for ($j = 0; $j <= $len2; $j++) {
                if ($i == 0) {
                    $matrix[$i][$j] = $j;
                } elseif ($j == 0) {
                    $matrix[$i][$j] = $i;
                } else {
                    $cost = ($str1[$i - 1] != $str2[$j - 1]) ? 1 : 0;
                    $matrix[$i][$j] = min(
                        $matrix[$i - 1][$j] + 1,
                        $matrix[$i][$j - 1] + 1,
                        $matrix[$i - 1][$j - 1] + $cost
                    );
                }
            }
        }

        return $matrix[$len1][$len2];
    }


    public static function orderWithLevenshtein($array, $text) {
        usort($array, function($a, $b) use ($text) {
            $levA = Functions::levenshteinDistance($text, $a['name']);
            $levB = Functions::levenshteinDistance($text, $b['name']);
    
            return $levA - $levB;
        });
    
        return $array;
    }

    public static function orderDeparture($array) {
        usort ($array, function($a, $b) {
            
            if ( $a['stop_date_time']['arrival_date_time'] != "" ) {
                $a = $a['stop_date_time']['arrival_date_time'];
            } else if ( $a['stop_date_time']['departure_date_time'] != "" ) {
                $a = $a['stop_date_time']['departure_date_time'];
            }

            
            if ( $b['stop_date_time']['arrival_date_time'] != "" ) {
                $b = $b['stop_date_time']['arrival_date_time'];
            } else if ( $b['stop_date_time']['departure_date_time'] != "" ) {
                $b = $b['stop_date_time']['departure_date_time'];
            }
        
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });
        return $array;
    }

    public static function calculateDistance( $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo) {
        $distance = Functions::getDistanceBeetwenPoints(
            (float) $latitudeFrom, 
            (float) $longitudeFrom, 
            (float) $latitudeTo, 
            (float) $longitudeTo
        );
        $distance = ceil( $distance );
        
        return $distance > 1000 ? 0 : $distance;
    }

    public static function getDistanceBeetwenPoints($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo) {
        // Honetement, j'ai rien compris a cette fonction mais ça fonctionne :D
        $earth = 6371000;
        
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);
      
        $lat = $latTo - $latFrom;
        $lon = $lonTo - $lonFrom;
      
        $angle = 2 * asin(sqrt(pow(sin($lat / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lon / 2), 2)));
        return $angle * $earth;
    }

    public static function getReportsMesageTitle($messages){
        foreach ($messages as $message) {
            if ($message->channel->name == 'titre') {
                return $message->text;
            }
        }
        return '';
    }

    public static function getReportsMesageText($messages){
        $search = ['<br>', '</p>', "  "];
        $replace = [PHP_EOL, PHP_EOL, '', ' '];
    
        foreach ($messages as $message) {
            if ($message->channel->name == 'moteur') {
                $msg = str_replace($search, $replace, $message->text);
                $msg = strip_tags($msg);
                $msg = html_entity_decode($msg);
                return trim($msg);
            }
        }
        foreach ($messages as $message) {
            if ($message->channel->name == 'email') {
                $msg = str_replace($search, $replace, $message->text);
                $msg = strip_tags($msg);
                $msg = html_entity_decode($msg);
                return trim($msg);
            }
        }
        foreach ($messages as $message) {
            if ($message->channel->name != 'titre') {
                $msg = str_replace($search, $replace, $message->text);
                $msg = strip_tags($msg);
                $msg = html_entity_decode($msg);
                return trim($msg);
            }
        }
    }

    public static function getSeverity($effect, $cause, $status){
        if ($status == 'past') {
            return 0;
        } elseif ($cause == 'information') {
            return 1;
        } elseif ($status == 'future' && $cause == 'travaux') {
            return 2;
        } elseif ($cause == 'travaux') {
            return 3;
        } elseif ($status == 'future') {
            return 1;
        } elseif (in_array($effect, array('REDUCED_SERVICE', 'SIGNIFICANT_DELAYS', 'DETOUR', 'ADDITIONAL_SERVICE', 'MODIFIED_SERVICE'))) {
            return 4;
        } elseif (in_array($effect, array('NO_SERVICE', 'STOP_MOVED'))) {
            return 5;
        } elseif (in_array($effect, array('UNKNOWN_EFFECT', 'OTHER_EFFECT', 'NO_EFFECT', 'ACCESSIBILITY_ISSUE'))) {
            return 1;
        } else {
            return 0;
        }
    }

    public static function idfmFormat($str){
        $search = [
            'stop_area',
            'stop_point',
            'stopArea',
            'StopPoint',
            'ADMIN:',
            'IDFM:',
            'STIF:',
            'SNCF:',
            'line',
            'Line',
            ':Q:',
            ':BP:',
            '::',
            ':'
        ];
        $replace = '';
        return str_replace($search, $replace, $str);
    }

    public static function gareFormat($id){
        // return $id;
        $allowed_name = [
            "Gare de l'Est",
            "Gare de Lyon"
        ];
    
        if (in_array($id, $allowed_name))
            return $id;

        $search = [
            "Gare de",
            "Gare d'"
        ];

        $id = preg_replace('/- quai \d+/', '', $id);
        $id = preg_replace('/\([^)]+\)/', '', $id);
        $id = str_replace('Gare des', 'Les', $id);
        $id = str_replace($search, '', $id);
        $id = trim( $id );
        return ucfirst($id);
    }

    public static function getCSVHeader($csv, $sep = ';'){
        $line = [];
        $file = fopen($csv, 'r');
        while (!feof($file)) {
            $line[] = fgetcsv($file, 0, $sep);
            break;
        }
        fclose($file);
        return $line;
    }

    public static function readCsv($csv, $sep = ';'){
        $line = [];
        $file = fopen($csv, 'r');
        while (!feof($file)) {
            $line[] = fgetcsv($file, 0, $sep);
        }
        fclose($file);
        return $line;
    }

    public static function isValidDateYMD($date) {
        $format = 'Y-m-d';
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    public static function isToday($date) {
        $today = new DateTime("today");
        
        $format = 'Y-m-d';
        $dateTime = DateTime::createFromFormat($format, $date);
        $dateTime->setTime( 0, 0, 0 );

        $interval = $today->diff($dateTime);
        $diffDays = $interval->days;

        return $diffDays == 0;
    }

    public static function addRealTime($el, $real_time) {
        foreach($real_time as $real) {
            if ($real['id'] != null && $el['trip_id'] != null && $real['id'] == $el['trip_id'] ) {
                return $real['date_time'];
            } 
        }
        foreach($real_time as $real) {
            if ($real['stop_name'] == $el['stop_name'] && Functions::isSameTime($real['date_time']['base_departure_date_time'], $el['departure_date_time']) ) {
                return $real['date_time'];
            } 
        }
        foreach($real_time as $real) {
            if ($real['trip_name'] == $el['trip_name'] && strlen($el['trip_name']) >= 6 ) {
                return $real['date_time'];
            }
        }
        return null;
    }

    public static function isSameTime($date1, $date2) {
        $date1 = new DateTime($date1);
        $date2 = new DateTime($date2);
      
        return $date1 == $date2;
      }

    public static function prepareTime($dt, $i = false){
        if ($dt == '') return '';

        $datetime = $i == true ? date_create($dt, timezone_open('Europe/Paris')) : date_create($dt, timezone_open('UTC'));
        
        if (is_bool($datetime)) {
            $timeArray = explode(':', $dt);
    
            $hours = (int) $timeArray[0];
            $minutes = (int) $timeArray[1];
            $seconds = (int) $timeArray[2];
    
            // 'cause GTFS time can be 25:00:00
            $hours %= 24;
            $minutes %= 60;
            $seconds %= 60;
    
            $datetime = new DateTime();
            $datetime->setTime($hours, $minutes, $seconds);
            $datetime->setTimezone(new DateTimeZone('Europe/Paris'));
    
            if ($timeArray[0] >= 24) {
                $datetime->modify('+1 day');
            }
        }
        return date_format($datetime, DATE_ATOM);
    }

    public static function rPrepareTime($dt, $i = false) {
        $time = substr($dt, 0, 2) . ':' . substr($dt, 2, 2) . ':' .substr($dt, 4, 2);
        return Functions::prepareTime($time, $i);
    }

    public static function getState($call){
        // theorical - ontime - delayed - cancelled - modified
        if (isset($call->DepartureStatus) && ($call->DepartureStatus == "cancelled" || $call->DepartureStatus == "delayed"))
            return $call->DepartureStatus;
    
        if (isset($call->ArrivalStatus) && ($call->ArrivalStatus == "cancelled" || $call->ArrivalStatus == "delayed"))
            return $call->ArrivalStatus;
    
        if ((isset($call->DepartureStatus) && $call->DepartureStatus == "onTime") || (isset($call->ArrivalStatus) && $call->ArrivalStatus == "onTime"))
            return "ontime";
    
        return "theorical";
    }

    public static function getMessage($call){
        // terminus - origin
        if (!isset($call->ExpectedDepartureTime) && !isset($call->AimedDepartureTime)) {
            return "terminus";
        } elseif (!isset($call->ExpectedArrivalTime) && !isset($call->AimedArrivalTime)) {
            return "origin";
        } else
            return "";
    }

    public static function getPhysicalModes($physical_modes){
        $list = [];
        foreach ($physical_modes as $modes) {
            $list[] = $modes->id;
        }
        return $list;
    }
    public static function getAllLines($lines){
        $list = [];
    
        foreach ($lines as $line) {
            $list[] = array(
                "id"         =>  (string)    'IDFM:' . Functions::idfmFormat($line->id),
                "code"       =>  (string)    $line->code,
                "name"       =>  (string)    $line->name,
                "mode"       =>  (string)    $line->commercial_mode->id,
                "color"      =>  (string)    $line->color,
                "text_color" =>  (string)    $line->text_color,
            );
        }
        return Functions::order_line($list);
    }

    public static function getSNCFid($links){
        foreach ($links as $link) {
            if ($link->type == 'vehicle_journey') {
                return $link->id;
            }
        }
    }

    public static function getApiDetails($api_results, $api_results2, $name){
        $departures = [];

        // foreach ($api_results2->departures as $api_result) {
        //     if ($api_result->display_informations->headsign == $name) {    
        //         $departures = array(
        //             "id"                =>  (string)    Functions::getSNCFid($api_result->links),
        //             "name"              =>  (string)    $api_result->display_informations->headsign,
        //             "network"           =>  (string)    $api_result->display_informations->network,
        //             "to_id"             =>  (string)    'SNCF:' . Functions::idfmFormat( $api_result->route->direction->id ),
        //         );
        //     }
        // }
        foreach ($api_results->departures as $api_result) {
            if ($api_result->display_informations->headsign == $name) {
                $departures = array(
                    "id"                =>  (string)    Functions::getSNCFid($api_result->links),
                    "name"              =>  (string)    $api_result->display_informations->headsign,
                    "network"           =>  (string)    $api_result->display_informations->network,
                    "to_id"             =>  (string)    'SNCF:' . Functions::idfmFormat( $api_result->route->direction->id ),
                );
            }
        }
        return $departures;
    }

    public static function getReports($disruptions){    
        $echo = [];    
        foreach ($disruptions as $disruption) {    
            $causes = [];
    
            foreach ($disruption->impacted_objects[0]->impacted_stops as $stop) {    
                $cause = $stop->cause;
                if ( $cause != '' && !in_array( $cause, $causes ) ) {
                    $causes[] = $cause;
                }
            }
            foreach($causes as $cause) {
                $echo[] = array(
                    "id"            => (string) $disruption->id,
                    "status"        => (string) $disruption->status,
                    "cause"         => (string) $disruption->cause,
                    "severity"      => Functions::getSeverityByEffect($disruption->severity->effect),
                    "effect"        => (string) $disruption->severity->effect,
                    "updated_at"    => (string) $disruption->updated_at,
                    "message"       => array(
                        "title"         => Functions::getTitleByEffect($disruption->severity->effect),
                        "text"          => $cause,
                    ),
                );
            }
            if ($causes == []) {
                $echo[] = array(
                    "id"            => (string) $disruption->id,
                    "status"        => (string) $disruption->status,
                    "cause"         => (string) $disruption->cause,
                    "severity"      => Functions::getSeverityByEffect($disruption->severity->effect),
                    "effect"        => (string) $disruption->severity->effect,
                    "updated_at"    => (string) $disruption->updated_at,
                    "message"       => array(
                        "title"         => Functions::getTitleByEffect($disruption->severity->effect),
                        "text"          => '',
                    ),
                );
            }
        }
        return $echo;
    }

    public static function getTitleByEffect($effect){
        switch ($effect) {
            case 'SIGNIFICANT_DELAYS':
                return 'Retardé';
            case 'REDUCED_SERVICE':
                return 'Trajet modifié';
            case 'NO_SERVICE':
                return 'Supprimé';
            case 'MODIFIED_SERVICE':
                return 'Trajet modifié';
            case 'ADDITIONAL_SERVICE':
                return 'Train supplémentaire';
            case 'DETOUR':
                return 'Trajet modifié';
            default: // UNKNOWN_EFFECT et OTHER_EFFECT
                return "Trajet Perturbé";
        }
    }

    public static function getSeverityByEffect($effect){
        switch ($effect) {
            case 'SIGNIFICANT_DELAYS':
                return 4;
            case 'REDUCED_SERVICE':
                return 4;
            case 'NO_SERVICE':
                return 5;
            case 'MODIFIED_SERVICE':
                return 1;
            case 'ADDITIONAL_SERVICE':
                return 1;
            case 'DETOUR':
                return 4;
            default: // UNKNOWN_EFFECT et OTHER_EFFECT
                return 4;
        }
    }

    public static function getSNCFState($status, $level, $traffic){
        if ($level == "Normal") {
            return "ontime";
        }
    
        switch($status) {
            case "RETARD": 
            case "RETARD_OBSERVE": 
                return 'delayed';
    
            case "MODIFICATION": 
            case "MODIFICATION_LIMITATION": 
            case "MODIFICATION_DESSERTE_SUPPRIMEE": 
            case "MODIFICATION_DETOURNEMENT": 
            case "MODIFICATION_PROLONGATION":
                return "modified";
        
            case "SUPPRESSION" :
            case "SUPPRESSION_TOTALE": 
            case "SUPPRESSION_PARTIELLE": 
            case "SUPPRESSION_DETOURNEMENT":
                return "cancelled";
                
            case "MODIFICATION_DESSERTE_AJOUTEE":
                return "added";
    
            case "OnTime":
                return 'ontime';
        }
    
        return "theorical";
    }

    public static function getIDFMID($id) {
        $pattern = '/(?<=::)\d+(?=:)/';
    
        if (preg_match($pattern, $id, $matches)) {
            return $matches[0];
        } else {
            return null;
        }
    }

    public static function getStopDateTime($call){
        return array(
            // Si l'horaire est present               On affiche l'horaire est                    Sinon, si l'autre est present                           On affiche l'autre                    Ou rien  
            "base_departure_date_time"  =>  (string)  isset($call->AimedDepartureTime) !== '' && (string)  isset($call->AimedDepartureTime) !== '0'          ? Functions::prepareTime($call->AimedDepartureTime)    : (isset($call->ExpectedDepartureTime) ? Functions::prepareTime($call->ExpectedDepartureTime) : ""),
            "departure_date_time"       =>  (string)  isset($call->ExpectedDepartureTime) !== '' && (string)  isset($call->ExpectedDepartureTime) !== '0'       ? Functions::prepareTime($call->ExpectedDepartureTime) : (isset($call->AimedDepartureTime)    ? Functions::prepareTime($call->AimedDepartureTime)    : ""),
            "base_arrival_date_time"    =>  (string)  isset($call->AimedArrivalTime) !== '' && (string)  isset($call->AimedArrivalTime) !== '0'            ? Functions::prepareTime($call->AimedArrivalTime)      : (isset($call->ExpectedArrivalTime)   ? Functions::prepareTime($call->ExpectedArrivalTime)   : ""),
            "arrival_date_time"         =>  (string)  isset($call->ExpectedArrivalTime) !== '' && (string)  isset($call->ExpectedArrivalTime) !== '0'         ? Functions::prepareTime($call->ExpectedArrivalTime)   : (isset($call->AimedArrivalTime)      ? Functions::prepareTime($call->AimedArrivalTime)      : ""),
            "state"                     =>  (string)  Functions::getState($call),
            "atStop"                    =>  (string)  isset($call->VehicleAtStop) !== '' && (string)  isset($call->VehicleAtStop) !== '0'               ? ($call->VehicleAtStop ? 'true' : 'false') : 'false',
            "platform"                  =>  (string)  isset($call->ArrivalPlatformName->value) !== '' && (string)  isset($call->ArrivalPlatformName->value) !== '0'  ? $call->ArrivalPlatformName->value : '-'
        );
    }

    public static function getDisruptionForStop($disruptions){    
        $stops = [];
        $order = 0;    
        foreach ($disruptions[0]->impacted_objects[0]->impacted_stops as $stop) {            
            $stops[] = array(
                "name"              => (string) $stop->stop_point->name,
                "id"                => (string) $stop->stop_point->id,
                "order"             => (int)    $order,
                "type"              => (int)    count($disruptions[0]->impacted_objects[0]->impacted_stops) - 1 == $order ? 'terminus' : ($order == 0 ? 'origin' : ''),
                "coords" => array(
                    "lat"           => $stop->stop_point->coord->lat,
                    "lon"           => $stop->stop_point->coord->lon,
                ),
                "stop_time" => array(
                    "departure_time" =>  isset($stop->base_departure_time)    ? Functions::rPrepareTime($stop->base_departure_time, true) : '',
                    "arrival_time"   =>  isset($stop->base_arrival_time)      ? Functions::rPrepareTime($stop->base_arrival_time, true)   : '',
                ),
                "disruption" => array(
                    "departure_state"       => (string) $stop->departure_status,
                    "arrival_state"         => (string) $stop->arrival_status,
                    "message"               => (string) "",
                    "base_departure_time"   => (string) isset($stop->base_departure_time)    !== '' && isset($stop->base_departure_time)    !== '0' ? Functions::rPrepareTime($stop->base_departure_time, true)    : '',
                    "departure_time"        => (string) isset($stop->amended_departure_time) !== '' && isset($stop->amended_departure_time) !== '0' ? Functions::rPrepareTime($stop->amended_departure_time, true) : '',
                    "base_arrival_time"     => (string) isset($stop->base_arrival_time)      !== '' && isset($stop->base_arrival_time)      !== '0' ? Functions::rPrepareTime($stop->base_arrival_time, true)      : '',
                    "arrival_time"          => (string) isset($stop->amended_arrival_time)   !== '' && isset($stop->amended_arrival_time)   !== '0' ? Functions::rPrepareTime($stop->amended_arrival_time, true)   : '',
                    "is-detour"             => $stop->is_detour,
                ),
            );
            $order++;
        }
        return $stops;
    }

    public static function callIsFuture($call){
        if ( isset($call->ExpectedDepartureTime) ) {
            return date_create($call->ExpectedDepartureTime) >= date_create();
        }
        if ( isset($call->ExpectedArrivalTime) ) {
            return date_create($call->ExpectedArrivalTime) >= date_create();
        }
        if ( isset($call->AimedDepartureTime) ) {
            return date_create($call->AimedDepartureTime) >= date_create();
        }
        if ( isset($call->AimedArrivalTime) ) {
            return date_create($call->AimedArrivalTime) >= date_create();
        }
    }

    public static function getParentId($em, $id) {
        $req = $em->prepare("
            SELECT parent_station
            FROM stops
            WHERE stop_id = :stop_id;
        ");
        $req->bindValue( "stop_id", $id );
        $results = $req->executeQuery();

        $res = $results->fetchAll();

        if ( array_key_exists(0, $res) ) {
            return $res[0]['parent_station'];
        }
        return $id;
    }

    public static function getTerminusForLine($em, \App\Entity\Routes $route){    
        $req = $em->prepare("
            SELECT DISTINCT S2.stop_name, S2.stop_id
            FROM stops S2
            JOIN stops S
                ON S.parent_station = S2.stop_id
            JOIN stop_times ST 
                ON S.stop_id = ST.stop_id
            JOIN trips T 
                ON ST.trip_id = T.trip_id
            WHERE T.route_id = :route_id
            AND (ST.stop_sequence = 0 OR ST.stop_sequence = (SELECT MAX(stop_sequence) FROM stop_times WHERE trip_id = T.trip_id));
        ");
        $req->bindValue( "route_id", $route->getRouteId() );
        $results = $req->executeQuery();
        return $results->fetchAll();
    }

    public static function getSchedulesByStop($em, $stop_id, $route_id, $date, $departure_time){    
        $req = $em->prepare("
            SELECT DISTINCT ST.trip_id, ST.departure_time, ST.arrival_time, T.*
            FROM stops S
            
            INNER JOIN stop_times ST 
            ON S.stop_id = ST.stop_id
            
            INNER JOIN trips T 
            ON ST.trip_id = T.trip_id
            
            LEFT JOIN calendar C 
            ON T.service_id = C.service_id
            
            LEFT JOIN calendar_dates CD 
            ON (T.service_id = CD.service_id AND CD.date = :date)
            
            WHERE S.parent_station = :stop_id
                AND T.route_id = :route_id
                AND ST.departure_time >= :departure_time
                AND ST.pickup_type != '1'
                AND (
                    (C.start_date <= :date
                        AND C.end_date >= :date
                        AND (
                            DATE_FORMAT(:date, '%w') = '1' AND C.monday = '1'
                            OR DATE_FORMAT(:date, '%w') = '2' AND C.tuesday = '1'
                            OR DATE_FORMAT(:date, '%w') = '3' AND C.wednesday = '1'
                            OR DATE_FORMAT(:date, '%w') = '4' AND C.thursday = '1'
                            OR DATE_FORMAT(:date, '%w') = '5' AND C.friday = '1'
                            OR DATE_FORMAT(:date, '%w') = '6' AND C.saturday = '1'
                            OR DATE_FORMAT(:date, '%w') = '0' AND C.sunday = '1'
                        ) 
                        AND (CD.exception_type <> '2' OR CD.exception_type IS NULL)
                    )
                    OR CD.exception_type = '1' 
                )
            ORDER BY ST.departure_time
        ");
        $req->bindValue("date", $date);
        $req->bindValue("route_id", $route_id);
        $req->bindValue("stop_id", $stop_id);
        $req->bindValue("departure_time", $departure_time);
        $results = $req->executeQuery();
        return $results->fetchAll();
    }

    public static function getSchedules($em, $stop_id, $date, $departure_time){    
        $req = $em->prepare("
            SELECT DISTINCT ST.trip_id, ST.departure_time, ST.arrival_time, T.*
            FROM stops S
            
            INNER JOIN stop_times ST 
            ON S.stop_id = ST.stop_id
            
            INNER JOIN trips T 
            ON ST.trip_id = T.trip_id
            
            LEFT JOIN calendar C 
            ON T.service_id = C.service_id
            
            LEFT JOIN calendar_dates CD 
            ON (T.service_id = CD.service_id AND CD.date = :date)
            
            WHERE S.parent_station = :stop_id
                AND ST.departure_time >= :departure_time
                AND ST.pickup_type != '1'
                AND (
                    (C.start_date <= :date
                        AND C.end_date >= :date
                        AND (
                            DATE_FORMAT(:date, '%w') = '1' AND C.monday = '1'
                            OR DATE_FORMAT(:date, '%w') = '2' AND C.tuesday = '1'
                            OR DATE_FORMAT(:date, '%w') = '3' AND C.wednesday = '1'
                            OR DATE_FORMAT(:date, '%w') = '4' AND C.thursday = '1'
                            OR DATE_FORMAT(:date, '%w') = '5' AND C.friday = '1'
                            OR DATE_FORMAT(:date, '%w') = '6' AND C.saturday = '1'
                            OR DATE_FORMAT(:date, '%w') = '0' AND C.sunday = '1'
                        ) 
                        AND (CD.exception_type <> '2' OR CD.exception_type IS NULL)
                    )
                    OR CD.exception_type = '1' 
                )
            ORDER BY ST.departure_time
        ");
        $req->bindValue("date", $date);
        $req->bindValue("stop_id", $stop_id);
        $req->bindValue("departure_time", $departure_time);
        $results = $req->executeQuery();
        return $results->fetchAll();
    }

    public static function getLastStopOfTrip($em, $trip_id){    
        $req = $em->prepare("
            SELECT S2.*
            FROM trips T

            JOIN stop_times ST 
            ON T.trip_id = ST.trip_id

            JOIN stops S
            ON ST.stop_id = S.stop_id

            JOIN stops S2
            ON S.parent_station = S2.stop_id

            WHERE T.trip_id = :trip_id

            ORDER BY ST.stop_sequence DESC
            LIMIT 1;
      
        ");
        $req->bindValue("trip_id", $trip_id);
        $results = $req->executeQuery();
        return $results->fetchAll();
    }

    public static function getTripStopsByNameOrId($em, $trip_id, $date)
    {
        $req = $em->prepare("
            SELECT *
            FROM trips T
            
            JOIN stop_times ST 
            ON T.trip_id = ST.trip_id

            JOIN routes R 
            ON T.route_id = R.route_id
            
            JOIN stops S
            ON ST.stop_id = S.stop_id
            
            LEFT JOIN calendar C 
            ON T.service_id = C.service_id
            
            LEFT JOIN calendar_dates CD 
            ON (T.service_id = CD.service_id AND CD.date = :date)
            
            WHERE (T.trip_short_name = :trip_id
                OR T.trip_id = :trip_id)
                AND (
                    (C.start_date <= :date
                        AND C.end_date >= :date
                        AND (
                            DATE_FORMAT(:date, '%w') = '1' AND C.monday = '1'
                            OR DATE_FORMAT(:date, '%w') = '2' AND C.tuesday = '1'
                            OR DATE_FORMAT(:date, '%w') = '3' AND C.wednesday = '1'
                            OR DATE_FORMAT(:date, '%w') = '4' AND C.thursday = '1'
                            OR DATE_FORMAT(:date, '%w') = '5' AND C.friday = '1'
                            OR DATE_FORMAT(:date, '%w') = '6' AND C.saturday = '1'
                            OR DATE_FORMAT(:date, '%w') = '0' AND C.sunday = '1'
                        ) 
                        AND (CD.exception_type <> '2' OR CD.exception_type IS NULL)
                    )
                    OR CD.exception_type = '1' 
                )
                ORDER BY ST.departure_time
        ");
        $req->bindValue("date", $date);
        $req->bindValue("trip_id", $trip_id);
        $results = $req->executeQuery();
        return $results->fetchAll();
    }

    public static function getStopsOfRoutes($em, $route_id)
    {
        $req = $em->prepare("
            SELECT DISTINCT S2.*
            FROM stops AS S
            
            JOIN stop_times ST 
            ON S.stop_id = ST.stop_id
            
            JOIN trips T 
            ON ST.trip_id = T.trip_id
            
            JOIN routes R 
            ON T.route_id = R.route_id
            
            JOIN stops S2
            ON S.parent_station = S2.stop_id
            
            WHERE R.route_id = :route_id
            ORDER BY T.trip_id, ST.stop_sequence;
        ");
        $req->bindValue("route_id", $route_id);
        $results = $req->executeQuery();
        return $results->fetchAll();
    }

    public static function getForbiddenModesURI($forbidden_modes) {
        if ($forbidden_modes == null) {
            return [];
        }
        $uri = [];
        $all = [
            'rail' => [
                'physical_mode:Train',
                'physical_mode:LocalTrain',
                'physical_mode:LongDistanceTrain',
                'physical_mode:RailShuttle',
                'physical_mode:RapidTransit',
            ],
            'metro' => [
                'physical_mode:Metro',
                'physical_mode:Shuttle',
            ],
            'tram' => [
                'physical_mode:Tramway',
            ],
            'bus' => [
                'physical_mode:Bus',
                'physical_mode:BusRapidTransit',
                'physical_mode:Coach',
            ],
            'cable' => [
                'physical_mode:SuspendedCableCar',
            ],
            'funicular' => [
                'physical_mode:Funicular',
            ],
            'boat' => [
                'physical_mode:Boat',
                'physical_mode:Ferry',
            ]
        ];

        foreach($forbidden_modes as $forbidden_mode) {
            foreach( $all[$forbidden_mode] as $el) {
                $uri[] = $el;
            }
        }

        return $uri;
        
    // physical_mode:Air
    // physical_mode:Boat
    // physical_mode:Ferry

    }

    public static function getForbiddenLines($forbidden_lines) {
        if ($forbidden_lines == null) {
            return [];
        }
        foreach ($forbidden_lines as $key => $value) {
            $forbidden_lines[$key] = 'line:' . $value;
        }
        return $forbidden_lines;
    }

    public static function buildUrl($baseUrl, $params) {
        $query = [];
    
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $element) {
                    $encodedElement = rawurlencode($element);
    
                    $query[] = $key . '[]=' . $encodedElement;
                }
            } else {
                $encodedValue = rawurlencode($value);
    
                $query[] = "$key=$encodedValue";
            }
        }
    
        $uri = implode('&', $query);
        $url = "$baseUrl?$uri";
    
        return $url;
    }

    public static function getCentroidOfStops($points) {
        $num = count($points);
        $lat = 0;
        $lon = 0;
    
        foreach ($points as $point) {
            $lat += $point['coord']['lat'];
            $lon += $point['coord']['lon'];
        }
    
        return [
            'lat' => $lat / $num,
            'lon' => $lon / $num
        ];
    }
}