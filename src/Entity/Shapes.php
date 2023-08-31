<?php

namespace App\Entity;

use App\Repository\ShapesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShapesRepository::class)]
class Shapes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shapes')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Column(length: 255)]
    private ?string $shape_id = null;

    #[ORM\Column(length: 255)]
    private ?string $shape_pt_lat = null;

    #[ORM\Column(length: 255)]
    private ?string $shape_pt_lon = null;

    #[ORM\Column]
    private ?int $shape_pt_sequence = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: '0', nullable: true)]
    private ?string $shape_dist_traveled = null;

    public function __construct()
    {
        $this->trips = new ArrayCollection();
    }

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

    public function getShapePtLat(): array
    {
        return $this->shape_pt_lat;
    }

    public function setShapePtLat(array $shape_pt_lat): static
    {
        $this->shape_pt_lat = $shape_pt_lat;

        return $this;
    }

    public function getShapePtLon(): ?string
    {
        return $this->shape_pt_lon;
    }

    public function setShapePtLon(string $shape_pt_lon): static
    {
        $this->shape_pt_lon = $shape_pt_lon;

        return $this;
    }

    public function getShapePtSequence(): ?int
    {
        return $this->shape_pt_sequence;
    }

    public function setShapePtSequence(int $shape_pt_sequence): static
    {
        $this->shape_pt_sequence = $shape_pt_sequence;

        return $this;
    }

    public function getShapeDistTraveled(): ?string
    {
        return $this->shape_dist_traveled;
    }

    public function setShapeDistTraveled(?string $shape_dist_traveled): static
    {
        $this->shape_dist_traveled = $shape_dist_traveled;

        return $this;
    }

    /**
     * @return Collection<int, Trips>
     */
    public function getTrips(): Collection
    {
        return $this->trips;
    }

    public function addTrip(Trips $trip): static
    {
        if (!$this->trips->contains($trip)) {
            $this->trips->add($trip);
            $trip->setShapeId($this);
        }

        return $this;
    }

    public function removeTrip(Trips $trip): static
    {
        // set the owning side to null (unless already changed)
        if ($this->trips->removeElement($trip) && $trip->getShapeId() === $this) {
            $trip->setShapeId(null);
        }

        return $this;
    }

    public function setTrips(?Trips $trips): static
    {
        $this->trips = $trips;

        return $this;
    }
}
