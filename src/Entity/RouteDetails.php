<?php

namespace App\Entity;

use App\Repository\RouteDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RouteDetailsRepository::class)]
class RouteDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'routeDetails')]
    #[ORM\JoinColumn(name: "route_id", referencedColumnName: "route_id", nullable: false)]
    private ?Routes $route_id = null;

    #[ORM\Column(length: 255)]
    private ?string $vehicule_name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $vehicule_img = null;

    #[ORM\Column]
    private ?bool $is_air_conditioned = null;

    #[ORM\Column]
    private ?bool $has_power_sockets = null;

    #[ORM\Column]
    private ?bool $is_bike_accesible = null;

    #[ORM\Column]
    private ?bool $is_wheelchair_accesible = null;

    public function getDetails(): ?array
    {
        return array(
            "vehicule_name"             => $this->vehicule_name,
            "vehicule_img"              => $this->vehicule_img,
            "is_air_conditioned"        => $this->is_air_conditioned ?? false,
            "has_power_sockets"         => $this->has_power_sockets ?? false,
            "is_bike_accesible"         => $this->is_bike_accesible ?? false,
            "is_wheelchair_accesible"   => $this->is_wheelchair_accesible ?? false,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRouteId(): ?Routes
    {
        return $this->route_id;
    }

    public function setRouteId(?Routes $route_id): static
    {
        $this->route_id = $route_id;

        return $this;
    }

    public function getVehiculeName(): ?string
    {
        return $this->vehicule_name;
    }

    public function setVehiculeName(string $vehicule_name): static
    {
        $this->vehicule_name = $vehicule_name;

        return $this;
    }

    public function getVehiculeImg(): ?string
    {
        return $this->vehicule_img;
    }

    public function setVehiculeImg(?string $vehicule_img): static
    {
        $this->vehicule_img = $vehicule_img;

        return $this;
    }

    public function isAirConditioned(): ?bool
    {
        return $this->is_air_conditioned;
    }

    public function setIsAirConditioned(bool $is_air_conditioned): static
    {
        $this->is_air_conditioned = $is_air_conditioned;

        return $this;
    }

    public function hasPowerSockets(): ?bool
    {
        return $this->has_power_sockets;
    }

    public function setHasPowerSockets(bool $has_power_sockets): static
    {
        $this->has_power_sockets = $has_power_sockets;

        return $this;
    }

    public function isBikeAccesible(): ?bool
    {
        return $this->is_bike_accesible;
    }

    public function setIsBikeAccesible(bool $is_bike_accesible): static
    {
        $this->is_bike_accesible = $is_bike_accesible;

        return $this;
    }

    public function isWheelchairAccesible(): ?bool
    {
        return $this->is_wheelchair_accesible;
    }

    public function setIsWheelchairAccesible(bool $is_wheelchair_accesible): static
    {
        $this->is_wheelchair_accesible = $is_wheelchair_accesible;

        return $this;
    }
}
