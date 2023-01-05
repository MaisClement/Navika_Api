<?php

$results = curl_PRIM($prim_url);
$results = json_decode($results);

file_put_contents($dossier . 'api_' . $id . '.json', json_encode($results));

$responseTimestamp = date_create($results->Siri->ServiceDelivery->ResponseTimestamp);
$results = $results->Siri->ServiceDelivery->StopMonitoringDelivery[0]->MonitoredStopVisit;

$schedules = [];
$departures = [];
$direction = [];

foreach($results as $result){
    if (!isset($result->MonitoredVehicleJourney->MonitoredCall)){
        ErrorMessage(
            520,
            'Invalid fecthed data'
        );
    }
    $call = $result->MonitoredVehicleJourney->MonitoredCall;

    $line_id = idfm_format( $result->MonitoredVehicleJourney->LineRef->value );
    $destination_ref = $result->MonitoredVehicleJourney->DestinationRef->value;
    if ( !isset( $direction[$destination_ref] ) ){
        $request = getDirection($destination_ref);
        $dir_obj = $request->fetch();
        if (isset($dir_obj['stop_name']) && is_string($dir_obj['stop_name'])){
            $direction[$destination_ref] = gare_format($dir_obj['stop_name']);
        } else {
            $direction[$destination_ref] = gare_format($call->DestinationDisplay[0]->value);
        }
    }

    if (!isset( $lines_data[$line_id] )) { 
        $request = getLinesById($line_id);
        $obj = $request->fetch();
        if ($obj['shortname_line'] == 'TER') {
            $line_id = 'TER';
        }
        $lines_data[$line_id] = array(
            "id"         =>  (String)    $line_id,
            "code"       =>  (String)    $obj['shortname_line'],
            "name"       =>  (String)    $obj['name_line'],
            "mode"       =>  (String)    $obj['transportmode'],
            "color"      =>  (String)    strlen($obj['colourweb_hexa']) < 6 ? "000000" : $obj['colourweb_hexa'],
            "text_color" =>  (String)    strlen($obj['textcolourweb_hexa']) < 6 ? "000000" : $obj['textcolourweb_hexa'],
        );
    }
    if (($lines_data[$line_id]['mode'] == "rail" || $lines_data[$line_id]['mode'] == "nationalrail") && date_create(isset($call->ExpectedDepartureTime) ? $call->ExpectedDepartureTime : "") >= date_create()){
            // Si c'est du ferré, l'affichage est different          

        if (!in_array($line_id, $departures_lines)){
            $departures_lines[] = $line_id;
        }
        $departures[$line_id][] = array(
            "informations" => array(
                "direction" => array(
                  "id"         =>  (String)   $destination_ref,
                  "name"       =>  (String)   $direction[$destination_ref], // gare_format( $call->DestinationDisplay[0]->value),
                ),
                "id"            =>  (String)  $result->ItemIdentifier,
                "name"          =>  (String)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) ? $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value : "",
                "mode"          =>  (String)  $lines_data[$line_id]['mode'],
                "trip_name"     =>  (String)  isset($result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value) ? $result->MonitoredVehicleJourney->TrainNumbers->TrainNumberRef[0]->value : "",
                "headsign"      =>  (String)  isset($result->MonitoredVehicleJourney->JourneyNote[0]->value) ? $result->MonitoredVehicleJourney->JourneyNote[0]->value : "",
                "description"   =>  (String)  "",
                "message"       =>  (String)  getMessage($call),
            ),
            "stop_date_time" => array(
                                                      // Si l'horaire est present          On affiche l'horaire est             Sinon, si l'autre est present            On affiche l'autre            Ou rien  
            "base_departure_date_time"  =>  (String)  isset($call->AimedDepartureTime)      ? prepareTime($call->AimedDepartureTime)         : (isset($call->ExpectedDepartureTime)   ? prepareTime($call->ExpectedDepartureTime)  : ""),
            "departure_date_time"       =>  (String)  isset($call->ExpectedDepartureTime)   ? prepareTime($call->ExpectedDepartureTime)      : (isset($call->AimedDepartureTime)      ? prepareTime($call->AimedDepartureTime)     : ""),
            "base_arrival_date_time"    =>  (String)  isset($call->AimedArrivalTime)        ? prepareTime($call->AimedArrivalTime)           : (isset($call->ExpectedArrivalTime)     ? prepareTime($call->ExpectedArrivalTime)    : ""),
            "arrival_date_time"         =>  (String)  isset($call->ExpectedArrivalTime)     ? prepareTime($call->ExpectedArrivalTime)        : (isset($call->AimedArrivalTime)        ? prepareTime($call->AimedArrivalTime)       : ""),
            "state"                     =>  (String)  getState($call),
            "atStop"                    =>  (String)  isset($call->VehicleAtStop)               ? ($call->VehicleAtStop ? 'true' : 'false') : "false",
            "platform"                  =>  (String)  isset($call->ArrivalPlatformName->value)  ? $call->ArrivalPlatformName->value : "-"
            )
        );
    } else {
        // Affichage normal

        if (!isset( $terminus_data[$line_id][$destination_ref] )) { 
            $terminus_data[$line_id][$destination_ref] = array(
                "id"         =>  (String)    $destination_ref,
                "name"       =>  (String)    $direction[$destination_ref], // $call->DestinationDisplay[0]->value,
                "schedules"  =>  array()
            );
        }
        
        if (isset($call->ExpectedDepartureTime)){
            $schedules[$line_id][$destination_ref][] = array(
                                                            // Si l'horaire est present          On affiche l'horaire est             Sinon, si l'autre est present            On affiche l'autre            Ou rien  
                "base_departure_date_time"  =>  (String)  isset($call->AimedDepartureTime)      ? prepareTime($call->AimedDepartureTime)         : (isset($call->ExpectedDepartureTime)   ? prepareTime($call->ExpectedDepartureTime)  : ""),
                "departure_date_time"       =>  (String)  isset($call->ExpectedDepartureTime)   ? prepareTime($call->ExpectedDepartureTime)      : (isset($call->AimedDepartureTime)      ? prepareTime($call->AimedDepartureTime)     : ""),
                "base_arrival_date_time"    =>  (String)  isset($call->AimedArrivalTime)        ? prepareTime($call->AimedArrivalTime)           : (isset($call->ExpectedArrivalTime)     ? prepareTime($call->ExpectedArrivalTime)    : ""),
                "arrival_date_time"         =>  (String)  isset($call->ExpectedArrivalTime)     ? prepareTime($call->ExpectedArrivalTime)        : (isset($call->AimedArrivalTime)        ? prepareTime($call->AimedArrivalTime)       : ""),
                "state"                     =>  (String)  getState($call),
                "atStop"                    =>  (String)  isset($call->VehicleAtStop)               ? ($call->VehicleAtStop ? 'true' : 'false') : "false",
                "platform"                  =>  (String)  isset($call->ArrivalPlatformName->value)  ? $call->ArrivalPlatformName->value : "-"
            );
        }
    }
}

$json = [];
$l = [];

foreach($departures_lines as $line){
    $l[] = $lines_data[$line];
}

usort($l, "order_line");
$departures_l = [];

foreach($l as $line){
    if (isset($departures[$line['id']])){
        foreach($departures[$line['id']] as $departure){
            $line['departures'][] = $departure;
        }
    }
    $json['departures'][] = $line;
}

usort($lines_data, "order_line");

foreach($lines_data as $line){
    if ($line['mode'] != 'rail' && $line['mode'] != 'nationalrail'){

        if (isset($terminus_data[$line['id']])){
            foreach($terminus_data[$line['id']] as $term){

                if (isset($schedules[$line['id']][$term['id']])){
                    foreach($schedules[$line['id']][$term['id']] as $schedule){
                        $term['schedules'][] = $schedule;
                        
                    }
                    $line['terminus_schedules'][] = $term;
                }
            }
        }
        $json['schedules'][] = $line;
    }
}

?>