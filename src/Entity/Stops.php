<?php

namespace App\Entity;

use App\Repository\StopsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StopsRepository::class)]
class Stops
{
    #[ORM\ManyToOne(inversedBy: 'stops')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $stop_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stop_code = null;

    #[ORM\Column(length: 255)]
    private ?string $stop_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stop_desc = null;

    #[ORM\Column(length: 255)]
    private ?string $stop_lat = null;

    #[ORM\Column(length: 255)]
    private ?string $stop_lon = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zone_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stop_url = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1', '2', '3', '4')")]
    private ?string $location_type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vehicle_type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stop_timezone = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1', '2')")]
    private ?string $wheelchair_boarding = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $platform_code = null;    

    #[ORM\ManyToOne(inversedBy: 'stops')]
    #[ORM\JoinColumn(name: "level_id", referencedColumnName: "level_id", nullable: true, onDelete: "CASCADE")]
    private ?Levels $level_id = null;

    #[ORM\OneToMany(mappedBy: 'stop_id', targetEntity: StopTimes::class)]
    private Collection $stopTimes;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parent_station = null;

    #[ORM\OneToMany(mappedBy: 'from_stop_id', targetEntity: Pathways::class)]
    private Collection $pathways;

    #[ORM\OneToMany(mappedBy: 'from_stop_id', targetEntity: Transfers::class)]
    private Collection $transfers;

    #[ORM\OneToMany(mappedBy: 'origin_id', targetEntity: FareRules::class)]
    private Collection $fareRules;

    public function __construct()
    {
        $this->stopTimes = new ArrayCollection();
        $this->stops = new ArrayCollection();
        $this->pathways = new ArrayCollection();
        $this->transfers = new ArrayCollection();
        $this->fareRules = new ArrayCollection();
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

    public function getStopId(): ?string
    {
        return $this->stop_id;
    }

    public function setStopId(string $stop_id): static
    {
        $this->stop_id = $stop_id;

        return $this;
    }

    public function getStopCode(): ?string
    {
        return $this->stop_code;
    }

    public function setStopCode(string $stop_code): static
    {
        $this->stop_code = $stop_code;

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

    public function getStopDesc(): ?string
    {
        return $this->stop_desc;
    }

    public function setStopDesc(?string $stop_desc): static
    {
        $this->stop_desc = $stop_desc;

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

    public function getZoneId(): ?string
    {
        return $this->zone_id;
    }

    public function setZoneId(string $zone_id): static
    {
        $this->zone_id = $zone_id;

        return $this;
    }

    public function getStopUrl(): ?string
    {
        return $this->stop_url;
    }

    public function setStopUrl(string $stop_url): static
    {
        $this->stop_url = $stop_url;

        return $this;
    }

    public function getLocationType(): ?string
    {
        return $this->location_type;
    }

    public function setLocationType(string $location_type): static
    {
        $this->location_type = $location_type;

        return $this;
    }

    public function getStops(): ?self
    {
        return $this->Stops;
    }

    public function setStops(?self $Stops): static
    {
        $this->Stops = $Stops;

        return $this;
    }

    public function getVehicleType(): ?string
    {
        return $this->vehicle_type;
    }

    public function setVehicleType(?string $vehicle_type): static
    {
        $this->vehicle_type = $vehicle_type;

        return $this;
    }

    public function getStopTimezone(): ?string
    {
        return $this->stop_timezone;
    }

    public function setStopTimezone(?string $stop_timezone): static
    {
        $this->stop_timezone = $stop_timezone;

        return $this;
    }

    public function getWheelchairBoarding(): ?string
    {
        return $this->wheelchair_boarding;
    }

    public function setWheelchairBoarding(string $wheelchair_boarding): static
    {
        $this->wheelchair_boarding = $wheelchair_boarding;

        return $this;
    }

    public function getLevelId(): ?int
    {
        return $this->level_id;
    }

    public function setLevelId(int $level_id): static
    {
        $this->level_id = $level_id;

        return $this;
    }

    public function getPlatformCode(): ?string
    {
        return $this->platform_code;
    }

    public function setPlatformCode(?string $platform_code): static
    {
        $this->platform_code = $platform_code;

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
            $stopTime->setStopId($this);
        }

        return $this;
    }

    public function removeStopTime(StopTimes $stopTime): static
    {
        if ($this->stopTimes->removeElement($stopTime)) {
            // set the owning side to null (unless already changed)
            if ($stopTime->getStopId() === $this) {
                $stopTime->setStopId(null);
            }
        }

        return $this;
    }

    public function getParentStation(): ?string
    {
        return $this->parent_station;
    }

    public function setParentStation(?string $parent_station): static
    {
        $this->parent_station = $parent_station;

        return $this;
    }

    /**
     * @return Collection<int, Pathways>
     */
    public function getPathways(): Collection
    {
        return $this->pathways;
    }

    public function addPathway(Pathways $pathway): static
    {
        if (!$this->pathways->contains($pathway)) {
            $this->pathways->add($pathway);
            $pathway->setFromStopId($this);
        }

        return $this;
    }

    public function removePathway(Pathways $pathway): static
    {
        if ($this->pathways->removeElement($pathway)) {
            // set the owning side to null (unless already changed)
            if ($pathway->getFromStopId() === $this) {
                $pathway->setFromStopId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transfers>
     */
    public function getTransfers(): Collection
    {
        return $this->transfers;
    }

    public function addTransfer(Transfers $transfer): static
    {
        if (!$this->transfers->contains($transfer)) {
            $this->transfers->add($transfer);
            $transfer->setFromStopId($this);
        }

        return $this;
    }

    public function removeTransfer(Transfers $transfer): static
    {
        if ($this->transfers->removeElement($transfer)) {
            // set the owning side to null (unless already changed)
            if ($transfer->getFromStopId() === $this) {
                $transfer->setFromStopId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FareRules>
     */
    public function getFareRules(): Collection
    {
        return $this->fareRules;
    }

    public function addFareRule(FareRules $fareRule): static
    {
        if (!$this->fareRules->contains($fareRule)) {
            $this->fareRules->add($fareRule);
            $fareRule->setOriginId($this);
        }

        return $this;
    }

    public function removeFareRule(FareRules $fareRule): static
    {
        if ($this->fareRules->removeElement($fareRule)) {
            // set the owning side to null (unless already changed)
            if ($fareRule->getOriginId() === $this) {
                $fareRule->setOriginId(null);
            }
        }

        return $this;
    }
}
