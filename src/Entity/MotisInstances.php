<?php

namespace App\Entity;

use App\Repository\MotisInstancesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une instance MOTIS (blue / green).
 *
 * Modèle blue-green : les deux instances existent en permanence, mais une seule
 * porte le drapeau `active`, celle vers laquelle l'API route les requêtes.
 * L'autre est libre pour recevoir un nouveau jeu de données et être testée sans
 * impact ; la bascule se résume à déplacer le drapeau.
 */
#[ORM\Entity(repositoryClass: MotisInstancesRepository::class)]
class MotisInstances
{
    public const STATE_UNKNOWN  = 'unknown';
    public const STATE_STOPPED  = 'stopped';
    public const STATE_STARTING = 'starting';
    public const STATE_RUNNING  = 'running';
    public const STATE_FAILED   = 'failed';
    public const STATE_BUILDING = 'building';

    #[ORM\Id]
    #[ORM\Column]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $port = null;

    #[ORM\Column(length: 255)]
    private ?string $state = self::STATE_UNKNOWN;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updated_at = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pid = null;

    /**
     * Instance vers laquelle l'API route les requêtes. Une seule à la fois :
     * la bascule blue-green consiste à déplacer ce drapeau une fois la nouvelle
     * instance vérifiée.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $active = false;

    /** Résultat de la dernière sonde HTTP. */
    #[ORM\Column(options: ['default' => false])]
    private bool $healthy = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $checked_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $started_at = null;

    /** Répertoire de données servi par l'instance (motis server -d). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $data_path = null;

    /**
     * Empreinte des GTFS ayant servi à construire data_path : permet de savoir
     * si l'instance sert encore les données courantes.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $data_hash = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $last_error = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPort(): ?string
    {
        return $this->port;
    }

    public function setPort(string $port): static
    {
        $this->port = $port;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;
        $this->updated_at = new \DateTime();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getPid(): ?string
    {
        return $this->pid;
    }

    public function setPid($pid): static
    {
        $this->pid = $pid === null ? null : (string) $pid;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isHealthy(): bool
    {
        return $this->healthy;
    }

    public function setHealthy(bool $healthy): static
    {
        $this->healthy = $healthy;
        $this->checked_at = new \DateTime();

        return $this;
    }

    public function getCheckedAt(): ?\DateTime
    {
        return $this->checked_at;
    }

    public function setCheckedAt(?\DateTime $checked_at): static
    {
        $this->checked_at = $checked_at;

        return $this;
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->started_at;
    }

    public function setStartedAt(?\DateTime $started_at): static
    {
        $this->started_at = $started_at;

        return $this;
    }

    public function getDataPath(): ?string
    {
        return $this->data_path;
    }

    public function setDataPath(?string $data_path): static
    {
        $this->data_path = $data_path;

        return $this;
    }

    public function getDataHash(): ?string
    {
        return $this->data_hash;
    }

    public function setDataHash(?string $data_hash): static
    {
        $this->data_hash = $data_hash;

        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->last_error;
    }

    public function setLastError(?string $last_error): static
    {
        $this->last_error = $last_error === null ? null : mb_substr($last_error, 0, 2000);

        return $this;
    }
}
