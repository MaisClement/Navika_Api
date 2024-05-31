<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Repository\MapsRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class Maps
{
    private MapsRepository $mapsRepository;
    
    public function __construct(MapsRepository $mapsRepository)
    {
        $this->mapsRepository = $mapsRepository;
    }

    /**
     * Get maps list
     * 
     * Get list of all PDF map available
     */
    #[Route('/maps', name: 'get_maps', methods: ['GET'])]
    #[OA\Tag(name: 'Maps')]

    #[OA\Response(
        response: 200,
        description: 'Return maps'
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function getMaps()
    {
        // --- On recupere les infos de la base de données
        $maps = $this->mapsRepository->findAllByOrder();

        $json = [];
        $json['maps'] = [];

        foreach ($maps as $map) {
            $json['maps'][] = array(
                'name'  => $map->getName(),
                'url'   => $map->getUrl(),
                'icon'   => $map->getIconUrl(),
            );
        }

        return new JsonResponse($json);
    }
}
