<?php

namespace App\Entity;

use App\Repository\TraficLinksRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraficLinksRepository::class)]
class TraficLinks
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

    #[ORM\Column(length: 255)]
    private ?string $link = null;

    #[ORM\ManyToOne(inversedBy: 'traficLinks')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE", columnDefinition: 'BIGINT UNSIGNED NOT NULL')]
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
