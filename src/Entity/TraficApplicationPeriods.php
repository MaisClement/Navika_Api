<?php

namespace App\Entity;

use App\Repository\TraficApplicationPeriodsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraficApplicationPeriodsRepository::class)]
class TraficApplicationPeriods
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

    #[ORM\ManyToOne(inversedBy: 'applicationPeriods')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE", columnDefinition: 'BIGINT UNSIGNED NOT NULL')]
    private ?Trafic $report_id = null;

    #[ORM\Column(nullable: false)]
    private ?\DateTime $begin = null;

    #[ORM\Column(nullable: false)]
    private ?\DateTime $end = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReportId(): ?Trafic
    {
        return $this->report_id;
    }

    public function setReportId(?Trafic $report_id): static
    {
        $this->report_id = $report_id;

        return $this;
    }

    public function getBegin(): ?\DateTime
    {
        return $this->begin;
    }

    public function setBegin(\DateTime $begin): static
    {
        $this->begin = $begin;

        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    public function setEnd(\DateTime $end): static
    {
        $this->end = $end;

        return $this;
    }
}
