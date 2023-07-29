<?php

namespace App\Entity;

use App\Repository\AgencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgencyRepository::class)]
class Agency
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $agency_id = null;

    #[ORM\ManyToOne(inversedBy: 'agencies')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Column(length: 255)]
    private ?string $agency_name = null;

    #[ORM\Column(length: 255)]
    private ?string $agency_url = null;

    #[ORM\Column(length: 255)]
    private ?string $agency_timezone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $agency_lang = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $agency_phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $agency_fare_url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $agency_email = null;

    #[ORM\OneToMany(mappedBy: 'agency_id', targetEntity: Routes::class)]
    private Collection $routes;

    #[ORM\OneToMany(mappedBy: 'agency_id', targetEntity: FareAttributes::class)]
    private Collection $fareAttributes;

    public function __construct()
    {
        $this->routes = new ArrayCollection();
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

    public function getAgencyId(): ?string
    {
        return $this->agency_id;
    }

    public function setAgencyId(string $agency_id): static
    {
        $this->agency_id = $agency_id;

        return $this;
    }

    public function getAgencyName(): ?string
    {
        return $this->agency_name;
    }

    public function setAgencyName(string $agency_name): static
    {
        $this->agency_name = $agency_name;

        return $this;
    }

    public function getAgencyUrl(): ?string
    {
        return $this->agency_url;
    }

    public function setAgencyUrl(string $agency_url): static
    {
        $this->agency_url = $agency_url;

        return $this;
    }

    public function getAgencyTimezone(): ?string
    {
        return $this->agency_timezone;
    }

    public function setAgencyTimezone(string $agency_timezone): static
    {
        $this->agency_timezone = $agency_timezone;

        return $this;
    }

    public function getAgencyLang(): ?string
    {
        return $this->agency_lang;
    }

    public function setAgencyLang(?string $agency_lang): static
    {
        $this->agency_lang = $agency_lang;

        return $this;
    }

    public function getAgencyPhone(): ?string
    {
        return $this->agency_phone;
    }

    public function setAgencyPhone(?string $agency_phone): static
    {
        $this->agency_phone = $agency_phone;

        return $this;
    }

    public function getAgencyFareUrl(): ?string
    {
        return $this->agency_fare_url;
    }

    public function setAgencyFareUrl(?string $agency_fare_url): static
    {
        $this->agency_fare_url = $agency_fare_url;

        return $this;
    }

    public function getAgencyEmail(): ?string
    {
        return $this->agency_email;
    }

    public function setAgencyEmail(?string $agency_email): static
    {
        $this->agency_email = $agency_email;

        return $this;
    }

    /**
     * @return Collection<int, Routes>
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    public function addRoute(Routes $route): static
    {
        if (!$this->routes->contains($route)) {
            $this->routes->add($route);
            $route->setAgencyId($this);
        }

        return $this;
    }

    public function removeRoute(Routes $route): static
    {
        if ($this->routes->removeElement($route)) {
            // set the owning side to null (unless already changed)
            if ($route->getAgencyId() === $this) {
                $route->setAgencyId(null);
            }
        }

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
            $fareAttribute->setAgencyId($this);
        }

        return $this;
    }

    public function removeFareAttribute(FareAttributes $fareAttribute): static
    {
        if ($this->fareAttributes->removeElement($fareAttribute)) {
            // set the owning side to null (unless already changed)
            if ($fareAttribute->getAgencyId() === $this) {
                $fareAttribute->setAgencyId(null);
            }
        }

        return $this;
    }
}
