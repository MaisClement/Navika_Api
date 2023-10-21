<?php

namespace App\Entity;

use App\Repository\ProviderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProviderRepository::class)]
class Provider
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $area = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updated_at = null;

    #[ORM\Column(columnDefinition: 'ENUM("0", "1", "2")')]
    private ?string $flag = null;

    #[ORM\Column(columnDefinition: 'ENUM("tc", "bikes")')]
    private ?string $type = null;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Trips::class)]
    private Collection $trips;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Agency::class)]
    private Collection $agencies;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Calendar::class)]
    private Collection $calendars;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: CalendarDates::class)]
    private Collection $calendarDates;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Attributions::class)]
    private Collection $attributions;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Translations::class)]
    private Collection $translations;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Transfers::class)]
    private Collection $transfers;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: StopTimes::class)]
    private Collection $stopTimes;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Stops::class)]
    private Collection $stops;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Stations::class)]
    private Collection $stations;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Shapes::class)]
    private Collection $shapes;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Routes::class)]
    private Collection $routes;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Pathways::class)]
    private Collection $pathways;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Levels::class)]
    private Collection $levels;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Frequencies::class)]
    private Collection $frequencies;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: FeedInfo::class)]
    private Collection $feedInfos;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: FareRules::class)]
    private Collection $fareRules;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: FareAttributes::class)]
    private Collection $fareAttributes;

    #[ORM\OneToMany(mappedBy: 'provider_id', targetEntity: Trafic::class)]
    private Collection $trafics;

    public function __construct()
    {
        $this->trips = new ArrayCollection();
        $this->agencies = new ArrayCollection();
        $this->calendars = new ArrayCollection();
        $this->calendarDates = new ArrayCollection();
        $this->attributions = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->transfers = new ArrayCollection();
        $this->stopTimes = new ArrayCollection();
        $this->stops = new ArrayCollection();
        $this->stations = new ArrayCollection();
        $this->shapes = new ArrayCollection();
        $this->routes = new ArrayCollection();
        $this->pathways = new ArrayCollection();
        $this->levels = new ArrayCollection();
        $this->frequencies = new ArrayCollection();
        $this->feedInfos = new ArrayCollection();
        $this->fareRules = new ArrayCollection();
        $this->fareAttributes = new ArrayCollection();
        $this->trafics = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(string $area): static
    {
        $this->area = $area;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

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

    public function getFlag(): ?string
    {
        return $this->flag;
    }

    public function setFlag(string $flag): static
    {
        $this->flag = $flag;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, Trips>
     */
    public function getTrips(): Collection
    {
        return $this->trips;
    }

    public function addTrip(Trips $trip): static
    {
        if (!$this->trips->contains($trip)) {
            $this->trips->add($trip);
            $trip->setProviderId($this);
        }

        return $this;
    }

    public function removeTrip(Trips $trip): static
    {
        // set the owning side to null (unless already changed)
        if ($this->trips->removeElement($trip) && $trip->getProviderId() === $this) {
            $trip->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Agency>
     */
    public function getAgencies(): Collection
    {
        return $this->agencies;
    }

    public function addAgency(Agency $agency): static
    {
        if (!$this->agencies->contains($agency)) {
            $this->agencies->add($agency);
            $agency->setProviderId($this);
        }

        return $this;
    }

    public function removeAgency(Agency $agency): static
    {
        // set the owning side to null (unless already changed)
        if ($this->agencies->removeElement($agency) && $agency->getProviderId() === $this) {
            $agency->setProviderId(null);
        }

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
            $calendar->setProviderId($this);
        }

        return $this;
    }

    public function removeCalendar(Calendar $calendar): static
    {
        // set the owning side to null (unless already changed)
        if ($this->calendars->removeElement($calendar) && $calendar->getProviderId() === $this) {
            $calendar->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, CalendarDates>
     */
    public function getCalendarDates(): Collection
    {
        return $this->calendarDates;
    }

    public function addCalendarDate(CalendarDates $calendarDate): static
    {
        if (!$this->calendarDates->contains($calendarDate)) {
            $this->calendarDates->add($calendarDate);
            $calendarDate->setProviderId($this);
        }

        return $this;
    }

    public function removeCalendarDate(CalendarDates $calendarDate): static
    {
        // set the owning side to null (unless already changed)
        if ($this->calendarDates->removeElement($calendarDate) && $calendarDate->getProviderId() === $this) {
            $calendarDate->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Attributions>
     */
    public function getAttributions(): Collection
    {
        return $this->attributions;
    }

    public function addAttribution(Attributions $attribution): static
    {
        if (!$this->attributions->contains($attribution)) {
            $this->attributions->add($attribution);
            $attribution->setProviderId($this);
        }

        return $this;
    }

    public function removeAttribution(Attributions $attribution): static
    {
        // set the owning side to null (unless already changed)
        if ($this->attributions->removeElement($attribution) && $attribution->getProviderId() === $this) {
            $attribution->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Translations>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(Translations $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setProviderId($this);
        }

        return $this;
    }

    public function removeTranslation(Translations $translation): static
    {
        // set the owning side to null (unless already changed)
        if ($this->translations->removeElement($translation) && $translation->getProviderId() === $this) {
            $translation->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Transfers>
     */
    public function getTransfers(): Collection
    {
        return $this->transfers;
    }

    public function addTransfer(Transfers $transfer): static
    {
        if (!$this->transfers->contains($transfer)) {
            $this->transfers->add($transfer);
            $transfer->setProviderId($this);
        }

        return $this;
    }

    public function removeTransfer(Transfers $transfer): static
    {
        // set the owning side to null (unless already changed)
        if ($this->transfers->removeElement($transfer) && $transfer->getProviderId() === $this) {
            $transfer->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, StopTimes>
     */
    public function getStopTimes(): Collection
    {
        return $this->stopTimes;
    }

    public function addStopTime(StopTimes $stopTime): static
    {
        if (!$this->stopTimes->contains($stopTime)) {
            $this->stopTimes->add($stopTime);
            $stopTime->setProviderId($this);
        }

        return $this;
    }

    public function removeStopTime(StopTimes $stopTime): static
    {
        // set the owning side to null (unless already changed)
        if ($this->stopTimes->removeElement($stopTime) && $stopTime->getProviderId() === $this) {
            $stopTime->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Stops>
     */
    public function getStops(): Collection
    {
        return $this->stops;
    }

    public function addStop(Stops $stop): static
    {
        if (!$this->stops->contains($stop)) {
            $this->stops->add($stop);
            $stop->setProviderId($this);
        }

        return $this;
    }

    public function removeStop(Stops $stop): static
    {
        // set the owning side to null (unless already changed)
        if ($this->stops->removeElement($stop) && $stop->getProviderId() === $this) {
            $stop->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Stations>
     */
    public function getStations(): Collection
    {
        return $this->stations;
    }

    public function addStation(Stations $station): static
    {
        if (!$this->stations->contains($station)) {
            $this->stations->add($station);
            $station->setProviderId($this);
        }

        return $this;
    }

    public function removeStation(Stations $station): static
    {
        // set the owning side to null (unless already changed)
        if ($this->stations->removeElement($station) && $station->getProviderId() === $this) {
            $station->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Shapes>
     */
    public function getShapes(): Collection
    {
        return $this->shapes;
    }

    public function addShape(Shapes $shape): static
    {
        if (!$this->shapes->contains($shape)) {
            $this->shapes->add($shape);
            $shape->setProviderId($this);
        }

        return $this;
    }

    public function removeShape(Shapes $shape): static
    {
        // set the owning side to null (unless already changed)
        if ($this->shapes->removeElement($shape) && $shape->getProviderId() === $this) {
            $shape->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Routes>
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    public function addRoute(Routes $route): static
    {
        if (!$this->routes->contains($route)) {
            $this->routes->add($route);
            $route->setProviderId($this);
        }

        return $this;
    }

    public function removeRoute(Routes $route): static
    {
        // set the owning side to null (unless already changed)
        if ($this->routes->removeElement($route) && $route->getProviderId() === $this) {
            $route->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Pathways>
     */
    public function getPathways(): Collection
    {
        return $this->pathways;
    }

    public function addPathway(Pathways $pathway): static
    {
        if (!$this->pathways->contains($pathway)) {
            $this->pathways->add($pathway);
            $pathway->setProviderId($this);
        }

        return $this;
    }

    public function removePathway(Pathways $pathway): static
    {
        // set the owning side to null (unless already changed)
        if ($this->pathways->removeElement($pathway) && $pathway->getProviderId() === $this) {
            $pathway->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Levels>
     */
    public function getLevels(): Collection
    {
        return $this->levels;
    }

    public function addLevel(Levels $level): static
    {
        if (!$this->levels->contains($level)) {
            $this->levels->add($level);
            $level->setProviderId($this);
        }

        return $this;
    }

    public function removeLevel(Levels $level): static
    {
        // set the owning side to null (unless already changed)
        if ($this->levels->removeElement($level) && $level->getProviderId() === $this) {
            $level->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Frequencies>
     */
    public function getFrequencies(): Collection
    {
        return $this->frequencies;
    }

    public function addFrequency(Frequencies $frequency): static
    {
        if (!$this->frequencies->contains($frequency)) {
            $this->frequencies->add($frequency);
            $frequency->setProviderId($this);
        }

        return $this;
    }

    public function removeFrequency(Frequencies $frequency): static
    {
        // set the owning side to null (unless already changed)
        if ($this->frequencies->removeElement($frequency) && $frequency->getProviderId() === $this) {
            $frequency->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, FeedInfo>
     */
    public function getFeedInfos(): Collection
    {
        return $this->feedInfos;
    }

    public function addFeedInfo(FeedInfo $feedInfo): static
    {
        if (!$this->feedInfos->contains($feedInfo)) {
            $this->feedInfos->add($feedInfo);
            $feedInfo->setProviderId($this);
        }

        return $this;
    }

    public function removeFeedInfo(FeedInfo $feedInfo): static
    {
        // set the owning side to null (unless already changed)
        if ($this->feedInfos->removeElement($feedInfo) && $feedInfo->getProviderId() === $this) {
            $feedInfo->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, FareRules>
     */
    public function getFareRules(): Collection
    {
        return $this->fareRules;
    }

    public function addFareRule(FareRules $fareRule): static
    {
        if (!$this->fareRules->contains($fareRule)) {
            $this->fareRules->add($fareRule);
            $fareRule->setProviderId($this);
        }

        return $this;
    }

    public function removeFareRule(FareRules $fareRule): static
    {
        // set the owning side to null (unless already changed)
        if ($this->fareRules->removeElement($fareRule) && $fareRule->getProviderId() === $this) {
            $fareRule->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, FareAttributes>
     */
    public function getFareAttributes(): Collection
    {
        return $this->fareAttributes;
    }

    public function addFareAttribute(FareAttributes $fareAttribute): static
    {
        if (!$this->fareAttributes->contains($fareAttribute)) {
            $this->fareAttributes->add($fareAttribute);
            $fareAttribute->setProviderId($this);
        }

        return $this;
    }

    public function removeFareAttribute(FareAttributes $fareAttribute): static
    {
        // set the owning side to null (unless already changed)
        if ($this->fareAttributes->removeElement($fareAttribute) && $fareAttribute->getProviderId() === $this) {
            $fareAttribute->setProviderId(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Trafic>
     */
    public function getTrafics(): Collection
    {
        return $this->trafics;
    }
}