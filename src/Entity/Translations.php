<?php

namespace App\Entity;

use App\Repository\TranslationsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TranslationsRepository::class)]
class Translations
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'translations')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Column(length: 255)]
    private ?string $table_name = null;

    #[ORM\Column(length: 255)]
    private ?string $field_name = null;

    #[ORM\Column(length: 255)]
    private ?string $language = null;

    #[ORM\Column(length: 255)]
    private ?string $translation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $record_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $record_sub_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field_value = null;

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

    public function getTableName(): ?string
    {
        return $this->table_name;
    }

    public function setTableName(string $table_name): static
    {
        $this->table_name = $table_name;

        return $this;
    }

    public function getFieldName(): ?string
    {
        return $this->field_name;
    }

    public function setFieldName(string $field_name): static
    {
        $this->field_name = $field_name;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getTranslation(): ?string
    {
        return $this->translation;
    }

    public function setTranslation(string $translation): static
    {
        $this->translation = $translation;

        return $this;
    }

    public function getRecordId(): ?string
    {
        return $this->record_id;
    }

    public function setRecordId(?string $record_id): static
    {
        $this->record_id = $record_id;

        return $this;
    }

    public function getRecordSubId(): ?string
    {
        return $this->record_sub_id;
    }

    public function setRecordSubId(?string $record_sub_id): static
    {
        $this->record_sub_id = $record_sub_id;

        return $this;
    }

    public function getFieldValue(): ?string
    {
        return $this->field_value;
    }

    public function setFieldValue(?string $field_value): static
    {
        $this->field_value = $field_value;

        return $this;
    }
}
