<?php

namespace App\Entity;

use App\Repository\StationsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StationsRepository::class)]
class Stations
{
    #[ORM\ManyToOne(inversedBy: 'stations')]
    #[ORM\JoinColumn(name: "provider_id", nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $station_id = null;

    #[ORM\Column(length: 255)]
    private ?string $station_name = null;

    #[ORM\Column(length: 255)]
    private ?string $station_lat = null;

    #[ORM\Column(length: 255)]
    private ?string $station_lon = null;

    #[ORM\Column(nullable: true)]
    private ?int $station_capacity = null;

    public function getProviderId(): ?Provider
    {
        return $this->provider_id;
    }

    public function setProviderId(Provider $provider_id): static
    {
        $this->provider_id = $provider_id;

        return $this;
    }

    public function getStationId(): ?string
    {
        return $this->station_id;
    }

    public function setStationId(string $station_id): static
    {
        $this->station_id = $station_id;

        return $this;
    }

    public function getStationName(): ?string
    {
        return $this->station_name;
    }

    public function setStationName(string $station_name): static
    {
        $this->station_name = $station_name;

        return $this;
    }

    public function getStationLat(): ?string
    {
        return $this->station_lat;
    }

    public function setStationLat(string $station_lat): static
    {
        $this->station_lat = $station_lat;

        return $this;
    }

    public function getStationLon(): ?string
    {
        return $this->station_lon;
    }

    public function setStationLon(string $station_lon): static
    {
        $this->station_lon = $station_lon;

        return $this;
    }

    public function getStationCapacity(): ?int
    {
        return $this->station_capacity;
    }

    public function setStationCapacity(?int $station_capacity): static
    {
        $this->station_capacity = $station_capacity;

        return $this;
    }
}