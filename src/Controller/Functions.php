<?php

namespace App\Controller;
use Symfony\Component\HttpClient\HttpClient;
use Google\Transit\Realtime\FeedMessage;
use DateTime;
use DateTimeZone;

class Functions
{
    /**
     * Itinerary analysis of the trip updates already seen during this request.
     *
     * @var array
     */
    private static array $trip_update_analysis = [];

    /**
     * Stop areas already resolved during this request.
     *
     * @var array
     */
    private static array $parent_stops = [];

    public static function httpErrorMessage($http_code, $details = ''): array
    {
        return array(
            'error' => array(
                'code' => (int) $http_code,
                'message' => (string) null,
                'details' => (string) $details ?? '',
            )
        );
    }

    public static function httpSuccesMessage($http_code, $details = ''): array
    {
        return array(
            'succes' => array(
                'code' => (int) $http_code,
                'message' => (string) null,
                'details' => (string) $details ?? '',
            )
        );
    }

    public static function getTransportMode($mode): string
    {
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

        return $modes[$mode];
    }

    public static function getTypeFromPelias($el): string
    {
        $search = [
            'venue',
        ];
        $replace = [
            'poi',
        ];

        return str_replace($search, $replace, $el);

    }

    public static function getTownByAdministrativeRegions($administrative_regions): string
    {
        foreach ($administrative_regions as $region) {
            if ($region->level == 8) {
                return $region->name;
            }
        }
        return "";
    }

    public static function getZipByAdministrativeRegions($administrative_regions): string
    {
        foreach ($administrative_regions as $region) {
            if ($region->level == 8) {
                return substr($region->insee, 0, 2);
            }
        }
        return "";
    }

    public static function getLineId($links): mixed
    {
        foreach ($links as $link) {
            if ($link->type == 'line') {
                return $link->id;
            }
        }
    }

    public static function getJourneyId($links): string
    {
        foreach ($links as $link) {
            if ($link->rel == 'this_journey') {
                return Functions::base64url_encode(substr($link->href, strpos($link->href, 'journeys')));
            }
        }
    }

    public static function base64url_encode($s): string
    {
        return str_replace(array('+', '/'), array('-', '_'), base64_encode($s));
    }

    public static function base64url_decode($s): string
    {
        return base64_decode(str_replace(array('-', '_'), array('+', '/'), $s));
    }

    public static function order_reports($array): array
    {
        usort($array, function ($a, $b) {

            if ($a["severity"] != $b["severity"]) {
                return ($a["severity"] < $b["severity"]) ? 1 : -1;
            }

            return ($a["updated_at"] < $b["updated_at"]) ? 1 : -1;
        });
        return $array;
    }

    public static function order_line($array): array
    {
        usort($array, function ($a, $b) {
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

    public static function order_routes($array, $query): array
    {
        usort($array, function ($a, $b) use ($query) {
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

    public static function orderPlaces($array): array
    {
        usort($array, function ($a, $b): int {
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

    public static function orderByDistance($array, $latitudeTo, $longitudeTo): array
    {
        usort($array, function ($a, $b) use ($latitudeTo, $longitudeTo): int {

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

    public static function levenshteinDistance($str1, $str2): mixed
    {
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

    public static function orderWithLevenshtein($array, $text): array
    {
        usort($array, function ($a, $b) use ($text) {
            $levA = Functions::levenshteinDistance($text, $a['name']);
            $levB = Functions::levenshteinDistance($text, $b['name']);

            return $levA - $levB;
        });

        return $array;
    }

    public static function filterStopsWithLevenshtein($array, $text, $limit = 3): array
    {
        $res = [];
        foreach ($array as $element) {
            $d = levenshtein($element->getStopId()->getStopName(), $text);
            if ($d <= 3) {
                $res[] = $element;
            }
        }

        return $res;
    }

    public static function orderPlacesMixed(array $array, string $text): array
    {
        $text = strtolower($text);

        usort($array, function ($a, $b) use ($text): int {
            // 1. Calcul de la distance avec la fonction native PHP
            $levA = levenshtein($text, strtolower($a['name']));
            $levB = levenshtein($text, strtolower($b['name']));

            // 2. Définition des "paliers" de tolérance (Tiers)
            // 0 = Exact, 1 = Petite faute (1-2), 2 = Faute moyenne (3-4), 3 = Loin
            $getTier = function(int $lev): int {
                if ($lev === 0) return 0;
                if ($lev <= 2) return 1;
                if ($lev <= 4) return 2;
                return 3;
            };

            $tierA = $getTier($levA);
            $tierB = $getTier($levB);

            // Si les paliers sont différents, le meilleur palier gagne (le plus petit)
            if ($tierA !== $tierB) {
                return $tierA <=> $tierB;
            }

            // 3. À palier égal, on calcule un "Score d'importance"
            // On convertit tes critères en un score numérique unique pour simplifier le tri
            $getImportanceScore = function (array $item): float {
                $modes = $item['modes'] ?? [];
                $lines = $item['lines'] ?? [];
                
                $score = count($modes);
                if (in_array('nationalrail', $modes, true)) {
                    $score += 1; // Bonus pour les gares nationales
                }
                
                // On utilise les lignes comme un critère secondaire (décimales)
                // Ex: 2 modes et 5 lignes = score de 2.05
                $score += count($lines) * 0.02; 
                
                return $score;
            };

            $importanceA = $getImportanceScore($a);
            $importanceB = $getImportanceScore($b);

            // Si l'importance est différente, on trie par importance décroissante (le plus grand en premier)
            if ($importanceA !== $importanceB) {
                return $importanceB <=> $importanceA;
            }

            // 4. En cas d'égalité parfaite (même palier de faute ET même importance)
            // On départage sur la distance de Levenshtein pure (le plus petit en premier)
            if ($levA !== $levB) {
                return $levA <=> $levB;
            }

            // Ultime secours : tri alphabétique
            return $a['name'] <=> $b['name'];
        });

        return $array;
    }

    public static function orderDeparture($array): array
    {
        usort($array, function ($a, $b): int {

            if ($a['stop_date_time']['departure_date_time'] != "") {
                $a = new DateTime($a['stop_date_time']['departure_date_time']);
            } else if ($a['stop_date_time']['arrival_date_time'] != "") {
                $a = new DateTime($a['stop_date_time']['arrival_date_time']);
            }

            if ($b['stop_date_time']['departure_date_time'] != "") {
                $b = new DateTime($b['stop_date_time']['departure_date_time']);
            } else if ($b['stop_date_time']['arrival_date_time'] != "") {
                $b = new DateTime($b['stop_date_time']['arrival_date_time']);
            }

            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });
        return $array;
    }

    public static function calculateDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo): float|int
    {
        $distance = Functions::getDistanceBeetwenPoints(
            (float) $latitudeFrom,
            (float) $longitudeFrom,
            (float) $latitudeTo,
            (float) $longitudeTo
        );
        $distance = ceil($distance);

        return $distance > 1000 ? 0 : $distance;
    }

    public static function getDistanceBeetwenPoints($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo): float
    {
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

    public static function getReportsMesageTitle($messages): mixed
    {
        foreach ($messages as $message) {
            if ($message->channel->name == 'titre') {
                return $message->text;
            }
        }
        return '';
    }

    public static function getReportsMesageText($messages): string
    {
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

    public static function getSeverity($effect, $cause, $status): int
    {
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

    public static function idfmFormat($str): string
    {
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

    public static function gareFormat($id): mixed
    {
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
        $id = trim($id);
        return ucfirst($id);
    }

    public static function getCSVHeader($csv, $sep = ';'): array
    {
        $line = [];
        $file = fopen($csv, 'r');
        while (!feof($file)) {
            $line[] = fgetcsv($file, 0, $sep);
            break;
        }
        fclose($file);
        return $line;
    }

    public static function readCsv($csv, $sep = ';'): array
    {
        $line = [];
        $file = fopen($csv, 'r');
        while (!feof($file)) {
            $line[] = fgetcsv($file, 0, $sep);
        }
        fclose($file);
        return $line;
    }

    public static function isValidDateYMD($date): bool
    {
        $format = 'Y-m-d';
        $date_time = DateTime::createFromFormat($format, $date);
        return $date_time && $date_time->format($format) === $date;
    }

    public static function isToday($date): bool
    {
        $today = new DateTime("today");

        $format = 'Y-m-d';
        $date_time = DateTime::createFromFormat($format, $date);
        $date_time->setTime(0, 0, 0);

        $interval = $today->diff($date_time);
        $diffDays = $interval->days;

        return $diffDays == 0;
    }

    public static function addRealTime($el, $real_time): mixed
    {
        foreach ($real_time as $real) {
            if ($real['id'] != null && $el['trip_id'] != null && $real['id'] == $el['trip_id']) {
                return $real['date_time'];
            }
        }
        foreach ($real_time as $real) {
            if (isset($el['trip_name'])) {
                if ($real['id'] == $el['trip_name'] && strlen($el['trip_name']) >= 6) {
                    return $real['date_time'];
                }
            }
        }
        // foreach ($real_time as $real) {
        //     if ($real['trip_name'] == $el['trip_name'] && strlen($el['trip_name']) >= 6) {
        //         return $real['date_time'];
        //     }
        // }
        return null;
    }

    public static function isSameTime($date1, $date2): bool
    {
        $date1 = new DateTime($date1);
        $date2 = new DateTime($date2);

        return $date1 == $date2;
    }

    public static function prepareTime($dt, $i = false): string
    {
        if ($dt == '')
            return '';

        $date_time = $i == true ? date_create($dt, timezone_open('Europe/Paris')) : date_create($dt, timezone_open('UTC'));

        if (is_bool($date_time)) {
            $s = $dt;
            $dt = explode(' ', $dt);
            $dateArray = explode('-', $dt[0]);

            if (!isset($dateArray[1])) {
                $year = date("Y");
                $month = date("m");
                $day = date("d");

                $timeArray = $dt[0];

            } else {
                $year = (int) $dateArray[0];
                $month = (int) $dateArray[1];
                $day = (int) $dateArray[2];

                $timeArray = $dt[0];
            }
            
            $hours = (int) $timeArray[0];
            $minutes = (int) $timeArray[1];
            $seconds = (int) $timeArray[2];

            // 'cause GTFS time can be 25:00:00
            $hours %= 24;
            $minutes %= 60;
            $seconds %= 60;

            $date_time = new DateTime();
            $date_time->setDate($year, $month, $day);
            $date_time->setTime($hours, $minutes, $seconds);
            $date_time->setTimezone(new DateTimeZone('Europe/Paris'));

            if ($timeArray[0] >= 24) {
                $date_time->modify('+1 day');
            }
        }
        return date_format($date_time, DATE_ATOM);
    }

    public static function getState($call): mixed
    {
        // theorical - ontime - delayed - cancelled - modified
        if (isset($call->DepartureStatus) && ($call->DepartureStatus == "cancelled" || $call->DepartureStatus == "delayed"))
            return $call->DepartureStatus;

        if (isset($call->ArrivalStatus) && ($call->ArrivalStatus == "cancelled" || $call->ArrivalStatus == "delayed"))
            return $call->ArrivalStatus;

        if ((isset($call->DepartureStatus) && $call->DepartureStatus == "onTime") || (isset($call->ArrivalStatus) && $call->ArrivalStatus == "onTime"))
            return "ontime";

        return "ontime";
    }

    public static function getMessage($call): string
    {
        // terminus - origin
        if (!isset($call->ExpectedDepartureTime) && !isset($call->AimedDepartureTime)) {
            return "terminus";
        } elseif (!isset($call->ExpectedArrivalTime) && !isset($call->AimedArrivalTime)) {
            return "origin";
        } else
            return "";
    }

    public static function getVehicleSize($vehicle_feature)
    {
        if ( in_array('shortTrain', $vehicle_feature))
            return 'short';
        if ( in_array('longTrain', $vehicle_feature))
            return 'long';
        return null;
    }

    public static function getIDFMID($id): mixed
    {
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

    public static function getRATPName($id): mixed
    {
        $pattern = 'RATP\.(\w+):LOC';

        if (preg_match($pattern, $id, $matches)) {
            return $matches[0];
        } else {
            return $id;
        }
    }

    public static function getStopDateTime($call): array
    {
        return array(
            "base_departure_date_time" => (string) isset($call->AimedDepartureTime) ? $call->AimedDepartureTime : (isset($call->ExpectedDepartureTime) ? $call->ExpectedDepartureTime : ''),
            "departure_date_time" => (string) isset($call->ExpectedDepartureTime) ? $call->ExpectedDepartureTime : (isset($call->AimedDepartureTime) ? $call->AimedDepartureTime : ''),
            "base_arrival_date_time" => (string) isset($call->AimedArrivalTime) ? $call->AimedArrivalTime : (isset($call->ExpectedArrivalTime) ? $call->ExpectedArrivalTime : ''),
            "arrival_date_time" => (string) isset($call->ExpectedArrivalTime) ? $call->ExpectedArrivalTime : (isset($call->AimedArrivalTime) ? $call->AimedArrivalTime : ''),
            "state" => (string) Functions::getState($call),
            "atStop" => (string) isset($call->VehicleAtStop) !== '' && (string) isset($call->VehicleAtStop) !== '0' ? ($call->VehicleAtStop ? 'true' : 'false') : 'false',
            "platform" => (string) isset($call->ArrivalPlatformName->value) !== '' && (string) isset($call->ArrivalPlatformName->value) !== '0' ? $call->ArrivalPlatformName->value : '-'
        );
    }

    public static function isInNext12Hours($departure, $arrival): bool
    {
        if (isset($departure)) {
            return date_create($departure) <= date_create('+12 hours');
        }
        if (isset($arrival)) {
            return date_create($arrival) <= date_create('+12 hours');
        }
        return true;
    }

    public static function isFuture($real_time_departure, $departure, $real_time_arrival, $arrival): bool
    {
        if (isset($real_time_departure)) {
            return date_create($real_time_departure) >= date_create();
        }
        if (isset($departure)) {
            return date_create($departure) >= date_create();
        }
        if (isset($real_time_arrival)) {
            return date_create($real_time_arrival) >= date_create();
        }
        if (isset($arrival)) {
            return date_create($arrival) >= date_create();
        }
        return true;
    }

    public static function callIsFuture($call): bool
    {
        if (isset($call->ExpectedDepartureTime)) {
            return date_create($call->ExpectedDepartureTime) >= date_create();
        }
        if (isset($call->ExpectedArrivalTime)) {
            return date_create($call->ExpectedArrivalTime) >= date_create();
        }
        if (isset($call->AimedDepartureTime)) {
            return date_create($call->AimedDepartureTime) >= date_create();
        }
        if (isset($call->AimedArrivalTime)) {
            return date_create($call->AimedArrivalTime) >= date_create();
        }
    }

    /**
     * Retrieves the stop area a stop point belongs to.
     *
     * @param mixed $em The entity manager.
     * @param mixed $id The ID of the stop point.
     * @return mixed The stop area, null when it is unknown.
     */
    public static function getParentStopById($em, $id): mixed
    {
        if ($id == null) {
            return null;
        }

        if (array_key_exists($id, self::$parent_stops)) {
            return self::$parent_stops[$id];
        }

        $req = $em->prepare("
            SELECT S2.stop_id, S2.stop_name
            FROM stops S

            JOIN stops S2
            ON S.parent_station = S2.stop_id

            WHERE S.stop_id = :stop_id;
        ");
        $req->bindValue("stop_id", $id);
        $results = $req->executeQuery();

        $res = $results->fetchAll();
        self::$parent_stops[$id] = array_key_exists(0, $res) ? $res[0] : null;

        return self::$parent_stops[$id];
    }

    public static function getParentId($em, $id): mixed
    {
        $req = $em->prepare("
            SELECT parent_station
            FROM stops
            WHERE stop_id = :stop_id;
        ");
        $req->bindValue("stop_id", $id);
        $results = $req->executeQuery();

        $res = $results->fetchAll();

        if (array_key_exists(0, $res)) {
            return $res[0]['parent_station'];
        }
        return $id;
    }

    /**
     * Retrieves the realtime data from the specified provider.
     *
     * @param mixed $provider The provider from which to retrieve the data.
     * @return mixed The realtime data : the trip updates of the feed, and the
     *               messages of the service alerts feed already formatted for
     *               the API.
     */
    public static function getRealtimeData($provider): mixed
    {
        return array(
            "trip_updates"  => self::getGtfsRtEntities($provider, $provider->getGtfsRtTripUpdates()),
            "alerts"        => self::getRealtimeAlerts($provider),
        );
    }

    /**
     * Downloads a GTFS-RT feed and decodes the entities it carries.
     *
     * @param mixed $provider The provider owning the feed.
     * @param mixed $url The url of the feed, null when the provider has none.
     * @return array The entities of the feed.
     */
    private static function getGtfsRtEntities($provider, $url): array
    {
        if ($url == null) {
            return [];
        }

        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200) {
            return [];
        }

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

        $decoded = json_decode($content);

        return isset($decoded->entity) ? $decoded->entity : [];
    }

    /**
     * Messages of the service alerts feed of a provider, formatted as API
     * reports.
     *
     * Only the alerts naming a trip are kept : the ones about a whole line are
     * already gathered by the `app:trafic:update` task, and served aside the
     * line they disturb.
     *
     * @param mixed $provider The provider from which to retrieve the alerts.
     * @return array The alerts, each one as its report and the trips it informs.
     */
    public static function getRealtimeAlerts($provider): array
    {
        $alerts = [];

        foreach (self::getGtfsRtEntities($provider, $provider->getGtfsRtServicesAlerts()) as $entity) {
            if (!isset($entity->alert)) {
                continue;
            }

            $alert = $entity->alert;
            $trips = [];

            $informed_entities = isset($alert->informedEntity) ? $alert->informedEntity : [];

            foreach ($informed_entities as $informed_entity) {
                if (isset($informed_entity->trip->tripId) && $informed_entity->trip->tripId != '') {
                    $trips[] = (string) $informed_entity->trip->tripId;
                }
            }

            if (count($trips) == 0) {
                continue;
            }

            $status = self::getGtfsRtStatus($alert);

            // The alert has nothing to tell about the trip anymore.
            if ($status == 'past') {
                continue;
            }

            $title = self::getGtfsRtText(isset($alert->headerText) ? $alert->headerText : null);
            $text = self::getGtfsRtText(isset($alert->descriptionText) ? $alert->descriptionText : null);

            // Nothing to display.
            if ($title == '' && $text == '') {
                continue;
            }

            $cause = self::getGtfsRtCause(isset($alert->cause) ? $alert->cause : 'UNKNOWN_CAUSE');
            $effect = isset($alert->effect) ? $alert->effect : 'OTHER_EFFECT';

            $alerts[] = array(
                "trips" => $trips,
                "report" => array(
                    "id"        => (string) $provider->getId() . ':' . (isset($entity->id) ? $entity->id : ''),
                    "status"    => (string) $status,
                    "cause"     => (string) $cause,
                    "severity"  => (int) self::getGtfsRtSeverity($alert, $effect, $cause, $status),
                    "effect"    => (string) $effect,
                    "message"   => array(
                        "title" => (string) ($title != '' ? $title : $text),
                        "text"  => (string) ($title != '' ? $text : ''),
                    ),
                ),
            );
        }

        return $alerts;
    }

    /**
     * Reports of the service alerts feed which inform a trip.
     *
     * A feed does not always name a trip the way the schedules do : the SNCF
     * one only gives `OCESN<train number>F` where the trip id also carries the
     * agency and the itinerary (`OCESN6949F1187_F:OUI:FR:Line::...`). A trip is
     * informed as soon as its id starts with the one given by the alert.
     *
     * @param array $alerts The alerts, as returned by getRealtimeData.
     * @param mixed $trip_id The id of the trip.
     * @return array The reports informing the trip.
     */
    public static function getTripRealtimeAlerts($alerts, $trip_id): array
    {
        $regex = "/:\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/";
        $trip_id = preg_replace($regex, '', $trip_id);

        $reports = [];

        foreach ($alerts as $alert) {
            foreach ($alert['trips'] as $informed_trip_id) {
                if (str_starts_with($trip_id, $informed_trip_id)) {
                    $reports[] = $alert['report'];
                    break;
                }
            }
        }

        return $reports;
    }

    /**
     * Text of a GTFS-RT translated string, in French as soon as the feed gives
     * it, cleaned up from the html a producer may add.
     *
     * @param mixed $translated_string The translated string of the feed.
     * @param string $language The wanted language.
     * @return string The text, empty when the feed gives none.
     */
    private static function getGtfsRtText($translated_string, $language = 'fr'): string
    {
        if ($translated_string == null || !isset($translated_string->translation)) {
            return '';
        }

        $text = null;

        foreach ($translated_string->translation as $translation) {
            if (!isset($translation->text)) {
                continue;
            }

            // The feed does not always tell the language, the first text is
            // then the only one to display.
            if ($text === null) {
                $text = $translation->text;
            }

            if (isset($translation->language) && $translation->language == $language) {
                $text = $translation->text;
                break;
            }
        }

        if ($text === null) {
            return '';
        }

        $search = ['<br>', '</p>', '  '];
        $replace = [PHP_EOL, PHP_EOL, ' '];

        $text = str_replace($search, $replace, $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text);

        return trim($text);
    }

    /**
     * Status of an alert, read from its active periods : one without an end
     * never ends, one without a start has always begun, and an alert giving no
     * period at all is always active.
     *
     * @param mixed $alert The alert of the feed.
     * @return string The status : active, future or past.
     */
    private static function getGtfsRtStatus($alert): string
    {
        if (!isset($alert->activePeriod) || count($alert->activePeriod) == 0) {
            return 'active';
        }

        $now = time();
        $is_future = false;

        foreach ($alert->activePeriod as $period) {
            $start = isset($period->start) ? (int) $period->start : null;
            $end = isset($period->end) ? (int) $period->end : null;

            if ($start !== null && $now < $start) {
                $is_future = true;
                continue;
            }

            if ($end !== null && $now > $end) {
                continue;
            }

            return 'active';
        }

        return $is_future ? 'future' : 'past';
    }

    /**
     * Cause of an alert, as the API names it.
     *
     * @param mixed $cause The cause given by the feed.
     * @return string The cause for the API.
     */
    private static function getGtfsRtCause($cause): string
    {
        $causes = array(
            'UNKNOWN_CAUSE' => 'perturbation',
            'OTHER_CAUSE' => 'perturbation',
            'TECHNICAL_PROBLEM' => 'perturbation',
            'STRIKE' => 'perturbation',
            'DEMONSTRATION' => 'perturbation',
            'ACCIDENT' => 'perturbation',
            'HOLIDAY' => 'perturbation',
            'WEATHER' => 'perturbation',
            'MAINTENANCE' => 'travaux',
            'CONSTRUCTION' => 'travaux',
            'POLICE_ACTIVITY' => 'perturbation',
            'MEDICAL_EMERGENCY' => 'perturbation',
        );

        return isset($causes[$cause]) ? $causes[$cause] : 'perturbation';
    }

    /**
     * Severity of an alert : the one the feed gives when it does, otherwise the
     * one deduced from its effect.
     *
     * @param mixed $alert The alert of the feed.
     * @param mixed $effect The effect of the alert.
     * @param mixed $cause The cause of the alert, as the API names it.
     * @param mixed $status The status of the alert.
     * @return int The severity.
     */
    private static function getGtfsRtSeverity($alert, $effect, $cause, $status): int
    {
        $severities = array(
            'INFO' => 1,
            'WARNING' => 4,
            'SEVERE' => 5,
        );

        if (isset($alert->severityLevel) && isset($severities[$alert->severityLevel])) {
            return $severities[$alert->severityLevel];
        }

        return self::getSeverity($effect, $cause, $status);
    }

    /**
     * Analyse the stopTimeUpdate list of a trip update to rebuild the itinerary
     * really operated by the vehicle.
     *
     * GTFS-RT conventions used here :
     *  - a stop with the SKIPPED scheduleRelationship is not served anymore ;
     *  - a stop having an arrival but no departure, and which is not the last
     *    one of the feed, means the vehicle terminates there (exceptional
     *    terminus) : every following stop is dropped ;
     *  - a stop having a departure but no arrival, and which is not the first
     *    one of the feed, means the vehicle starts there (exceptional origin) :
     *    every previous stop is dropped.
     *
     * The two last rules are only applied when the feed is known to publish
     * both times (some producers only publish departures, or only arrivals),
     * otherwise every stop would look like a terminus / an origin.
     *
     * @param mixed $trip_update The trip update, as returned by getTripRealtime.
     * @return array The itinerary analysis.
     */
    public static function analyzeTripUpdate($trip_update): array
    {
        $res = array(
            "stops" => array(),
            "origin_id" => null,
            "terminus_id" => null,
            "has_exceptional_origin" => false,
            "has_exceptional_terminus" => false,
            "is_modified" => false,
            "is_cancelled" => false,
            "is_added" => false,
            "is_delayed" => false,
        );

        if ($trip_update == null || !isset($trip_update['trip_update']) || $trip_update['trip_update'] == null) {
            return $res;
        }

        $update = $trip_update['trip_update'];

        // Analysing the same feed entity once per stop would be pointless, the
        // result only depends on the trip and on the timestamp of the update.
        $cache_key = (isset($update->trip->tripId) ? $update->trip->tripId : '')
            . '|' . (isset($update->timestamp) ? $update->timestamp : '');

        if ($cache_key != '|' && isset(self::$trip_update_analysis[$cache_key])) {
            return self::$trip_update_analysis[$cache_key];
        }

        if (isset($update->trip->scheduleRelationship)) {
            $schedule_relationship = $update->trip->scheduleRelationship;

            if ($schedule_relationship == "CANCELED" || $schedule_relationship == "DELETED") {
                $res['is_cancelled'] = true;
            }
            if ($schedule_relationship == "ADDED" || $schedule_relationship == "DUPLICATED") {
                $res['is_added'] = true;
            }
        }

        $stop_times = isset($update->stopTimeUpdate) ? $update->stopTimeUpdate : array();
        $len = count($stop_times);

        if ($len == 0) {
            return $res;
        }

        // ---- Which stops are still served ?
        $served = array();
        $feed_has_departures = false;
        $feed_has_arrivals = false;

        for ($i = 0; $i < $len; $i++) {
            $stop_time = $stop_times[$i];

            $skipped = isset($stop_time->scheduleRelationship) && $stop_time->scheduleRelationship == "SKIPPED";
            $served[$i] = !$skipped && !$res['is_cancelled'];

            if (!$skipped && $i > 0 && $i < $len - 1) {
                if (isset($stop_time->departure)) {
                    $feed_has_departures = true;
                }
                if (isset($stop_time->arrival)) {
                    $feed_has_arrivals = true;
                }
            }

            if (!$skipped) {
                if (isset($stop_time->arrival->delay) && $stop_time->arrival->delay != 0) {
                    $res['is_delayed'] = true;
                }
                if (isset($stop_time->departure->delay) && $stop_time->departure->delay != 0) {
                    $res['is_delayed'] = true;
                }
            }
        }

        // ---- The vehicle terminates at the first served stop it never leaves.
        if ($feed_has_departures) {
            for ($i = 0; $i < $len - 1; $i++) {
                if (!$served[$i]) {
                    continue;
                }

                if (isset($stop_times[$i]->arrival) && !isset($stop_times[$i]->departure)) {
                    for ($j = $i + 1; $j < $len; $j++) {
                        $served[$j] = false;
                    }
                    break;
                }
            }
        }

        // ---- ... and starts at the last served stop it never reaches.
        if ($feed_has_arrivals) {
            for ($i = $len - 1; $i > 0; $i--) {
                if (!$served[$i]) {
                    continue;
                }

                if (isset($stop_times[$i]->departure) && !isset($stop_times[$i]->arrival)) {
                    for ($j = $i - 1; $j >= 0; $j--) {
                        $served[$j] = false;
                    }
                    break;
                }
            }
        }

        // ---- Origin and terminus of the itinerary really operated.
        $origin = null;
        $terminus = null;

        for ($i = 0; $i < $len; $i++) {
            if ($served[$i]) {
                if ($origin === null) {
                    $origin = $i;
                }
                $terminus = $i;
            }
        }

        if ($origin !== null) {
            $res['has_exceptional_origin'] = $origin > 0;
            $res['has_exceptional_terminus'] = $terminus < $len - 1;
            $res['origin_id'] = $stop_times[$origin]->stopId;
            $res['terminus_id'] = $stop_times[$terminus]->stopId;

            // A stop dropped between the origin and the terminus changes the
            // served stops, not the boundaries of the itinerary.
            for ($i = $origin; $i <= $terminus; $i++) {
                if (!$served[$i]) {
                    $res['is_modified'] = true;
                    break;
                }
            }
        } else {
            $res['is_cancelled'] = true;
        }

        for ($i = 0; $i < $len; $i++) {
            $stop_id = $stop_times[$i]->stopId;

            // On a trip calling twice at the same place, keep the first call.
            if (isset($res['stops'][$stop_id])) {
                continue;
            }

            $res['stops'][$stop_id] = array(
                "index" => $i,
                "stop_time" => $stop_times[$i],
                "served" => $served[$i],
                "skipped" => isset($stop_times[$i]->scheduleRelationship) && $stop_times[$i]->scheduleRelationship == "SKIPPED",
                "is_origin" => $i === $origin,
                "is_terminus" => $i === $terminus,
            );
        }

        if ($cache_key != '|') {
            self::$trip_update_analysis[$cache_key] = $res;
        }

        return $res;
    }

    /**
     * Retrieves the realtime informations of a trip.
     *
     * @param mixed $trip_update The entities of the realtime feed.
     * @param mixed $trip_id The ID of the trip.
     * @param mixed $stop_id The ID of the stop the trip is requested from.
     * @return array|null The trip update and its state.
     */
    public static function getTripRealtime($trip_update, $trip_id, $stop_id = null): array|null
    {
        $regex = "/:\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/";
        $trip_id = preg_replace($regex, '', $trip_id);

        foreach ($trip_update as $trip) {
            if (!is_object($trip) || !isset($trip->tripUpdate->trip->tripId)) {
                continue;
            }

            if ($trip->tripUpdate->trip->tripId == $trip_id) {
                $res = array(
                    "trip_update" => $trip->tripUpdate,
                    "state" => "ontime",
                );

                $analyze = self::analyzeTripUpdate($res);
                $is_cancelled = $analyze['is_cancelled'];

                // Check if cancelled at the requested stop
                if ($stop_id != null && isset($analyze['stops'][$stop_id]) && !$analyze['stops'][$stop_id]['served']) {
                    $is_cancelled = true;
                }

                // An exceptional origin is reported as a modified trip : the
                // details are in the reports and in the state of each stop.
                if ($is_cancelled) {
                    $res['state'] = "cancelled";
                } else if ($analyze['has_exceptional_terminus']) {
                    $res['state'] = "exceptional_terminus";
                } else if ($analyze['is_modified'] || $analyze['has_exceptional_origin']) {
                    $res['state'] = "modified";
                } else if ($analyze['is_added']) {
                    $res['state'] = "added";
                }

                return $res;
            }
        }
        return null;
    }

    /**
     * Retrieves the realtime reports for a trip.
     *
     * @param mixed $trip_update The trip update data.
     * @param mixed $terminus The name of the exceptional terminus, when known.
     * @param mixed $origin The name of the exceptional origin, when known.
     * @return array The array of realtime reports for the trip.
     */
    public static function getTripRealtimeReports($trip_update, $terminus = null, $origin = null): array
    {
        $message = array(
            "canceled" => array(
                "id" => 'ADMIN:canceled',
                "status" => 'active',
                "cause" => 'canceled',
                "severity" => 5,
                "effect" => 'OTHER',
                "message" => array(
                    "title" => "Supprimé"
                ),
            ),
            "exceptional_terminus" => array(
                "id" => 'ADMIN:exceptional_terminus',
                "status" => 'active',
                "cause" => 'exceptional_terminus',
                "severity" => 5,
                "effect" => 'OTHER',
                "message" => array(
                    "title" => "Terminus exceptionnel"
                ),
            ),
            "exceptional_origin" => array(
                "id" => 'ADMIN:exceptional_origin',
                "status" => 'active',
                "cause" => 'exceptional_origin',
                "severity" => 5,
                "effect" => 'OTHER',
                "message" => array(
                    "title" => "Origine exceptionnelle"
                ),
            ),
            "modified" => array(
                "id" => 'ADMIN:modified',
                "status" => 'active',
                "cause" => 'modified',
                "severity" => 4,
                "effect" => 'OTHER',
                "message" => array(
                    "title" => "Desserte modifiée"
                ),
            ),
            "delayed" => array(
                "id" => 'ADMIN:delayed',
                "status" => 'active',
                "cause" => 'delayed',
                "severity" => 4,
                "effect" => 'OTHER',
                "message" => array(
                    "title" => "Retardé"
                ),
            ),
            "added" => array(
                "id" => 'ADMIN:added',
                "status" => 'active',
                "cause" => 'added',
                "severity" => 1,
                "effect" => 'OTHER',
                "message" => array(
                    "title" => "Trajet supplémentaire"
                ),
            ),
        );

        $reports = [];

        if ($trip_update != null && $trip_update['trip_update'] != null) {
            $analyze = self::analyzeTripUpdate($trip_update);

            if ($analyze['is_cancelled']) {
                $reports[] = $message['canceled'];
            }
            if ($analyze['is_added']) {
                $reports[] = $message['added'];
            }
            if ($analyze['has_exceptional_terminus']) {
                $reports[] = $message['exceptional_terminus'];
            }
            if ($analyze['has_exceptional_origin']) {
                $reports[] = $message['exceptional_origin'];
            }
            if ($analyze['is_modified']) {
                $reports[] = $message['modified'];
            }
            if ($analyze['is_delayed']) {
                $reports[] = $message['delayed'];
            }
        }

        return $reports;
    }

    /**
     * Retrieves the real-time date and time for a specific trip update and stop ID.
     *
     * @param mixed $trip_update The trip update object.
     * @param mixed $stop_id The ID of the stop.
     * @return array An array containing the real-time date and time.
     */
    public static function getTripRealtimeDateTime($trip_update, $stop_id): array
    {
        $res = array(
            "departure_date_time" => null,
            "departure_state" => null,
            "arrival_date_time" => null,
            "arrival_state" => null,
            "departure_delay" => null,
            "arrival_delay" => null,
            "message" => null,
            "state" => "theorical",
            "stop_state" => null,
            "is_origin" => false,
            "is_terminus" => false,
            "is_deleted" => false,
        );

        if ($trip_update == null || !isset($trip_update['trip_update']) || $trip_update['trip_update'] == null) {
            return $res;
        }

        $analyze = self::analyzeTripUpdate($trip_update);

        // The feed says nothing about this stop, keep the theorical times.
        if (!isset($analyze['stops'][$stop_id])) {
            return $res;
        }

        $stop = $analyze['stops'][$stop_id];
        $stop_time = $stop['stop_time'];

        $res['state'] = "ontime";
        $res['is_origin'] = $stop['is_origin'];
        $res['is_terminus'] = $stop['is_terminus'];

        // The stop is not served anymore : the callers keep the theorical
        // times, they are the ones to display, struck through.
        if (!$stop['served']) {
            $res['state'] = "deleted";
            $res['stop_state'] = "deleted";
            $res['is_deleted'] = true;
            $res['departure_state'] = "deleted";
            $res['arrival_state'] = "deleted";
            return $res;
        }

        if (isset($stop_time->arrival)) {
            $date_time = new DateTime();
            $date_time->setTimestamp($stop_time->arrival->time);
            $res['arrival_date_time'] = $date_time->format(DATE_ATOM);

            if (isset($stop_time->arrival->delay)) {
                $res['arrival_delay'] = (int) $stop_time->arrival->delay;

                if ($stop_time->arrival->delay != 0) {
                    $res['arrival_state'] = "delayed";
                }
            }
        }

        if (isset($stop_time->departure)) {
            $date_time = new DateTime();
            $date_time->setTimestamp($stop_time->departure->time);
            $res['departure_date_time'] = $date_time->format(DATE_ATOM);

            if (isset($stop_time->departure->delay)) {
                $res['departure_delay'] = (int) $stop_time->departure->delay;

                if ($stop_time->departure->delay != 0) {
                    $res['departure_state'] = "delayed";
                }
            }
        }

        // The vehicle never leaves its terminus : mirroring the arrival on the
        // departure keeps the delay visible for a client showing departures.
        if (!isset($stop_time->departure)) {
            if ($stop['is_terminus']) {
                $res['departure_date_time'] = $res['arrival_date_time'];
                $res['departure_state'] = $res['arrival_state'];
                $res['departure_delay'] = $res['arrival_delay'];
            } else {
                $res['departure_state'] = "deleted";
                $res['departure_date_time'] = null;
            }
        }

        // Same thing at the origin, the vehicle never arrives there.
        if (!isset($stop_time->arrival)) {
            if ($stop['is_origin']) {
                $res['arrival_date_time'] = $res['departure_date_time'];
                $res['arrival_state'] = $res['departure_state'];
                $res['arrival_delay'] = $res['departure_delay'];
            } else {
                $res['arrival_state'] = "deleted";
                $res['arrival_date_time'] = null;
            }
        }

        if ($stop['is_terminus'] && $analyze['has_exceptional_terminus']) {
            $res['stop_state'] = "exceptional_terminus";
        } else if ($stop['is_origin'] && $analyze['has_exceptional_origin']) {
            $res['stop_state'] = "exceptional_origin";
        } else if ($analyze['is_added']) {
            $res['stop_state'] = "added";
        } else {
            $res['stop_state'] = "unchanged";
        }

        return $res;
    }

    public static function getTerminusOfALine($em, $route): mixed
    {
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
        $req->bindValue("route_id", $route->getRouteId());
        $results = $req->executeQuery();
        return $results->fetchAll();
    }

    /**
     * Retrieves schedules by stop.
     *
     * @param mixed $em The entity manager.
     * @param mixed $stop_id The stop ID.
     * @param mixed $route_id The route ID.
     * @param mixed $date The date.
     * @return mixed The schedules.
     */
    public static function getSchedulesByStop($em, $stop_id, $route_id, $date): mixed
    {
        $req = $em->prepare("
            SELECT DISTINCT ST.*, CONCAT(:date, ' ', ST.departure_time) as departure_time, CONCAT(:date, ' ', ST.arrival_time) as arrival_time, T.*
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

    /**
     * Retrieves the last stop of a trip.
     *
     * @param EntityManagerInterface $em The entity manager.
     * @param int $trip_id The ID of the trip.
     * @return mixed The last stop of the trip.
     */
    public static function getTripStopsById($em, $trip_id, $date): mixed
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

    /**
     * Retrieves the stops of a specific route.
     *
     * @param mixed $em The entity manager.
     * @param mixed $route_id The ID of the route.
     * @return mixed The stops of the specified route.
     */
    public static function getStopsOfRoutes($em, $route_id): mixed
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

    /**
     * Retrieves the stops of a specific route.
     *
     * @param mixed $em The entity manager.
     * @param mixed $route_id The ID of the route.
     * @return mixed The stops of the specified route.
     */
    public static function getStopRelations($em, $route_id): mixed
    {
        $req = $em->prepare("
            SELECT
                s.parent_station as from_stop_id,
                (
                    SELECT 
                        s2.parent_station
                    FROM
                        stops s2
                    JOIN stop_times st2 ON s2.stop_id = st2.stop_id
                    JOIN stops s ON st.stop_id = s.stop_id
                    WHERE 
                        st2.trip_id = t.trip_id
                    AND st2.stop_sequence = st.stop_sequence + 1
                ) AS to_stop_id,
                st.stop_sequence
            FROM 
                routes r
            JOIN trips t ON r.route_id = t.route_id
            JOIN stop_times st ON t.trip_id = st.trip_id
            JOIN stops s ON st.stop_id = s.stop_id
            WHERE 
                r.route_id = :route_id
                AND t.direction_id = '1'
            ORDER BY 
                t.trip_id, st.stop_sequence;

        ");
        $req->bindValue("route_id", $route_id);
        $results = $req->executeQuery();
        return $results->fetchAll();
    }

    /**
     * Retrieves the URI list for the given modes.
     *
     * @param array $modes The modes for which to retrieve the URI list.
     * @return array The URI list for the given modes.
     */
    public static function getModesURIList($modes): array
    {
        if ($modes == null) {
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

        foreach ($modes as $mode) {
            foreach ($all[$mode] as $el) {
                $uri[] = $el;
            }
        }

        return $uri;

        // physical_mode:Air
        // physical_mode:Boat
        // physical_mode:Ferry
    }

    /**
     * Retrieves a list of lines.
     *
     * @param array $forbidden_lines The list of forbidden lines.
     * @return array The list of lines.
     */
    public static function getLinesList($forbidden_lines): array
    {
        if ($forbidden_lines == null) {
            return [];
        }
        foreach ($forbidden_lines as $key => $value) {
            $forbidden_lines[$key] = 'line:' . $value;
        }
        return $forbidden_lines;
    }

    /**
     * Builds a URL using the provided base URL and parameters.
     *
     * @param string $baseUrl The base URL to build upon.
     * @param array $params An associative array of parameters to append to the URL.
     * @return string The built URL.
     */
    public static function buildUrl($baseUrl, $params)
    {
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

    /**
     * Calculates the centroid of a set of points.
     *
     * @param array $points An array of points.
     * @return array The centroid coordinates as an array.
     */
    public static function getCentroidOfStops($points): array
    {
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