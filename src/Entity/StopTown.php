<?php

namespace App\Entity;

use App\Repository\StopTownRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StopTownRepository::class)]
class StopTown
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stopTowns')]
    #[ORM\JoinColumn(name: "stop_id", referencedColumnName: "stop_id", nullable: true, onDelete: "CASCADE")]
    private ?Stops $stop_id = null;

    #[ORM\ManyToOne(inversedBy: 'stopTowns')]
    #[ORM\JoinColumn(name: "town_id", referencedColumnName: "town_id", nullable: true, onDelete: "CASCADE")]
    private ?Town $town_id = null;

    public function getStopId(): ?Stops
    {
        return $this->stop_id;
    }

    public function setStopId(?Stops $stop_id): static
    {
        $this->stop_id = $stop_id;

        return $this;
    }

    public function getTownId(): ?Town
    {
        return $this->town_id;
    }

    public function setTownId(?Town $town_id): static
    {
        $this->town_id = $town_id;

        return $this;
    }
}