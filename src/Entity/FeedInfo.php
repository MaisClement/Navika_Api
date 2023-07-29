<?php

namespace App\Entity;

use App\Repository\FeedInfoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeedInfoRepository::class)]
class FeedInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'feedInfos')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Column(length: 255)]
    private ?string $feed_publisher_name = null;

    #[ORM\Column(length: 255)]
    private ?string $feed_publisher_url = null;

    #[ORM\Column(length: 255)]
    private ?string $feed_lang = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $default_lang = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $feed_start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $feed_end_date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $feed_version = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $feed_contact_email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $feed_contact_url = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProviderId(): ?Provider 
    {
        return $this->provider_id;
    }

    public function setProviderId(Provider $provider_id): static
    {
        $this->provider_id = $provider_id;

        return $this;
    }

    public function getFeedPublisherName(): ?string
    {
        return $this->feed_publisher_name;
    }

    public function setFeedPublisherName(string $feed_publisher_name): static
    {
        $this->feed_publisher_name = $feed_publisher_name;

        return $this;
    }

    public function getFeedPublisherUrl(): ?string
    {
        return $this->feed_publisher_url;
    }

    public function setFeedPublisherUrl(string $feed_publisher_url): static
    {
        $this->feed_publisher_url = $feed_publisher_url;

        return $this;
    }

    public function getFeedLang(): ?string
    {
        return $this->feed_lang;
    }

    public function setFeedLang(string $feed_lang): static
    {
        $this->feed_lang = $feed_lang;

        return $this;
    }

    public function getDefaultLang(): ?string
    {
        return $this->default_lang;
    }

    public function setDefaultLang(?string $default_lang): static
    {
        $this->default_lang = $default_lang;

        return $this;
    }

    public function getFeedStartDate(): ?\DateTimeInterface
    {
        return $this->feed_start_date;
    }

    public function setFeedStartDate(?\DateTimeInterface $feed_start_date): static
    {
        $this->feed_start_date = $feed_start_date;

        return $this;
    }

    public function getFeedEndDate(): ?\DateTimeInterface
    {
        return $this->feed_end_date;
    }

    public function setFeedEndDate(?\DateTimeInterface $feed_end_date): static
    {
        $this->feed_end_date = $feed_end_date;

        return $this;
    }

    public function getFeedVersion(): ?string
    {
        return $this->feed_version;
    }

    public function setFeedVersion(?string $feed_version): static
    {
        $this->feed_version = $feed_version;

        return $this;
    }

    public function getFeedContactEmail(): ?string
    {
        return $this->feed_contact_email;
    }

    public function setFeedContactEmail(?string $feed_contact_email): static
    {
        $this->feed_contact_email = $feed_contact_email;

        return $this;
    }
}
