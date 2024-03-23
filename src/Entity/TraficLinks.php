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
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Trafic $report_id = null;

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

    public function getReportId(): ?Trafic
    {
        return $this->report_id;
    }

    public function setTraficId(?Trafic $report_id): static
    {
        $this->report_id = $report_id;

        return $this;
    }
}
