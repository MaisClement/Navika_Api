<?php

namespace App\Entity;

use App\Repository\FareRulesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FareRulesRepository::class)]
class FareRules
{
    #[ORM\ManyToOne(inversedBy: 'fareRules')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $fare_id = null;

    #[ORM\ManyToOne(inversedBy: 'fareRules')]
    #[ORM\JoinColumn(name: "route_id", referencedColumnName: "route_id", onDelete: "CASCADE")]
    private ?Routes $route_id = null;

    #[ORM\ManyToOne(inversedBy: 'fareRules')]
    #[ORM\JoinColumn(name: "origin_id", referencedColumnName: "stop_id", onDelete: "CASCADE")]
    private ?Stops $origin_id = null;

    #[ORM\ManyToOne(inversedBy: 'fareRules')]
    #[ORM\JoinColumn(name: "destination_id", referencedColumnName: "stop_id", onDelete: "CASCADE")]
    private ?Stops $destination_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contains_id = null;

    #[ORM\OneToMany(mappedBy: 'fare_id', targetEntity: FareAttributes::class)]
    private Collection $fareAttributes;

    public function __construct()
    {
        $this->fareAttributes = new ArrayCollection();
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

    public function getFareId(): ?string
    {
        return $this->fare_id;
    }

    public function setFareId(string $fare_id): static
    {
        $this->fare_id = $fare_id;

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

    public function getOriginId(): ?string
    {
        return $this->origin_id;
    }

    public function setOriginId(?string $origin_id): static
    {
        $this->origin_id = $origin_id;

        return $this;
    }

    public function getDestinationId(): ?string
    {
        return $this->destination_id;
    }

    public function setDestinationId(?string $destination_id): static
    {
        $this->destination_id = $destination_id;

        return $this;
    }

    public function getContainsId(): ?string
    {
        return $this->contains_id;
    }

    public function setContainsId(?string $contains_id): static
    {
        $this->contains_id = $contains_id;

        return $this;
    }

    /**
     * @return Collection<int, FareAttributes>
     */
    public function getFareAttributes(): Collection
    {
        return $this->fareAttributes;
    }

    public function addFareAttribute(FareAttributes $fareAttribute): static
    {
        if (!$this->fareAttributes->contains($fareAttribute)) {
            $this->fareAttributes->add($fareAttribute);
            $fareAttribute->setFareId($this);
        }

        return $this;
    }

    public function removeFareAttribute(FareAttributes $fareAttribute): static
    {
        // set the owning side to null (unless already changed)
        if ($this->fareAttributes->removeElement($fareAttribute) && $fareAttribute->getFareId() === $this) {
            $fareAttribute->setFareId(null);
        }

        return $this;
    }
}
