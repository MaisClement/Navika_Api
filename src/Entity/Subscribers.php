<?php

namespace App\Entity;

use App\Repository\SubscribersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscribersRepository::class)]
class Subscribers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fcm_token = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\OneToMany(mappedBy: 'subscriber_id', targetEntity: RouteSub::class)]
    private Collection $routeSubs;

    public function __construct()
    {
        $this->routeSubs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFcmToken(): ?string
    {
        return $this->fcm_token;
    }

    public function setFcmToken(string $fcm_token): static
    {
        $this->fcm_token = $fcm_token;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return Collection<int, RouteSub>
     */
    public function getRouteSubs(): Collection
    {
        return $this->routeSubs;
    }

    public function addRouteSub(RouteSub $routeSub): static
    {
        if (!$this->routeSubs->contains($routeSub)) {
            $this->routeSubs->add($routeSub);
            $routeSub->setSubscriberId($this);
        }

        return $this;
    }

    public function removeRouteSub(RouteSub $routeSub): static
    {
        // set the owning side to null (unless already changed)
        if ($this->routeSubs->removeElement($routeSub) && $routeSub->getSubscriberId() === $this) {
            $routeSub->setSubscriberId(null);
        }

        return $this;
    }
}
