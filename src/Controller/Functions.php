<?php

namespace App\Controller;
use Symfony\Component\HttpClient\HttpClient;
use Google\Transit\Realtime\FeedMessage;
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

    public static function filterStopsWithLevenshtein($array, $text, $limit = 3) {
        $res = [];
        foreach($array as $element) {
            $d = levenshtein($element->getStopId()->getStopName(), $text);
            if ($d <= 3) {
                $res[] = $element;
            }
        }
    
        return $res;
    }

    public static function orderDeparture($array) {
        usort ($array, function($a, $b) {
            
            if ( $a['stop_date_time']['departure_date_time'] != "" ) {
                $a = new DateTime($a['stop_date_time']['departure_date_time']);
            } else if ( $a['stop_date_time']['arrival_date_time'] != "" ) {
                $a = new DateTime($a['stop_date_time']['arrival_date_time']);
            }
            
            if ( $b['stop_date_time']['departure_date_time'] != "" ) {
                $b = new DateTime($b['stop_date_time']['departure_date_time']);
            } else if ( $b['stop_date_time']['arrival_date_time'] != "" ) {
                $b = new DateTime($b['stop_date_time']['arrival_date_time']);
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
        $date_time = DateTime::createFromFormat($format, $date);
        return $date_time && $date_time->format($format) === $date;
    }

    public static function isToday($date) {
        $today = new DateTime("today");
        
        $format = 'Y-m-d';
        $date_time = DateTime::createFromFormat($format, $date);
        $date_time->setTime( 0, 0, 0 );

        $interval = $today->diff($date_time);
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
            if ($real['id'] == $el['trip_name'] && strlen($el['trip_name']) >= 6 ) {
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

        $date_time = $i == true ? date_create($dt, timezone_open('Europe/Paris')) : date_create($dt, timezone_open('UTC'));
        
        if (is_bool($date_time)) {
            $timeArray = explode(':', $dt);
    
            $hours = (int) $timeArray[0];
            $minutes = (int) $timeArray[1];
            $seconds = (int) $timeArray[2];
    
            // 'cause GTFS time can be 25:00:00
            $hours %= 24;
            $minutes %= 60;
            $seconds %= 60;
    
            $date_time = new DateTime();
            $date_time->setTime($hours, $minutes, $seconds);
            $date_time->setTimezone(new DateTimeZone('Europe/Paris'));
    
            if ($timeArray[0] >= 24) {
                $date_time->modify('+1 day');
            }
        }
        return date_format($date_time, DATE_ATOM);
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

    public static function getIDFMID($id) {
        $pattern1 = '/(?<=::)\d+(?=:)/';
        $pattern2 = '/RATP\.(\w+):LOC/';
    
        if (preg_match($pattern1, $id, $matches)) {
            return $matches[0];
        } else if (preg_match($pattern2, $id, $matches)) {
            return $matches[1];
        } else {
            return $id;
        }
    }

    public static function getRATPName($id) {
        $pattern = 'RATP\.(\w+):LOC';
    
        if (preg_match($pattern, $id, $matches)) {
            return $matches[0];
        } else {
            return $id;
        }
    }

    public static function getStopDateTime($call){
        return array(
            "base_departure_date_time"  =>  (string)  isset($call->AimedDepartureTime)      ? $call->AimedDepartureTime     :  ( isset($call->ExpectedDepartureTime) ? $call->ExpectedDepartureTime     : '' ),
            "departure_date_time"       =>  (string)  isset($call->ExpectedDepartureTime)   ? $call->ExpectedDepartureTime  :  ( isset($call->AimedDepartureTime)    ? $call->AimedDepartureTime        : '' ),
            "base_arrival_date_time"    =>  (string)  isset($call->AimedArrivalTime)        ? $call->AimedArrivalTime       :  ( isset($call->ExpectedArrivalTime)   ? $call->ExpectedArrivalTime       : '' ),
            "arrival_date_time"         =>  (string)  isset($call->ExpectedArrivalTime)     ? $call->ExpectedArrivalTime    :  ( isset($call->AimedArrivalTime)      ? $call->AimedArrivalTime          : '' ),
            "state"                     =>  (string)  Functions::getState($call),
            "atStop"                    =>  (string)  isset($call->VehicleAtStop) !== '' && (string)  isset($call->VehicleAtStop) !== '0'               ? ($call->VehicleAtStop ? 'true' : 'false') : 'false',
            "platform"                  =>  (string)  isset($call->ArrivalPlatformName->value) !== '' && (string)  isset($call->ArrivalPlatformName->value) !== '0'  ? $call->ArrivalPlatformName->value : '-'
        );
    }

    public static function getDisruptionForStop($trip_update, $obj){            
        $real_time = Functions::getTripRealtimeDateTime($trip_update, $obj['stop_id']);
        
        return array(
            "departure_state"       => (string) $real_time['departure_state'] != null ? $real_time['departure_state'] : 'unchanged',
            "arrival_state"         => (string) $real_time['arrival_state'] != null ? $real_time['arrival_state'] : 'unchanged',
            "message"               => (string) $real_time['message'] != null ? $real_time['message'] : '',
            "base_departure_date_time"  =>  (string)  Functions::prepareTime($obj['departure_time'], true),
            "departure_date_time"       =>  (string)  $real_time['departure_date_time'] != null ? Functions::prepareTime($real_time['departure_date_time'], true) : Functions::prepareTime($obj['departure_time'], true),
            "base_arrival_date_time"    =>  (string)  Functions::prepareTime($obj['arrival_time'], true),
            "arrival_date_time"         =>  (string)  $real_time['arrival_date_time'] != null ? Functions::prepareTime($real_time['arrival_date_time'], true) : Functions::prepareTime($obj['arrival_time'], true),
        );
    }

    public static function isFuture($real_time_departure, $departure, $real_time_arrival, $arrival){
        if ( isset($real_time_departure) ) {
            return date_create($real_time_departure) >= date_create();
        }
        if ( isset($departure) ) {
            return date_create($departure) >= date_create();
        }
        if ( isset($real_time_arrival) ) {
            return date_create($real_time_arrival) >= date_create();
        }
        if ( isset($arrival) ) {
            return date_create($arrival) >= date_create();
        }
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

    public static function getRealtimeData($provider) {
        $url = $provider->getGtfsRtTripUpdates();

        if ($url != null) {
            $client = HttpClient::create();
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();
            
            if ($status == 200){
                $feed = new FeedMessage();
                $feed->mergeFromString($response->getContent());

                $content = $feed->serializeToJsonString();

                ## We add the provider id before
                $search = [
                    '"tripId":"',
                    '"stopId":"',
                ];
                $replace = [
                    '"tripId":"' . $provider->getId() . ':',
                    '"stopId":"' . $provider->getId() . ':',
                ];
                $content = str_replace($search, $replace, $content);

                # Remove timestamp if added
                $regex = "/:\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/";
                $content = preg_replace($regex, '', $content);
        
                return json_decode($content)->entity;
            }
        }
        return [];
    }

    public static function getTripRealtime($trip_update, $trip_id, $stop_id = null) {
        $regex = "/:\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/";
        $trip_id = preg_replace($regex, '', $trip_id);

        $is_cancelled = false;
        $is_modified = false;
        $has_exceptional_terminus = false;
        $is_added = false;

        foreach($trip_update as $trip) {
            if (substr($trip->tripUpdate->trip->tripId, 0, -8) == substr($trip_id, 0, -8)) {

                // Check if fully canceled
                if ( isset($trip->tripUpdate->trip->scheduleRelationship) ) {
                    $schedule_relationship = $trip->tripUpdate->trip->scheduleRelationship;

                    // Check if fully canceled
                    if ( $schedule_relationship == "CANCELED") {
                        $is_cancelled = true;
                    }
                    // Check if fully canceled
                    if ( $schedule_relationship == "ADDED") {
                        $is_added = true;
                    }
                }
                $l = count($trip->tripUpdate->stopTimeUpdate);
                for ($i = 0; $i < $l; $i++) {
                    if ( isset($trip->tripUpdate->stopTimeUpdate[$i]->scheduleRelationship) ) {

                        // if modified
                        if ($trip->tripUpdate->stopTimeUpdate[$i]->scheduleRelationship == "SKIPPED")
                            $is_modified = true;

                        if ($stop_id != null){
                            // Check if cancelled at stop
                            if ($trip->tripUpdate->stopTimeUpdate[$i]->stopId == $stop_id && $trip->tripUpdate->stopTimeUpdate[$i]->scheduleRelationship == "SKIPPED")
                                $is_cancelled = true;
                        }
                        
                        // Check if cancelled at last stop
                        if ( ($i == ($l-1)) && $trip->tripUpdate->stopTimeUpdate[$i]->scheduleRelationship == "SKIPPED")
                            $has_exceptional_terminus = true;
                    }
                }

                $state = "ontime";
                if ($is_cancelled) {
                    $state = "cancelled";
                } else if ($has_exceptional_terminus) {
                    $state = "exceptional_terminus";
                } else if ($is_modified) {
                    $state = "modified";
                } else if ($is_added) {
                    $state = "added";
                } else if ($is_modified) {
                    $state = "ontime";
                }
                
                return array(
                    "trip_update"  => $trip->tripUpdate,
                    "state"  => $state
                );
            }
        }
        return null;
    }

    public static function getTripRealtimeReports($trip_update, $trip_id) {
        $message = array(
            "canceled" => array(
                "id" => 'ADMIN:canceled',
                "status" => 'active',
                "cause" => 'canceled',
                "severity" => 5,
                "effect" => 'OTHER',
                "message" => array(
                    "title" => "Supprimé",
                    "name" => "",
                ),
            ),
            "modified" => array(
                "id" => 'ADMIN:modified',
                "status" => 'active',
                "cause" => 'modified',
                "severity" => 4,
                "effect" => 'OTHER',
                "message" => array(
                "title" => "Desserte modifié",
                "name" => "",
                ),
            ),
            "delayed" => array(
                "id" => 'ADMIN:delayed',
                "status" => 'active',
                "cause" => 'delayed',
                "severity" => 4,
                "effect" => 'OTHER',
                "message" => array(
                "title" => "Retardé",
                "name" => "",
                ),
            ),
            "added" => array(
                "id" => 'ADMIN:added',
                "status" => 'active',
                "cause" => 'added',
                "severity" => 1,
                "effect" => 'OTHER',
                "message" => array(
                    "title" => "Trajet supplémentaire",
                ),
            ),
        );
        
        $is_delayed = false;
        $is_modified = false;

        $reports = [];

        if($trip_update != null && $trip_update['trip_update'] != null) {
            // Check if fully canceled
            if ( isset($trip_update['trip_update']->trip->scheduleRelationship) ) {
                $schedule_relationship = $trip_update['trip_update']->trip->scheduleRelationship;

                // Check if fully canceled
                if ( $schedule_relationship == "CANCELED") {
                    $reports[] = $message['canceled'];
                }
                // Check if fully canceled
                if ( $schedule_relationship == "ADDED") {
                    $reports[] = $message['added'];
                }
            }
            
            foreach($trip_update['trip_update']->stopTimeUpdate as $stop_time) {
                if (isset($stop_time->scheduleRelationship) && $stop_time->scheduleRelationship == "SKIPPED"){
                    $is_modified = true;
                }
                if (isset($stop_time->arrival) && isset($stop_time->arrival->delay)){
                    $is_delayed = true;
                }
                if (isset($stop_time->departure) && isset($stop_time->departure->delay)){
                    $is_delayed = true;
                }
            }
            
            if ($is_modified) {
                $reports[] = $message['modified'];
            }
            if ($is_delayed) {
                $reports[] = $message['delayed'];
            }
        }
            
        return $reports;
    }

    public static function getTripRealtimeDateTime($trip_update, $stop_id) {
        $res = array(
            "departure_date_time" => null,
            "departure_state" => null,
            "arrival_date_time"   => null,
            "arrival_state" => null,
            "message" => null,
        );

        if ($trip_update != null && $trip_update['trip_update'] != null){
            $len = count($trip_update['trip_update']->stopTimeUpdate);
            for ($i = 0; $i < $len; $i++) {
                $stop_time = $trip_update['trip_update']->stopTimeUpdate[$i];
                if ($stop_time->stopId == $stop_id) {
                    if (isset($stop_time->departure)) {
                        $date_time = new \DateTime();
                        $date_time->setTimestamp($stop_time->departure->time);
                        $res["departure_date_time"] = $date_time->format(DATE_ATOM);
                    }

                    if (isset($stop_time->arrival)) {
                        $date_time = new \DateTime();
                        $date_time->setTimestamp($stop_time->arrival->time);
                        $res["arrival_date_time"] = $date_time->format(DATE_ATOM);
                    }

                    if (isset($stop_time->departure) && isset($stop_time->departure->delay)) {
                        $res["departure_state"] = "delayed";
                    }
                    if (isset($stop_time->arrival) && isset($stop_time->arrival->delay)) {
                        $res["arrival_state"] = "delayed";
                    }

                    if ($i != $len-1 && !isset($stop_time->departure)) {
                        $res["departure_state"] = "deleted";
                        $res["departure_date_time"] = null;
                    }
                    if ($i > 0 && !isset($stop_time->arrival)) {
                        $res["arrival_state"] = "deleted";
                        $res["arrival_date_time"] = null;
                    }

                    if (isset($stop_time->scheduleRelationship) && $stop_time->scheduleRelationship == "SKIPPED") {
                        $res["departure_state"] = "deleted";
                        $res["departure_date_time"] = null;
                        $res["arrival_state"] = "deleted";
                        $res["arrival_date_time"] = null;
                    }
                    return $res;
                }
            }
        }
        return $res;
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
            SELECT DISTINCT ST.trip_id, ST.*, T.*
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