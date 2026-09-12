<?php

namespace App\Entity;

use App\Repository\StopExtensionsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StopExtensionsRepository::class)]
#[ORM\Index(name: "stop_extensions_object_code", fields: ["object_id"])]
class StopExtensions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'routes')]
    #[ORM\JoinColumn(name: "provider_id", nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\ManyToOne(inversedBy: 'stopExtensions')]
    #[ORM\JoinColumn(name: "object_id", referencedColumnName: "stop_id", nullable: true, onDelete: "CASCADE")]
    private ?Stops $object_id = null;

    #[ORM\Column(length: 255)]
    private ?string $object_code = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getObjectId(): ?Stops
    {
        return $this->object_id;
    }

    public function setObjectId(?Stops $object_id): static
    {
        $this->object_id = $object_id;

        return $this;
    }

    public function getObjectSystem(): ?string
    {
        return $this->object_system;
    }

    public function setObjectSystem(?string $object_system): static
    {
        $this->object_system = $object_system;

        return $this;
    }

    public function getObjectCode(): ?string
    {
        return $this->object_code;
    }

    public function setObjectCode(string $object_code): static
    {
        $this->object_code = $object_code;

        return $this;
    }
}
