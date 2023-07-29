<?php

namespace App\Entity;

use App\Repository\TempStopRouteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TempStopRouteRepository::class)]
class TempStopRoute
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $route_key = null;

    #[ORM\Column(length: 255)]
    private ?string $route_id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $route_short_name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $route_long_name = null;

    #[ORM\Column(length: 255)]
    private ?string $route_type = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $route_color = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $route_text_color = null;

    #[ORM\Column(length: 255)]
    private ?string $stop_id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $stop_name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $stop_query_name = null;

    #[ORM\Column(length: 255)]
    private ?string $stop_lat = null;

    #[ORM\Column(length: 255)]
    private ?string $stop_lon = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $town_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $town_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $town_query_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zip_code = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRouteKey(): ?string
    {
        return $this->route_key;
    }

    public function setRouteKey(string $route_key): static
    {
        $this->route_key = $route_key;

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

    public function getRouteShortName(): ?string
    {
        return $this->route_short_name;
    }

    public function setRouteShortName(string $route_short_name): static
    {
        $this->route_short_name = $route_short_name;

        return $this;
    }

    public function getRouteLongName(): ?string
    {
        return $this->route_long_name;
    }

    public function setRouteLongName(?string $route_long_name): static
    {
        $this->route_long_name = $route_long_name;

        return $this;
    }

    public function getRouteType(): ?string
    {
        return $this->route_type;
    }

    public function setRouteType(string $route_type): static
    {
        $this->route_type = $route_type;

        return $this;
    }

    public function getRouteColor(): ?string
    {
        return $this->route_color;
    }

    public function setRouteColor(?string $route_color): static
    {
        $this->route_color = $route_color;

        return $this;
    }

    public function getRouteTextColor(): ?string
    {
        return $this->route_text_color;
    }

    public function setRouteTextColor(?string $route_text_color): static
    {
        $this->route_text_color = $route_text_color;

        return $this;
    }

    public function getStopId(): ?string
    {
        return $this->stop_id;
    }

    public function setStopId(string $stop_id): static
    {
        $this->stop_id = $stop_id;

        return $this;
    }

    public function getStopName(): ?string
    {
        return $this->stop_name;
    }

    public function setStopName(string $stop_name): static
    {
        $this->stop_name = $stop_name;

        return $this;
    }

    public function getStopQueryName(): ?string
    {
        return $this->stop_query_name;
    }

    public function setStopQueryName(string $stop_query_name): static
    {
        $this->stop_query_name = $stop_query_name;

        return $this;
    }

    public function getStopLat(): ?string
    {
        return $this->stop_lat;
    }

    public function setStopLat(string $stop_lat): static
    {
        $this->stop_lat = $stop_lat;

        return $this;
    }

    public function getStopLon(): ?string
    {
        return $this->stop_lon;
    }

    public function setStopLon(string $stop_lon): static
    {
        $this->stop_lon = $stop_lon;

        return $this;
    }

    public function getTownId(): ?string
    {
        return $this->town_id;
    }

    public function setTownId(?string $town_id): static
    {
        $this->town_id = $town_id;

        return $this;
    }

    public function getTownName(): ?string
    {
        return $this->town_name;
    }

    public function setTownName(?string $town_name): static
    {
        $this->town_name = $town_name;

        return $this;
    }

    public function getTownQueryName(): ?string
    {
        return $this->town_query_name;
    }

    public function setTownQueryName(?string $town_query_name): static
    {
        $this->town_query_name = $town_query_name;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zip_code;
    }

    public function setZipCode(?string $zip_code): static
    {
        $this->zip_code = $zip_code;

        return $this;
    }
}
