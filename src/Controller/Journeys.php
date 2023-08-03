<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use App\Controller\Functions;

class Journeys
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }
 
    #[Route('/journeys', name: 'get_journeys')]
    public function getJourneys(Request $request)
    {
        $from   = $request->get('from');
        $to     = $request->get('to');
  
        if (!isset($from) || !isset($to)) {
            return new JsonResponse(Functions::ErrorMessage(400, 'One or more parameters are missing or null, have you "from" and "to" ?'), 400);
        }

        // ------------
        
        $datetime = $request->get('datetime') ?? date("c");
        $traveler_type = $request->get('traveler_type') ?? 'standard';

        $url = $this->params->get('prim_url') . '/journeys?from=' . $from . '&to=' . $to . '&datetime=' . $datetime . '&traveler_type=' . $traveler_type . '&depth=3&data_freshness=realtime';
        // $url = urlencode(trim($url));

        $client = HttpClient::create();        
        $response = $client->request('GET', $url, [
            'headers' => [
                'apiKey' => $this->params->get('prim_api_key'),
            ],
        ]);
        $status = $response->getStatusCode();

        if ($status != 200){
            return new JsonResponse(Functions::ErrorMessage(500, 'Cannot get data from provider'), 500);
        }

        $content = $response->getContent();
        $results = json_decode($content);

        $journeys = [];
        foreach ($results->journeys as $result) {
            $public_transport_distance = 0;

            $sections = [];
            foreach ($result->sections as $section) {
                if (isset($section->display_informations)) {
                    $informations = array(
                        "direction" => array(
                            "id"        =>  (string)    $section->display_informations->direction,
                            "name"      =>  (string)    $section->display_informations->direction,
                        ),
                        "trip_id"            =>  (string)    isset($result->ItemIdentifier) ? $result->ItemIdentifier : "",
                        "trip_name"     =>  (string)    $section->display_informations->trip_short_name,
                        "headsign"      =>  (string)    $section->display_informations->headsign,
                        "description"   =>  (string)    $section->display_informations->description,
                        "message"       =>  (string)    "",
                        "line"     => array(
                            "id"         =>  (string)   "IDFM:" . Functions::idfmFormat( Functions::getLineId($section->links) ),
                            "code"       =>  (string)   $section->display_informations->code,
                            "name"       =>  (string)   $section->display_informations->network . ' ' . $section->display_informations->name,
                            "mode"       =>  (string)   $section->display_informations->physical_mode,
                            "color"      =>  (string)   $section->display_informations->color,
                            "text_color" =>  (string)   $section->display_informations->text_color,
                        ),
                    );
                }
                $sections[] = array(
                    "type"          =>  (string)    $section->type,
                    "mode"          =>  (string)    isset($section->mode) ? $section->mode : $section->type,
                    "arrival_date_time"     =>  (string)    $section->arrival_date_time,
                    "departure_date_time"   =>  (string)    $section->departure_date_time,
                    "duration"      =>  (int)       $section->duration,
                    "informations"  => isset($section->display_informations) ? $informations : null,
                    "from" => array(
                        "id"        =>  (string)    $section->from->id,
                        "name"      =>  (string)    $section->from->{$section->from->embedded_type}->name,
                        "type"      =>  (string)    $section->from->embedded_type,
                        "distance"  =>  (int)       isset($section->from->distance) ? $section->from->distance : 0,
                        "town"      =>  (string)    Functions::getTownByAdministrativeRegions($section->from->{$section->from->embedded_type}->administrative_regions),
                        "zip_code"  =>  (string)    substr(Functions::getZipByAdministrativeRegions($section->from->{$section->from->embedded_type}->administrative_regions), 0, 2),
                        "coord"     => array(
                            "lat"           =>  floatval($section->from->{$section->from->embedded_type}->coord->lat),
                            "lon"           =>  floatval($section->from->{$section->from->embedded_type}->coord->lon),
                        ),
                    ),
                    "to" => array(
                        "id"        =>  (string)    $section->to->id,
                        "name"      =>  (string)    $section->to->{$section->to->embedded_type}->name,
                        "type"      =>  (string)    $section->to->embedded_type,
                        "town"      =>  (string)    Functions::getTownByAdministrativeRegions($section->to->{$section->to->embedded_type}->administrative_regions),
                        "zip_code"  =>  (string)    substr(Functions::getZipByAdministrativeRegions($section->to->{$section->to->embedded_type}->administrative_regions), 0, 2),
                        "coord"     => array(
                            "lat"       =>  floatval($section->to->{$section->to->embedded_type}->coord->lat),
                            "lon"       =>  floatval($section->to->{$section->to->embedded_type}->coord->lon),
                        ),
                    ),
                    "stop_date_times"   => isset($section->stop_date_times) ? $section->stop_date_times : null,
                    "geojson"           => isset($section->geojson)         ? $section->geojson : null,
                );
                if ($section->type == "public_transport") {
                    $public_transport_distance += (int) $section->geojson->properties[0]->length;
                }
            }

            $journeys[] = array(
                "type"                  =>  (string) $result->type,
                "duration"              =>  (int) $result->duration,

                "requested_date_time"   => $result->requested_date_time,
                "departure_date_time"   => $result->departure_date_time,
                "arrival_date_time"     => $result->arrival_date_time,

                "nb_transfers"          =>  (int)    floatval($result->type),
                "co2_emission"          => $result->co2_emission->value,
                "fare"                  => $result->fare->total->value / 100,
                "distances"             => array(
                    "walking"                  => $result->distances->walking,
                    "public_transport"         => $public_transport_distance
                ),
                "sections"              => $sections
            );
        }

        $json["journeys"] = $journeys;

        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}