<?php

namespace App\Entity;

use App\Repository\CalendarDatesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalendarDatesRepository::class)]
#[ORM\Index(name: "calendar_dates_service_id", fields: ["service_id"])]

class CalendarDates
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'calendarDates')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\ManyToOne(inversedBy: 'calendarDates')]
    #[ORM\JoinColumn(name: "service_id", referencedColumnName: "service_id", nullable: true, onDelete: "CASCADE")]
    private ?Calendar $service_id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1', '2')")]
    private ?int $exception_type = null;

    public function __construct()
    {
        $this->calendars = new ArrayCollection();
    }

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

    public function getServiceId(): ?string
    {
        return $this->service_id;
    }

    public function setServiceId(string $service_id): static
    {
        $this->service_id = $service_id;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getExceptionType(): ?int
    {
        return $this->exception_type;
    }

    public function setExceptionType(int $exception_type): static
    {
        $this->exception_type = $exception_type;

        return $this;
    }

    /**
     * @return Collection<int, Calendar>
     */
    public function getCalendars(): Collection
    {
        return $this->calendars;
    }

    public function addCalendar(Calendar $calendar): static
    {
        if (!$this->calendars->contains($calendar)) {
            $this->calendars->add($calendar);
            $calendar->setServiceId($this);
        }

        return $this;
    }

    public function removeCalendar(Calendar $calendar): static
    {
        if ($this->calendars->removeElement($calendar)) {
            // set the owning side to null (unless already changed)
            if ($calendar->getServiceId() === $this) {
                $calendar->setServiceId(null);
            }
        }

        return $this;
    }

    public function addServiceId(Calendar $serviceId): static
    {
        if (!$this->service_id->contains($serviceId)) {
            $this->service_id->add($serviceId);
            $serviceId->setCalendarDates($this);
        }

        return $this;
    }

    public function removeServiceId(Calendar $serviceId): static
    {
        if ($this->service_id->removeElement($serviceId)) {
            // set the owning side to null (unless already changed)
            if ($serviceId->getCalendarDates() === $this) {
                $serviceId->setCalendarDates(null);
            }
        }

        return $this;
    }
}
