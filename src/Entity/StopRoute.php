<?php

namespace App\Entity;

use App\Repository\StopRouteRepository;
use Doctrine\DBAL\Types\Types;
use App\Controller\Functions;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StopRouteRepository::class)]
#[ORM\Index(name: "stop_route_stop_id", fields: ["stop_id"])]
#[ORM\Index(name: "stop_route_query", fields: ["stop_query_name"])]
#[ORM\Index(name: "stop_route_query_town", fields: ["town_query_name"])]
#[ORM\Index(name: "stop_route_route_id", fields: ["route_id"])]

class StopRoute
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $route_key = null;

    #[ORM\ManyToOne(inversedBy: 'stopRoutes')]
    #[ORM\JoinColumn(name: "stop_id", referencedColumnName: "stop_id", nullable: false, onDelete: "CASCADE")]
    private ?Stops $stop_id = null;

    #[ORM\ManyToOne(inversedBy: 'stopRoutes')]
    #[ORM\JoinColumn(name: "route_id", referencedColumnName: "route_id", nullable: false, onDelete: "CASCADE")]
    private ?Routes $route_id = null;

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

    #[ORM\Column(type: Types::TEXT)]
    private ?string $stop_name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $stop_query_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stop_lat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stop_lon = null;

    #[ORM\ManyToOne(inversedBy: 'stopRoutes')]
    #[ORM\JoinColumn(name: "town_id", referencedColumnName: "town_id", nullable: true, onDelete: "CASCADE")]
    private ?Town $town_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $town_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $town_query_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zip_code = null;

    public function getRouteKey(): ?string
    {
        return $this->route_key;
    }

    public function setRouteKey(string $route_key): static
    {
        $this->route_key = $route_key;

        return $this;
    }

    public function getRouteId(): ?Routes
    {
        return $this->route_id;
    }

    public function setRouteId(Routes $route_id): static
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

    public function getTransportMode(): ?string
    {
        return Functions::getTransportMode($this->route_type);
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

    public function getStopId(): ?Stops
    {
        return $this->stop_id;
    }

    public function setStopId(Stops $stop_id): static
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

    public function setTown(Town $town): static
    {
        $this->town_id = $town;
        $this->town_name = $town->getTownName();
        $this->town_query_name = $town->getTownName();
        $this->zip_code = $town->getZipCode();

        return $this;
    }

    public function getTownId(): ?Town
    {
        return $this->town_id;
    }

    public function setTownId(?Town $town_id): static
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

    public function getStop($lat = null, $lon = null): ?array
    {
        $stop = array(
            'id'        =>              $this->stop_id->getStopId(),
            'name'      =>  (string)    $this->stop_id->getStopName(),
            'type'      =>  (string)    $this->stop_id->getParentStation() == null ? 'stop_area' : 'stop_point',
            'town'      =>  (string)    $this->town_name,
            'zip_code'  =>  (string)    $this->zip_code,
            'coord'     => array(
                'lat'       =>      (float) $this->stop_lat,
                'lon'       =>      (float) $this->stop_lon,
            ),
        );

        if ($lat != null && $lon != null) {
            $stop['distance'] = Functions::calculateDistance($stop->stop_lat, $stop->stop_lon, $lat, $lon);
        } else {
            $stop['distance'] =  0;
        }

        return $stop;
    }
}
