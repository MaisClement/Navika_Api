<?php

namespace App\Entity;

use App\Repository\TraficApplicationPeriodsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraficApplicationPeriodsRepository::class)]
class TraficApplicationPeriods
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'applicationPeriods')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
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
