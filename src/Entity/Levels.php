<?php

namespace App\Entity;

use App\Repository\LevelsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LevelsRepository::class)]
class Levels
{
    #[ORM\ManyToOne(inversedBy: 'levels')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $level_id = null;

    #[ORM\Column(length: 8)]
    private ?string $level_index = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $level_name = null;

    #[ORM\OneToMany(mappedBy: 'level_id', targetEntity: Stops::class)]
    private Collection $stops;

    public function __construct()
    {
        $this->stops = new ArrayCollection();
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

    public function getLevelId(): ?string
    {
        return $this->level_id;
    }

    public function setLevelId(string $level_id): static
    {
        $this->level_id = $level_id;

        return $this;
    }

    public function getLevelIndex(): ?string
    {
        return $this->level_index;
    }

    public function setLevelIndex(string $level_index): static
    {
        $this->level_index = $level_index;

        return $this;
    }

    public function getLevelName(): ?string
    {
        return $this->level_name;
    }

    public function setLevelName(?string $level_name): static
    {
        $this->level_name = $level_name;

        return $this;
    }

    /**
     * @return Collection<int, Stops>
     */
    public function getStops(): Collection
    {
        return $this->stops;
    }

    public function addStop(Stops $stop): static
    {
        if (!$this->stops->contains($stop)) {
            $this->stops->add($stop);
            $stop->setLevelId($this);
        }

        return $this;
    }

    public function removeStop(Stops $stop): static
    {
        // set the owning side to null (unless already changed)
        if ($this->stops->removeElement($stop) && $stop->getLevelId() === $this) {
            $stop->setLevelId(null);
        }

        return $this;
    }
}
