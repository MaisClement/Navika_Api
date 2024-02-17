<?php

namespace App\Controller;

use App\Repository\RoutesRepository;
use App\Repository\TraficRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Trafic
{
    private $params;
    
    private RoutesRepository $routesRepository;

    public function __construct(ParameterBagInterface $params, RoutesRepository $routesRepository)
    {
        $this->params = $params;

        $this->routesRepository = $routesRepository;
    }

    /**
     * Get trafic
     * 
     * Get trafic reports
     * 
     * You can defined wich lines do you want by using "lines[]" parameter. If not provided, default lines will be returned
     */

    #[Route('/trafic', name: 'get_trafic', methods: ['GET'])]
    #[OA\Tag(name: 'Trafic')]
    #[OA\Parameter(
        name: "lines[]",
        in: "query",
        description: "An array of line_id",
        schema: new OA\Schema(
            type: "array",
            items: new OA\Items(type: "string")
        )
    )]

    #[OA\Response(
        response: 200,
        description: 'OK'
    )]

    public function getTrafic(Request $request): JsonResponse
    {
        $lines = $request->get('lines') != null ? $request->get('lines') : $this->params->get('lines');

        // --- 

        $json = [];
        $json['trafic'] = [];

        foreach ($lines as $line) {
            $route = $this->routesRepository->findOneBy(['route_id' => $line]);

            if ($route != null) {
                $json['trafic'][] = $route->getRouteAndTrafic();
            }
        }
        
        return new JsonResponse($json);
    }
}