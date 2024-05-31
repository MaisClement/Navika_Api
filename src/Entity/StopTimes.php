<?php

namespace App\Entity;

use App\Repository\StopTimesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StopTimesRepository::class)]
#[ORM\Index(name: "stop_times_trip_id", fields: ["trip_id"])]
#[ORM\Index(name: "stop_times_stop_id", fields: ["stop_id"])]

class StopTimes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stopTimes')]
    #[ORM\JoinColumn(name: "provider_id", nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\ManyToOne(inversedBy: 'stopTimes')]
    #[ORM\JoinColumn(name: "trip_id", referencedColumnName: "trip_id", nullable: true, onDelete: "CASCADE")]
    private ?Trips $trip_id = null;

    #[ORM\ManyToOne(inversedBy: 'stopTimes')]
    #[ORM\JoinColumn(name: "stop_id", referencedColumnName: "stop_id", nullable: true, onDelete: "CASCADE")]
    private ?Stops $stop_id = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $arrival_time = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $departure_time = null;

    #[ORM\Column]
    private ?int $stop_sequence = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stop_headsign = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $local_zone_id = null;

    #[ORM\Column(columnDefinition: 'ENUM("0", "1", "2", "3")')]
    private ?int $pickup_type = null;

    #[ORM\Column(columnDefinition: 'ENUM("0", "1", "2", "3")')]
    private ?int $drop_off_type = null;

    #[ORM\Column(columnDefinition: 'ENUM("0", "1", "2", "3")')]
    private ?int $continuous_pickup = null;

    #[ORM\Column(columnDefinition: 'ENUM("0", "1", "2", "3")')]
    private ?int $continuous_drop_off = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: '0', nullable: true)]
    private ?string $shape_dist_traveled = null;

    #[ORM\Column(columnDefinition: 'ENUM("0", "1")')]
    private ?int $timepoint = null;

    public function getProviderId(): ?Provider
    {
        return $this->provider_id;
    }

    public function setProviderId(Provider $provider_id): static
    {
        $this->provider_id = $provider_id;

        return $this;
    }

    public function getArrivalTime(): ?\DateTimeInterface
    {
        return $this->arrival_time;
    }

    public function setArrivalTime(?\DateTimeInterface $arrival_time): static
    {
        $this->arrival_time = $arrival_time;

        return $this;
    }

    public function getDepartureTime(): ?\DateTimeInterface
    {
        return $this->departure_time;
    }

    public function setDepartureTime(?\DateTimeInterface $departure_time): static
    {
        $this->departure_time = $departure_time;

        return $this;
    }

    public function getStopSequence(): ?int
    {
        return $this->stop_sequence;
    }

    public function setStopSequence(int $stop_sequence): static
    {
        $this->stop_sequence = $stop_sequence;

        return $this;
    }

    public function getStopHeadsign(): ?string
    {
        return $this->stop_headsign;
    }

    public function setStopHeadsign(?string $stop_headsign): static
    {
        $this->stop_headsign = $stop_headsign;

        return $this;
    }

    public function getLocalZoneId(): ?string
    {
        return $this->local_zone_id;
    }

    public function setLocalZoneId(?string $local_zone_id): static
    {
        $this->local_zone_id = $local_zone_id;

        return $this;
    }

    public function getPickupType(): ?int
    {
        return $this->pickup_type;
    }

    public function setPickupType(int $pickup_type): static
    {
        $this->pickup_type = $pickup_type;

        return $this;
    }

    public function getDropOffType(): ?int
    {
        return $this->drop_off_type;
    }

    public function setDropOffType(int $drop_off_type): static
    {
        $this->drop_off_type = $drop_off_type;

        return $this;
    }

    public function getContinuousPickup(): ?int
    {
        return $this->continuous_pickup;
    }

    public function setContinuousPickup(int $continuous_pickup): static
    {
        $this->continuous_pickup = $continuous_pickup;

        return $this;
    }

    public function getContinuousDropOff(): ?int
    {
        return $this->continuous_drop_off;
    }

    public function setContinuousDropOff(int $continuous_drop_off): static
    {
        $this->continuous_drop_off = $continuous_drop_off;

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

    public function getTimepoint(): ?int
    {
        return $this->timepoint;
    }

    public function setTimepoint(?int $timepoint): static
    {
        $this->timepoint = $timepoint;

        return $this;
    }

    public function getTripId(): ?Trips
    {
        return $this->trip_id;
    }

    public function setTripId(?Trips $trip_id): static
    {
        $this->trip_id = $trip_id;

        return $this;
    }

    public function getStopId(): ?Stops
    {
        return $this->stop_id;
    }

    public function setStopId(?Stops $stop_id): static
    {
        $this->stop_id = $stop_id;

        return $this;
    }
}