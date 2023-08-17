<?php

namespace App\Entity;

use App\Repository\TransfersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransfersRepository::class)]
class Transfers
{
    #[ORM\ManyToOne(inversedBy: 'transfers')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'transfers')]
    #[ORM\JoinColumn(name: "from_stop_id", referencedColumnName: "stop_id",  nullable: true, onDelete: "CASCADE")]
    private ?Stops $from_stop_id = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'transfers')]
    #[ORM\JoinColumn(name: "to_stop_id", referencedColumnName: "stop_id",  nullable: true, onDelete: "CASCADE")]
    private ?Stops $to_stop_id = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1', '2', '3', '4', '5')")]
    private ?string $transfer_type = null;

    #[ORM\Column(nullable: true)]
    private ?int $min_transfer_time = null;

    public function getProviderId(): ?Provider 
    {
        return $this->provider_id;
    }

    public function setProviderId(Provider $provider_id): static
    {
        $this->provider_id = $provider_id;

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

    public function getTransferType(): ?string
    {
        return $this->transfer_type;
    }

    public function setTransferType(string $transfer_type): static
    {
        $this->transfer_type = $transfer_type;

        return $this;
    }

    public function getMinTransferTime(): ?int
    {
        return $this->min_transfer_time;
    }

    public function setMinTransferTime(?int $min_transfer_time): static
    {
        $this->min_transfer_time = $min_transfer_time;

        return $this;
    }
}
