<?php

namespace App\Entity;

use App\Repository\RouteSubRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RouteSubRepository::class)]
class RouteSub
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'routeSubs')]
    private ?Subscribers $subscriber_id = null;

    #[ORM\ManyToOne(inversedBy: 'routeSubs')]
    #[ORM\JoinColumn(name: "route_id", referencedColumnName: "route_id", nullable: true, onDelete: "CASCADE")]
    private ?Routes $route_id = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $monday = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $tuesday = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $wednesday = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $thursday = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $friday = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $saturday = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $sunday = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $start_time = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $end_time = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubscriberId(): ?Subscribers
    {
        return $this->subscriber_id;
    }

    public function setSubscriberId(?Subscribers $subscriber_id): static
    {
        $this->subscriber_id = $subscriber_id;

        return $this;
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

    public function getMonday(): ?string
    {
        return $this->monday;
    }

    public function setMonday(string $monday): static
    {
        $this->monday = $monday;

        return $this;
    }

    public function getTuesday(): ?string
    {
        return $this->tuesday;
    }

    public function setTuesday(string $tuesday): static
    {
        $this->tuesday = $tuesday;

        return $this;
    }

    public function getWednesday(): ?string
    {
        return $this->wednesday;
    }

    public function setWednesday(string $wednesday): static
    {
        $this->wednesday = $wednesday;

        return $this;
    }

    public function getThursday(): ?string
    {
        return $this->thursday;
    }

    public function setThursday(string $thursday): static
    {
        $this->thursday = $thursday;

        return $this;
    }

    public function getFriday(): ?string
    {
        return $this->friday;
    }

    public function setFriday(string $friday): static
    {
        $this->friday = $friday;

        return $this;
    }

    public function getSaturday(): ?string
    {
        return $this->saturday;
    }

    public function setSaturday(string $saturday): static
    {
        $this->saturday = $saturday;

        return $this;
    }

    public function getSunday(): ?string
    {
        return $this->sunday;
    }

    public function setSunday(string $sunday): static
    {
        $this->sunday = $sunday;

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
}
