<?php

namespace App\Entity;

use App\Repository\AttributionsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttributionsRepository::class)]
class Attributions
{
    #[ORM\ManyToOne(inversedBy: 'attributions')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Id]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attribution_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $agency_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $route_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trip_id = null;

    #[ORM\Column(length: 255)]
    private ?string $organization_name = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $is_producer = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $is_operator = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $is_authority = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attribution_url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attribution_email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attribution_phone = null;

    public function getProviderId(): ?Provider 
    {
        return $this->provider_id;
    }

    public function setProviderId(Provider $provider_id): static
    {
        $this->provider_id = $provider_id;

        return $this;
    }

    public function getAttributionId(): ?string
    {
        return $this->attribution_id;
    }

    public function setAttributionId(?string $attribution_id): static
    {
        $this->attribution_id = $attribution_id;

        return $this;
    }

    public function getAgencyId(): ?string
    {
        return $this->agency_id;
    }

    public function setAgencyId(?string $agency_id): static
    {
        $this->agency_id = $agency_id;

        return $this;
    }

    public function getRouteId(): ?string
    {
        return $this->route_id;
    }

    public function setRouteId(?string $route_id): static
    {
        $this->route_id = $route_id;

        return $this;
    }

    public function getTripId(): ?string
    {
        return $this->trip_id;
    }

    public function setTripId(?string $trip_id): static
    {
        $this->trip_id = $trip_id;

        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organization_name;
    }

    public function setOrganizationName(string $organization_name): static
    {
        $this->organization_name = $organization_name;

        return $this;
    }

    public function getIsProducer(): ?string
    {
        return $this->is_producer;
    }

    public function setIsProducer(string $is_producer): static
    {
        $this->is_producer = $is_producer;

        return $this;
    }

    public function getIsOperator(): ?string
    {
        return $this->is_operator;
    }

    public function setIsOperator(string $is_operator): static
    {
        $this->is_operator = $is_operator;

        return $this;
    }

    public function getIsAuthority(): ?string
    {
        return $this->is_authority;
    }

    public function setIsAuthority(string $is_authority): static
    {
        $this->is_authority = $is_authority;

        return $this;
    }

    public function getAttributionUrl(): ?string
    {
        return $this->attribution_url;
    }

    public function setAttributionUrl(?string $attribution_url): static
    {
        $this->attribution_url = $attribution_url;

        return $this;
    }

    public function getAttributionEmail(): ?string
    {
        return $this->attribution_email;
    }

    public function setAttributionEmail(?string $attribution_email): static
    {
        $this->attribution_email = $attribution_email;

        return $this;
    }

    public function getAttributionPhone(): ?string
    {
        return $this->attribution_phone;
    }

    public function setAttributionPhone(?string $attribution_phone): static
    {
        $this->attribution_phone = $attribution_phone;

        return $this;
    }
}
