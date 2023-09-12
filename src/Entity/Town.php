<?php

namespace App\Entity;

use App\Repository\TownRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use CrEOF\Spatial\PHP\Types\Geography\Polygon;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TownRepository::class)]
#[ORM\Index(name: "town_polygon", fields: ["town_polygon"])]

class Town
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $town_id = null;

    #[ORM\Column(length: 255)]
    private ?string $town_name = null;

    #[ORM\Column(type: 'polygon', nullable: true)]
    private $town_polygon = null;

    #[ORM\Column(length: 255)]
    private ?string $zip_code = null;

    #[ORM\OneToMany(mappedBy: 'town_id', targetEntity: StopRoute::class)]
    private Collection $stopRoutes;

    #[ORM\OneToMany(mappedBy: 'town_id', targetEntity: StopTown::class)]
    private Collection $stopTowns;

    public function __construct()
    {
        $this->stopRoutes = new ArrayCollection();
        $this->stopTowns = new ArrayCollection();
    }

    public function getTownId(): ?string
    {
        return $this->town_id;
    }

    public function setTownId(string $town_id): static
    {
        $this->town_id = $town_id;

        return $this;
    }

    public function getTownName(): ?string
    {
        return $this->town_name;
    }

    public function setTownName(string $town_name): static
    {
        $this->town_name = $town_name;

        return $this;
    }

    public function getTownPolygon()
    {
        return $this->town_polygon;
    }

    public function setTownPolygon(Polygon $town_polygon): static
    {
        $this->town_polygon = $town_polygon;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zip_code;
    }

    public function setZipCode(string $zip_code): static
    {
        $this->zip_code = $zip_code;

        return $this;
    }

    /**
     * @return Collection<int, StopRoute>
     */
    public function getStopRoutes(): Collection
    {
        return $this->stopRoutes;
    }

    public function addStopRoute(StopRoute $stopRoute): static
    {
        if (!$this->stopRoutes->contains($stopRoute)) {
            $this->stopRoutes->add($stopRoute);
            $stopRoute->setTownId($this);
        }

        return $this;
    }

    public function removeStopRoute(StopRoute $stopRoute): static
    {
        // set the owning side to null (unless already changed)
        if ($this->stopRoutes->removeElement($stopRoute) && $stopRoute->getTownId() === $this) {
            $stopRoute->setTownId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, StopTown>
     */
    public function getStopTowns(): Collection
    {
        return $this->stopTowns;
    }

    public function addStopTown(StopTown $stopTown): static
    {
        if (!$this->stopTowns->contains($stopTown)) {
            $this->stopTowns->add($stopTown);
            $stopTown->setTownId($this);
        }

        return $this;
    }

    public function removeStopTown(StopTown $stopTown): static
    {
        if ($this->stopTowns->removeElement($stopTown)) {
            // set the owning side to null (unless already changed)
            if ($stopTown->getTownId() === $this) {
                $stopTown->setTownId(null);
            }
        }

        return $this;
    }
}