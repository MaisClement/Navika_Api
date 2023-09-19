<?php

namespace App\Entity;

use App\Repository\TraficRepository;
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column]
    private ?int $severity = null;

    #[ORM\Column(length: 255)]
    private ?string $effect = null;

    #[ORM\Column]
    private ?\DateTime $updated_at = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\ManyToOne(inversedBy: 'trafics')]
    #[ORM\JoinColumn(name: "route_id", referencedColumnName: "route_id", nullable: false, onDelete: "CASCADE")]
    private ?Routes $route_id = null;

    public function getReport(): ?array
    {
        return array(
            "id" =>         $this->getReportId(),
            "status" =>     $this->getStatus(),
            "cause" =>      $this->getCause(),
            "category" =>   $this->getCategory(),
            "severity" =>   $this->getSeverity(),
            "effect" =>     $this->getEffect(),
            "updated_at" => $this->getUpdatedAt()->format("Y-m-d\TH:i:sP"),
            "message" =>    array(
                "title" =>      $this->getTitle(),
                "text" =>       $this->getText(),
            ),
        );
    }

    public function getReportMessage(): ?array
    {
        return array(
            "id" =>         $this->getReportId(),
            "type" =>       'report',
            "line" =>       (string)    $this->getRouteId()->getRouteId(),
            "status" =>     (string)    $this->getStatus(),
            "cause" =>      (string)    $this->getCause(),
            "category" =>   (string)    $this->getCategory(),
            "severity" =>   (int)       $this->getSeverity(),
            "effect" =>     (string)    $this->getEffect(),
            "updated_at" => $this->getUpdatedAt()->format("Y-m-d\TH:i:sP"),
            "title" =>      (string)    $this->getTitle(),
            "body" =>       (string)    $this->getText(),
        );
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

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
}