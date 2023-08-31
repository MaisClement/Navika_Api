<?php

namespace App\Entity;

use App\Repository\PathwaysRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PathwaysRepository::class)]
class Pathways
{
    #[ORM\ManyToOne(inversedBy: 'pathways')]
    #[ORM\JoinColumn(name: "provider_id", nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $pathway_id = null;

    #[ORM\ManyToOne(inversedBy: 'pathways')]
    #[ORM\JoinColumn(name: "from_stop_id", referencedColumnName: "stop_id", nullable: true, onDelete: "CASCADE")]
    private ?Stops $from_stop_id = null;

    #[ORM\ManyToOne(inversedBy: 'pathways')]
    #[ORM\JoinColumn(name: "to_stop_id", referencedColumnName: "stop_id", nullable: true, onDelete: "CASCADE")]
    private ?Stops $to_stop_id = null;

    #[ORM\Column(columnDefinition: 'ENUM("0", "1", "2", "3", "4", "5", "6", "7")')]
    private ?string $pathway_mode = null;

    #[ORM\Column(columnDefinition: 'ENUM("0", "1")')]
    private ?string $is_bidirectional = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: '0', nullable: true)]
    private ?string $length = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $traversal_time = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $stair_count = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $max_slope = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $min_width = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signposted_as = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reversed_signposted_as = null;

    public function getProviderId(): ?Provider
    {
        return $this->provider_id;
    }

    public function setProviderId(Provider $provider_id): static
    {
        $this->provider_id = $provider_id;

        return $this;
    }

    public function getPathwayId(): ?string
    {
        return $this->pathway_id;
    }

    public function setPathwayId(string $pathway_id): static
    {
        $this->pathway_id = $pathway_id;

        return $this;
    }

    public function getFromStopId(): ?string
    {
        return $this->from_stop_id;
    }

    public function setFromStopId(string $from_stop_id): static
    {
        $this->from_stop_id = $from_stop_id;

        return $this;
    }

    public function getToStopId(): ?string
    {
        return $this->to_stop_id;
    }

    public function setToStopId(string $to_stop_id): static
    {
        $this->to_stop_id = $to_stop_id;

        return $this;
    }

    public function getPathwayMode(): ?string
    {
        return $this->pathway_mode;
    }

    public function setPathwayMode(string $pathway_mode): static
    {
        $this->pathway_mode = $pathway_mode;

        return $this;
    }

    public function getIsBidirectional(): ?string
    {
        return $this->is_bidirectional;
    }

    public function setIsBidirectional(string $is_bidirectional): static
    {
        $this->is_bidirectional = $is_bidirectional;

        return $this;
    }

    public function getLength(): ?string
    {
        return $this->length;
    }

    public function setLength(string $length): static
    {
        $this->length = $length;

        return $this;
    }

    public function getTraversalTime(): ?string
    {
        return $this->traversal_time;
    }

    public function setTraversalTime(?string $traversal_time): static
    {
        $this->traversal_time = $traversal_time;

        return $this;
    }

    public function getStairCount(): ?string
    {
        return $this->stair_count;
    }

    public function setStairCount(?string $stair_count): static
    {
        $this->stair_count = $stair_count;

        return $this;
    }

    public function getMaxSlope(): ?string
    {
        return $this->max_slope;
    }

    public function setMaxSlope(?string $max_slope): static
    {
        $this->max_slope = $max_slope;

        return $this;
    }

    public function getMinWidth(): ?string
    {
        return $this->min_width;
    }

    public function setMinWidth(?string $min_width): static
    {
        $this->min_width = $min_width;

        return $this;
    }

    public function getSignpostedAs(): ?string
    {
        return $this->signposted_as;
    }

    public function setSignpostedAs(?string $signposted_as): static
    {
        $this->signposted_as = $signposted_as;

        return $this;
    }

    public function getReversedSignpostedAs(): ?string
    {
        return $this->reversed_signposted_as;
    }

    public function setReversedSignpostedAs(?string $reversed_signposted_as): static
    {
        $this->reversed_signposted_as = $reversed_signposted_as;

        return $this;
    }
}