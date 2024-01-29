<?php

namespace App\Entity;

use App\Repository\TraficLinksRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraficLinksRepository::class)]
class TraficLinks
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $link = null;

    #[ORM\ManyToOne(inversedBy: 'traficLinks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trafic $trafic_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getTraficId(): ?Trafic
    {
        return $this->trafic_id;
    }

    public function setTraficId(?Trafic $trafic_id): static
    {
        $this->trafic_id = $trafic_id;

        return $this;
    }
}
