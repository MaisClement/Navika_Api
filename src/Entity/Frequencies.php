<?php

namespace App\Entity;

use App\Repository\FrequenciesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FrequenciesRepository::class)]
class Frequencies
{
    #[ORM\ManyToOne(inversedBy: 'frequencies')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'frequencies')]
    #[ORM\JoinColumn(name: "trip_id", referencedColumnName: "trip_id", nullable: true, onDelete: "CASCADE")]
    private ?Trips $trip_id = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $start_time = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $end_time = null;

    #[ORM\Column]
    private ?int $headway_secs = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $exact_times = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTripId(): ?string
    {
        return $this->trip_id;
    }

    public function setTripId(string $trip_id): static
    {
        $this->trip_id = $trip_id;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->start_time;
    }

    public function setStartTime(\DateTimeInterface $start_time): static
    {
        $this->start_time = $start_time;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->end_time;
    }

    public function setEndTime(\DateTimeInterface $end_time): static
    {
        $this->end_time = $end_time;

        return $this;
    }

    public function getHeadwaySecs(): ?int
    {
        return $this->headway_secs;
    }

    public function setHeadwaySecs(int $headway_secs): static
    {
        $this->headway_secs = $headway_secs;

        return $this;
    }

    public function getExactTimes(): ?string
    {
        return $this->exact_times;
    }

    public function setExactTimes(string $exact_times): static
    {
        $this->exact_times = $exact_times;

        return $this;
    }
}
