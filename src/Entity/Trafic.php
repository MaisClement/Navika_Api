<?php

namespace App\Entity;

use App\Repository\TraficRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraficRepository::class)]
class Trafic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $report_id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cause = null;

    #[ORM\Column]
    private ?int $severity = null;

    #[ORM\Column(length: 255)]
    private ?string $effect = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updated_at = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\ManyToOne(inversedBy: 'trafics')]
    #[ORM\JoinColumn(name: "route_id", referencedColumnName: "route_id", nullable: false, onDelete: "CASCADE")]
    private ?Routes $route_id = null;

    #[ORM\OneToMany(mappedBy: 'report_id', targetEntity: TraficLinks::class)]
    private Collection $traficLinks;

    #[ORM\OneToMany(mappedBy: 'report_id', targetEntity: TraficApplicationPeriods::class)]
    private Collection $applicationPeriods;

    public function __construct()
    {
        $this->traficLinks = new ArrayCollection();
        $this->applicationPeriods = new ArrayCollection();
    }

    public function getReportMessage(): ?array
    {
        return array(
            "id" =>         $this->getReportId(),
            "type" =>       'report',
            "line" =>       (string)    $this->getRouteId()->getRouteId(),
            "status" =>     (string)    $this->getStatus(),
            "cause" =>      (string)    $this->getCause(),
            "severity" =>   (int)       $this->getSeverity(),
            "effect" =>     (string)    $this->getEffect(),
            "updated_at" =>             $this->getUpdatedAt() == null ? null : $this->getUpdatedAt()->format("Y-m-d\TH:i:sP"),
            "title" =>      (string)    $this->getTitle(),
            "body" =>       (string)    $this->getText(),
        );
    }

    public function getApplicationPeriods(): ?array
    {
        $application_periods = [];

        foreach($this->getTraficApplicationPeriods() as $periods) {
            $application_periods[] = array(
                "begin" => $periods->getBegin()->format("Y-m-d\TH:i:sP"),
                "end"   => $periods->getEnd()->format("Y-m-d\TH:i:sP"),
            );
        }

        return $application_periods;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReportId(): ?string
    {
        return $this->report_id;
    }

    public function setReportId(string $report_id): static
    {
        $this->report_id = $report_id;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCause(): ?string
    {
        return $this->cause;
    }

    public function setCause(?string $cause): static
    {
        $this->cause = $cause;

        return $this;
    }

    public function getSeverity(): ?int
    {
        return $this->severity;
    }

    public function setSeverity(int $severity): static
    {
        $this->severity = $severity;

        return $this;
    }

    public function getEffect(): ?string
    {
        return $this->effect;
    }

    public function setEffect(string $effect): static
    {
        $this->effect = $effect;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getRouteId(): ?Routes
    {
        return $this->route_id;
    }

    public function setRouteId(?Routes $route_id): static
    {
        $this->route_id = $route_id;

        return $this;
    }

    /**
     * @return Collection<int, TraficLinks>
     */
    public function getTraficLinks(): Collection
    {
        return $this->traficLinks;
    }

    public function addTraficLink(TraficLinks $traficLink): static
    {
        if (!$this->traficLinks->contains($traficLink)) {
            $this->traficLinks->add($traficLink);
            $traficLink->setTraficId($this);
        }

        return $this;
    }

    public function removeTraficLink(TraficLinks $traficLink): static
    {
        if ($this->traficLinks->removeElement($traficLink)) {
            // set the owning side to null (unless already changed)
            if ($traficLink->getReportId() === $this) {
                $traficLink->setTraficId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TraficApplicationPeriods>
     */
    public function getTraficApplicationPeriods(): Collection
    {
        return $this->applicationPeriods;
    }

    public function addApplicationPeriod(TraficApplicationPeriods $applicationPeriods): static
    {
        if (!$this->applicationPeriods->contains($applicationPeriods)) {
            $this->applicationPeriods->add($applicationPeriods);
            $applicationPeriods->setReportId($this);
        }

        return $this;
    }

    public function removeTraficApplicationPeriod(TraficApplicationPeriods $applicationPeriods): static
    {
        if ($this->applicationPeriods->removeElement($applicationPeriods)) {
            // set the owning side to null (unless already changed)
            if ($applicationPeriods->getReportId() === $this) {
                $applicationPeriods->setReportId(null);
            }
        }

        return $this;
    }
}