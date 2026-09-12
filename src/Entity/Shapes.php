<?php

namespace App\Entity;

use App\Repository\ShapesRepository;
use CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STAsGeoJSON;
use CrEOF\Spatial\PHP\Types\Geometry\LineString;
use CrEOF\Spatial\PHP\Types\Geometry\MultiLineString;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShapesRepository::class)]
class Shapes
{
    // BIGINT UNSIGNED : cette table est reconstruite en boucle, son compteur
    // AUTO_INCREMENT doit avoir de la marge même si la renumérotation opérée à
    // chaque import (App\Service\DB::copyTable) le maintient au niveau du
    // nombre de lignes. columnDefinition garde le type PHP en int : Doctrine
    // continue d'hydrater et de générer l'id comme un entier.
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(columnDefinition: 'BIGINT UNSIGNED AUTO_INCREMENT NOT NULL')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shapes')]
    #[ORM\JoinColumn(name: "provider_id", nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Column(length: 255)]
    private ?string $shape_id = null;

    #[ORM\Column(type: 'linestring', nullable: true)]
    private ?LineString $geometry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $route_id = null;

    // #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: '0', nullable: true)]
    // private ?string $shape_dist_traveled = null;

    public function getProviderId(): ?Provider
    {
        return $this->provider_id;
    }

    public function setProviderId(Provider $provider_id): static
    {
        $this->provider_id = $provider_id;

        return $this;
    }

    public function getShapeId(): ?string
    {
        return $this->shape_id;
    }

    public function setShapeId(string $shape_id): static
    {
        $this->shape_id = $shape_id;

        return $this;
    }

    public function getGeometry(): ?LineString
    {
        return $this->geometry;
    }

    public function setGeometry(?LineString $geometry): static
    {
        $this->geometry = $geometry;

        return $this;
    }

    public function getLineId(): ?string
    {
        return $this->route_id;
    }

    public function setLineId(?string $route_id): static
    {
        $this->route_id = $route_id;

        return $this;
    }

    /**
     * Get the geometry as GeoJSON array
     */
    public function getGeometryAsGeoJson(): ?array
    {
        if (!$this->geometry) {
            return null;
        }

        // Pour CrEOF, nous devons utiliser getPoints() pour obtenir les coordonnées
        $points = $this->geometry->getPoints();
        $coordinates = [];
        
        foreach ($points as $point) {
            // Convertir en float explicitement pour éviter les problèmes avec le lexer
            $coordinates[] = [(float)$point->getX(), (float)$point->getY()];
        }

        return [
            'type' => 'LineString',
            'coordinates' => $coordinates
        ];
    }

    /**
     * Create LineString from coordinate array
     */
    public function setGeometryFromCoordinates(array $coordinates): static
    {
        if (empty($coordinates)) {
            $this->geometry = null;
            return $this;
        }

        // Convertir les coordonnées en string si elles ne le sont pas déjà
        $stringCoordinates = [];
        foreach ($coordinates as $coord) {
            if (is_array($coord) && count($coord) >= 2) {
                $stringCoordinates[] = [(string)$coord[0], (string)$coord[1]];
            }
        }

        $this->geometry = new LineString($stringCoordinates);
        return $this;
    }

    // public function getShapeDistTraveled(): ?string
    // {
    //     return $this->shape_dist_traveled;
    // }

    // public function setShapeDistTraveled(?string $shape_dist_traveled): static
    // {
    //     $this->shape_dist_traveled = $shape_dist_traveled;

    //     return $this;
    // }
}