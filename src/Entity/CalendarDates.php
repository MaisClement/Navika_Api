<?php

namespace App\Entity;

use App\Repository\CalendarDatesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalendarDatesRepository::class)]
#[ORM\Index(name: "calendar_dates_service_id", fields: ["service_id"])]

class CalendarDates
{
    // BIGINT UNSIGNED : cette table est reconstruite en boucle, son compteur
    // AUTO_INCREMENT doit avoir de la marge même si la renumérotation opérée à
    // chaque import (App\Service\DB::copyTable) le maintient au niveau du
    // nombre de lignes. columnDefinition garde le type PHP en int : Doctrine
    // continue d'hydrater et de générer l'id comme un entier.
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(columnDefinition: 'BIGINT UNSIGNED AUTO_INCREMENT NOT NULL')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'calendarDates')]
    #[ORM\JoinColumn(name: "provider_id", nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Column(length: 255)]
    private ?string $service_id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(columnDefinition: 'ENUM("0", "1", "2")')]
    private ?int $exception_type = null;

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

    public function getServiceId(): ?string
    {
        return $this->service_id;
    }

    public function setServiceId(string $service_id): static
    {
        $this->service_id = $service_id;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getExceptionType(): ?int
    {
        return $this->exception_type;
    }

    public function setExceptionType(int $exception_type): static
    {
        $this->exception_type = $exception_type;

        return $this;
    }
}