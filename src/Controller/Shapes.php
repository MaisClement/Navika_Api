<?php

namespace App\Controller;

use App\Repository\ShapesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use OpenApi\Attributes as OA;

class Shapes extends AbstractController
{
    private ShapesRepository $shapesRepository;
    private ParameterBagInterface $params;

    public function __construct(ShapesRepository $shapesRepository, ParameterBagInterface $params)
    {
        $this->shapesRepository = $shapesRepository;
        $this->params = $params;
    }

    /**
     * Get the GeoJSON for a specific shape
     */
    #[Route('/shapes/{shapeId}', name: 'shape_geojson', methods: ['GET'])]
    #[OA\Tag(name: 'Shapes')]
     #[OA\Response(
        response: 200,
        description: 'OK'
    )]
    public function getShapeGeoJson(
        string $shapeId,
        Request $request
    ): JsonResponse {
        $geoJson = $this->shapesRepository->getLineAsGeoJsonSql($shapeId);
        
        if (!$geoJson) {
            return $this->json([
                'error' => 'Shape not found or no geometry available',
                'shape_id' => $shapeId,
            ], 404);
        }
        
        return $this->json($geoJson);
    }

    /**
     * Get main shapes (GeoJSON FeatureCollection)
     *
     * By default returns shapes for configured main lines (parameter "lines").
     * You can override by passing query param lines[]=<line_id>.
     */
    #[Route('/shapes', name: 'shapes_geojson', methods: ['GET'])]
    #[OA\Tag(name: 'Shapes')]
     #[OA\Response(
        response: 200,
        description: 'OK'
    )]
    public function getMainShapes(Request $request): JsonResponse
    {
        $lines = $request->get('lines');
        if ($lines === null) {
            $lines = $this->params->get('lines');
        }

        // Ensure array
        if (is_string($lines)) {
            $lines = [$lines];
        }

        if (!is_array($lines) || empty($lines)) {
            return $this->json([
                'error' => 'No lines provided or configured',
            ], 400);
        }

        $geoJson = $this->shapesRepository->getLinesAsGeoJsonSql($lines);

        if (!$geoJson || empty($geoJson['features'])) {
            return $this->json([
                'error' => 'No shapes found for given lines',
                'lines' => $lines,
            ], 404);
        }

        return $this->json($geoJson);
    }
}
