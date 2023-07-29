<?php

namespace App\Entity;

use App\Repository\FareAttributesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FareAttributesRepository::class)]
class FareAttributes
{
    #[ORM\ManyToOne(inversedBy: 'fareAttributes')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;
    
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'fareAttributes')]
    #[ORM\JoinColumn(name: "fare_id", referencedColumnName: "fare_id",  nullable: true, onDelete: "CASCADE")]
    private ?FareRules $fare_id = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'fareAttributes')]
    #[ORM\JoinColumn(name: "agency_id", referencedColumnName: "agency_id",  nullable: true, onDelete: "CASCADE")]
    private ?Agency $agency_id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: '0')]
    private ?string $price = null;

    #[ORM\Column(length: 255)]
    private ?string $currency_type = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1')")]
    private ?string $payment_method = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1', '2')")]
    private ?string $transfers = null;

    #[ORM\Column(nullable: true)]
    private ?int $transfer_duration = null;

    public function getProviderId(): ?Provider 
    {
        return $this->provider_id;
    }

    public function setProviderId(Provider $provider_id): static
    {
        $this->provider_id = $provider_id;

        return $this;
    }

    public function getFareId(): ?string
    {
        return $this->fare_id;
    }

    public function setFareId(string $fare_id): static
    {
        $this->fare_id = $fare_id;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCurrencyType(): ?string
    {
        return $this->currency_type;
    }

    public function setCurrencyType(string $currency_type): static
    {
        $this->currency_type = $currency_type;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(string $payment_method): static
    {
        $this->payment_method = $payment_method;

        return $this;
    }

    public function getTransfers(): ?string
    {
        return $this->transfers;
    }

    public function setTransfers(string $transfers): static
    {
        $this->transfers = $transfers;

        return $this;
    }

    public function getAgencyId(): ?int
    {
        return $this->agency_id;
    }

    public function setAgencyId(int $agency_id): static
    {
        $this->agency_id = $agency_id;

        return $this;
    }

    public function getTransferDuration(): ?int
    {
        return $this->transfer_duration;
    }

    public function setTransferDuration(?int $transfer_duration): static
    {
        $this->transfer_duration = $transfer_duration;

        return $this;
    }
}
