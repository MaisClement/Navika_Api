<?php

namespace App\Entity;

use App\Repository\TripsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TripsRepository::class)]
#[ORM\Index(name: "trips_trip_short_name", fields: ["trip_short_name"])]

class Trips
{
    #[ORM\ManyToOne(inversedBy: 'trips')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\ManyToOne(inversedBy: 'trips')]
    #[ORM\JoinColumn(name: "route_id", referencedColumnName: "route_id", nullable: true, onDelete: "CASCADE")]
    private ?Routes $route_id = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $trip_id = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $service_id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $trip_headsign = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $trip_short_name = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $direction_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $block_id = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1', '2')")]
    private ?string $wheelchair_accessible = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1', '2')")]
    private ?string $bikes_allowed = null;

    #[ORM\ManyToOne(inversedBy: 'trips')]
    #[ORM\JoinColumn(name: "shape_id", referencedColumnName: "shape_id", nullable: true, onDelete: "CASCADE")]
    private ?Shapes $shape_id = null;

    #[ORM\OneToMany(mappedBy: 'trip_id', targetEntity: Frequencies::class)]
    private Collection $frequencies;

    #[ORM\OneToMany(mappedBy: 'trip_id', targetEntity: StopTimes::class)]
    private Collection $stopTimes;

    public function __construct()
    {
        $this->frequencies = new ArrayCollection();
        $this->stopTimes = new ArrayCollection();
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

    public function getRouteId(): ?string
    {
        return $this->route_id;
    }

    public function setRouteId(string $route_id): static
    {
        $this->route_id = $route_id;

        return $this;
    }

    public function getServiceId(): ?string
    {
        return $this->service_id;
    }

    public function setServiceId(string $service_id): static
    {
        $this->service_id = $service_id;

        return $this;
    }

    public function getTripId(): ?string
    {
        return $this->trip_id;
    }

    public function setTripId(string $trip_id): static
    {
        $this->trip_id = $trip_id;

        return $this;
    }

    public function getTripHeadsign(): ?string
    {
        return $this->trip_headsign;
    }

    public function setTripHeadsign(?string $trip_headsign): static
    {
        $this->trip_headsign = $trip_headsign;

        return $this;
    }

    public function getTripShortName(): ?string
    {
        return $this->trip_short_name;
    }

    public function setTripShortName(?string $trip_short_name): static
    {
        $this->trip_short_name = $trip_short_name;

        return $this;
    }

    public function getDirectionId(): ?string
    {
        return $this->direction_id;
    }

    public function setDirectionId(string $direction_id): static
    {
        $this->direction_id = $direction_id;

        return $this;
    }

    public function getBlockId(): ?string
    {
        return $this->block_id;
    }

    public function setBlockId(?string $block_id): static
    {
        $this->block_id = $block_id;

        return $this;
    }

    public function getShapeId(): ?string
    {
        return $this->shape_id;
    }

    public function setShapeId(?string $shape_id): static
    {
        $this->shape_id = $shape_id;

        return $this;
    }

    public function getWheelchairAccessible(): ?string
    {
        return $this->wheelchair_accessible;
    }

    public function setWheelchairAccessible(string $wheelchair_accessible): static
    {
        $this->wheelchair_accessible = $wheelchair_accessible;

        return $this;
    }

    public function getBikesAllowed(): ?string
    {
        return $this->bikes_allowed;
    }

    public function setBikesAllowed(string $bikes_allowed): static
    {
        $this->bikes_allowed = $bikes_allowed;

        return $this;
    }

    /**
     * @return Collection<int, Frequencies>
     */
    public function getFrequencies(): Collection
    {
        return $this->frequencies;
    }

    public function addFrequency(Frequencies $frequency): static
    {
        if (!$this->frequencies->contains($frequency)) {
            $this->frequencies->add($frequency);
            $frequency->setTripId($this);
        }

        return $this;
    }

    public function removeFrequency(Frequencies $frequency): static
    {
        // set the owning side to null (unless already changed)
        if ($this->frequencies->removeElement($frequency) && $frequency->getTripId() === $this) {
            $frequency->setTripId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, StopTimes>
     */
    public function getStopTimes(): Collection
    {
        return $this->stopTimes;
    }

    public function addStopTime(StopTimes $stopTime): static
    {
        if (!$this->stopTimes->contains($stopTime)) {
            $this->stopTimes->add($stopTime);
            $stopTime->setTripId($this);
        }

        return $this;
    }

    public function removeStopTime(StopTimes $stopTime): static
    {
        // set the owning side to null (unless already changed)
        if ($this->stopTimes->removeElement($stopTime) && $stopTime->getTripId() === $this) {
            $stopTime->setTripId(null);
        }

        return $this;
    }
}
