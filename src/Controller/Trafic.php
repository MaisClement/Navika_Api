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
use App\Service\Logger;

class Trafic
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private Logger $logger;

    private RoutesRepository $routesRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, Logger $logger, RoutesRepository $routesRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->logger = $logger;

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

    /**
     * Get trafic
     * 
     * Get trafic reports by id
     **/

    #[Route('/trafic/{id}', name: 'get_trafic_by_id', methods: ['GET'])]
    #[OA\Tag(name: 'Trafic')]
    #[OA\Parameter(
        name: "id",
        in: "query",
        description: "Line Id",
        schema: new OA\Schema(
            type: "array",
            items: new OA\Items(type: "string")
        )
    )]

    #[OA\Response(
        response: 200,
        description: 'OK'
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function getTraficById(Request $request, $id): JsonResponse
    {
        $route = $this->routesRepository->findOneBy(['route_id' => $id]);

        if ($route == null) {
            $this->logger->logHttpErrorMessage($request, 'Nothing where found for this id', 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this id'), 400);
        }

        // --- 

        $json = [];
        $json['trafic'] = $route->getRouteAndTrafic();

        return new JsonResponse($json);
    }
}