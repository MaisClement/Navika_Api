<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\StopRouteRepository;
use App\Repository\TownRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Address
{
    private StopRouteRepository $stopRouteRepository;
    private TownRepository $townRepository;
    private $entityManager;
    private $params;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, StopRouteRepository $stopRouteRepository, TownRepository $townRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
    
        $this->stopRouteRepository = $stopRouteRepository;
        $this->townRepository = $townRepository;
    }
    
    /**
     * Get address
     * 
     * Address is provided by Pelias and OSM data. It includes stops, POIs and address.
     * 
     * **At least "q" or "lat" and "lon" must be defined.**
     * 
     * 
     * Unlike `/stops`, results can't be filtered
     * 
     * 
     * For better performance, use `/stops`
     */
    #[Route('/address', name: 'search_address', methods: ['GET'])]
    #[OA\Tag(name: 'Address')]
    #[OA\Parameter(
        name:"lat",
        in:"query",
        description:"Latitude of point",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"lon",
        in:"query",
        description:"Longitude for point",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]

    #[OA\Response(
        response: 200,
        description: 'OK'
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function searchAddress(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if ($lat != null && $lon != null) {
            $url = $this->params->get('geosearch_url') . 'reverse?layers=address&lang=fr&point.lon=' . $lon . '&point.lat=' . $lat;
        
        } else {
            return new JsonResponse(Functions::ErrorMessage(400, 'One or more parameters are missing or null, have you "lat" and "lon" ?'), 400);
        }

        // ------------
        // Places

        $stops = $this->stopRouteRepository->findByNearbyLocation($lat, $lon, 1000);

        $places = [];
        $lines = [];
        $modes = [];

        foreach($stops as $stop) {
            if (!isset($places[$stop->getStopId()->getStopId()])) {

                $places[$stop->getStopId()->getStopId()] = $stop->getStop($lat, $lon);

                $lines[$stop->getStopId()->getStopId()] = [];
                $modes[$stop->getStopId()->getStopId()] = [];
            }

            if (!in_array($stop->getRouteId()->getTransportMode(), $lines[$stop->getStopId()->getStopId()])) {
                $lines[$stop->getStopId()->getStopId()][] = $stop->getRouteId()->getRoute();
            }

            if (!in_array($stop->getRouteId()->getTransportMode(), $modes[$stop->getStopId()->getStopId()])) {
                $modes[$stop->getStopId()->getStopId()][] = $stop->getRouteId()->getTransportMode();
            }
        }

        $echo = [];
        foreach ($places as $key => $place) {
            $lines[$key] = Functions::order_line($lines[$key]);
            $place['lines'] = $lines[$key];
            $place['modes'] = $modes[$key];
            $echo[] = $place;
        }

        $echo = Functions::orderByDistance($echo, $lat, $lon);
        array_splice($echo, 10);

        // foreach($echo as $key => $e) {
        //     if ($e['distance'] == 0) {
        //         $town = $this->townRepository->findTownByCoordinates($e['coord']['lon'], $e['coord']['lat']);
        //         if ($town != null) {
        //             $echo[$key]['town'] = $town->getTownName();
        //             $echo[$key]['zip_code'] = $town->getZipCode();
        //         }
        //     }
        // }

        // ------------
        // GEOSEARCH
        $client = HttpClient::create();        
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200){
            return new JsonResponse(Functions::ErrorMessage(500, 'Can\'t get data from GeoSearch'), 500);
        }

        $content = $response->getContent();
        $results = json_decode($content);

        $results = $results->features;

        $json = array(
            "place" => array(
                "id"         =>  (string)    $results[0]->geometry->coordinates[0] . ';' . $results[0]->geometry->coordinates[1],
                "name"       =>  (string)    $results[0]->properties->name,
                "type"       =>  (string)    Functions::getTypeFromPelias($results[0]->properties->layer),
                "distance"   =>  (float)     (isset($results[0]->distance) ? $results[0]->distance : 0),
                "town"       =>  (string)    (isset($results[0]->properties->locality) ? $results[0]->properties->locality : ''),
                "zip_code"   =>  (string)    (isset($results[0]->properties->postalcode) ? $results[0]->properties->postalcode : ''),
                "department" =>  (string)    (isset($results[0]->properties->region) ? $results[0]->properties->region : ''),
                "region"     =>  (string)    (isset($results[0]->properties->macroregion) ? $results[0]->properties->macroregion : ''),
                "coord"      => array(
                    "lat"       =>  (float) $results[0]->geometry->coordinates[1],
                    "lon"       =>  (float) $results[0]->geometry->coordinates[0],
                ),
            ),
            "near_stops" => $echo,
        );

        // JO 2024
        $jo = array(
            "48.8637;2.3134"                   => "Pont Alexandre III",
            "48.83863;2.378597"                => "Arena Bercy",
            "48.8531;2.30252"                  => "Arena Champ de Mars",
            "48.8997292;2.3605141"             => "Arena Porte de la Chapelle",
            "48.8997292;2.3605141"             => "Arena Porte de la Chapelle",
            "48.906345;2.553544"               => "Clichy-sous-Bois",
            "46.8157;1.7582"                   => "Centre national de tir de Châteauroux",
            "48.8954;2.2294"                   => "Paris La Défense Arena",
            "48.85613;2.2978"                  => "Stade Tour Eiffel",
            "48.86616355;2.3125474"            => "Grand Palais",
            "48.8563881;2.35222203"            => "Hôtel de Ville",
            "48.9721;2.5149"                   => "Arena Paris Nord",
            "48.9721;2.5149"                   => "Arena Paris Nord",
            "48.84156974;2.253048697"          => "Parc des Princes",
            "48.845968;2.253522"               => "Stade Roland Garros",
            "48.845968;2.253522"               => "Stade Roland Garros",
            "48.832968;2.2840069"              => "Arena Paris Sud 1",
            "48.832968;2.2840069"              => "Arena Paris Sud 1",
            "48.78800979;2.03498269"           => "Vélodrome National de Saint-Quentin-en-Yvelines",
            "48.81432266;2.08452588"           => "Château de Versailles",
            "48.81432266;2.08452588"           => "Château de Versailles",
            "48.8623;2.6348"                   => "Stade nautique de Vaires-sur-Marne - bassin d'eaux calmes",
            "48.8622320958382;2.63954636136784"=> "Stade nautique de Vaires-sur-Marne - stade d'eaux vives",
            "48.923723;2.35578"                => "Centre Aquatique",
            "48.8531;2.30252"                  => "Arena Champ de Mars",
            "46.8157;1.7582"                   => "Centre national de tir de Châteauroux",
            "48.8954;2.2294"                   => "Paris La Défense Arena",
            "48.78981063;1.9642379"            => "Colline d’Elancourt",
            "48.85704803;2.312835932"          => "Invalides",
            "48.86640642;2.32119515"           => "La Concorde 1",
            "48.86504456;2.32119516"           => "La Concorde 2",
            "48.86486788;2.32139191"           => "La Concorde 3",
            "48.9372382;2.3994101"             => "La Courneuve",
            "48.829381;2.290865"               => "Arena Paris Sud 6",
            "48.924475;2.360127"               => "Stade de France",
            "48.85972558;2.29221884"           => "Trocadéro",
            "48.78800979;2.03498269"           => "Vélodrome National de Saint-Quentin-en-Yvelines",
            "48.78800979;2.03498269"           => "Stade BMX de Saint-Quentin-en-Yvelines",
            "48.8637;2.3134"                   => "Pont Alexandre III",
            "48.83863;2.378597"                => "Arena Bercy",
            "48.85613;2.2978"                  => "Stade Tour Eiffel",
            "48.86616355;2.3125474"            => "Grand Palais",
            "48.85704803;2.312835932"          => "Invalides",
            "48.93693402;2.41997931"           => "Site d’escalade du Bourget",
            "48.86573765;2.3220383"            => "La Concorde 4",
            "48.8657035745725;2.32121422631576"=> "La Concorde",
            "48.8657035745725;2.32121422631576"=> "La Concorde",
            "48.7532;2.0758"                   => "Golf National",
            "48.830184;2.289033"               => "Arena Paris Sud 4",
            "48.830184;2.289033"               => "Arena Paris Sud 4",
            "48.829381;2.290865"               => "Arena Paris Sud 6",
            "48.924475;2.360127"               => "Stade de France",
            "45.4607;4.3902"                   => "Stade Geoffroy-Guichard",
            "48.8623;2.6348"                   => "Stade nautique de Vaires-sur-Marne - bassin d'eaux calmes",
            "48.92934371;2.24777122"           => "Stade Yves-du-Manoir",
        );

        $id = $lat . ';' . $lon;
        if ( isset($jo[$id]) ) {
            $json['place']['name'] = $jo[$id];
            $json['place']['jo'] = true;
        }

        
        if ($request->get('flag') != null) {
            $json["flag"] = (int) $request->get('flag');
        }

        return new JsonResponse($json);
    }
}
