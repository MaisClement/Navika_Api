<?php

namespace App\Entity;

use App\Repository\RoutesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use App\Controller\Functions;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoutesRepository::class)]
class Routes
{
    #[ORM\ManyToOne(inversedBy: 'routes')]
    #[ORM\JoinColumn(name: "provider_id",  nullable: true, onDelete: "CASCADE")]
    private ?Provider $provider_id = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $route_id = null;

    #[ORM\ManyToOne(inversedBy: 'routes')]
    #[ORM\JoinColumn(name: "agency_id", referencedColumnName: "agency_id", nullable: false, onDelete: "CASCADE")]
    private ?Agency $agency_id = null;

   #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $route_short_name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $route_long_name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $route_desc = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $route_type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $route_url = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $route_color = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $route_text_color = null;

    #[ORM\Column(nullable: true)]
    private ?int $route_sort_order = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1', '2', '3')")]
    private ?string $continuous_pickup = null;

    #[ORM\Column(columnDefinition: "ENUM('0', '1', '2', '3')")]
    private ?string $continuous_drop_off = null;

    #[ORM\OneToMany(mappedBy: 'route_id', targetEntity: StopRoute::class)]
    private Collection $stopRoutes;

    #[ORM\OneToMany(mappedBy: 'route_id', targetEntity: Trips::class)]
    private Collection $trips;

    #[ORM\OneToMany(mappedBy: 'route_id', targetEntity: Trafic::class)]
    private Collection $trafics;

    #[ORM\OneToMany(mappedBy: 'route_id', targetEntity: FareRules::class)]
    private Collection $fareRules;

    #[ORM\OneToMany(mappedBy: 'route_id', targetEntity: Timetable::class)]
    private Collection $timetables;

    public function __construct()
    {
        $this->stopRoutes = new ArrayCollection();
        $this->trips = new ArrayCollection();
        $this->trafics = new ArrayCollection();
        $this->fareRules = new ArrayCollection();
        $this->timetables = new ArrayCollection();
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

    public function getRouteId(): ?string
    {
        return $this->route_id;
    }

    public function setRouteId(string $route_id): static
    {
        $this->route_id = $route_id;

        return $this;
    }

    public function getAgencyId(): ?Agency
    {
        return $this->agency_id;
    }

    public function setAgencyId(Agency $agency_id): static
    {
        $this->agency_id = $agency_id;

        return $this;
    }

    public function getRouteShortName(): ?string
    {
        return $this->route_short_name;
    }

    public function setRouteShortName(?string $route_short_name): static
    {
        $this->route_short_name = $route_short_name;

        return $this;
    }

    public function getRouteLongName(): ?string
    {
        return $this->route_long_name;
    }

    public function setRouteLongName(?string $route_long_name): static
    {
        $this->route_long_name = $route_long_name;

        return $this;
    }

    public function getRouteDesc(): ?string
    {
        return $this->route_desc;
    }

    public function setRouteDesc(?string $route_desc): static
    {
        $this->route_desc = $route_desc;

        return $this;
    }

    public function getRouteType(): ?string
    {
        return $this->route_type;
    }

    public function setRouteType(?string $route_type): static
    {
        $this->route_type = $route_type;

        return $this;
    }

    public function getTransportMode(): ?string
    {
        return Functions::getTransportMode($this->route_type);
    }

    public function getRouteUrl(): ?string
    {
        return $this->route_url;
    }

    public function setRouteUrl(?string $route_url): static
    {
        $this->route_url = $route_url;

        return $this;
    }

    public function getRouteColor(): ?string
    {
        return $this->route_color;
    }

    public function setRouteColor(?string $route_color): static
    {
        $this->route_color = $route_color;

        return $this;
    }

    public function getRouteTextColor(): ?string
    {
        return $this->route_text_color;
    }

    public function setRouteTextColor(?string $route_text_color): static
    {
        $this->route_text_color = $route_text_color;

        return $this;
    }

    public function getRouteSortOrder(): ?int
    {
        return $this->route_sort_order;
    }

    public function setRouteSortOrder(?int $route_sort_order): static
    {
        $this->route_sort_order = $route_sort_order;

        return $this;
    }

    public function getContinuousPickup(): ?string
    {
        return $this->continuous_pickup;
    }

    public function setContinuousPickup(string $continuous_pickup): static
    {
        $this->continuous_pickup = $continuous_pickup;

        return $this;
    }

    public function getRoute(): ?array
    {
        return array(
            "id"         =>  (string)    $this->route_id,
            "code"       =>  (string)    $this->route_short_name,
            "name"       =>  (string)    $this->route_long_name,
            "mode"       =>  (string)    Functions::getTransportMode($this->route_type),
            "color"      =>  (string)    strlen($this->route_color) < 6 ? "ffffff" : $this->route_color,
            "text_color" =>  (string)    strlen($this->route_text_color) < 6 ? "000000" : $this->route_text_color,
            "agency"     => array(
                "id"         =>              $this->agency_id->getAgencyId(),
                "name"       =>              $this->agency_id->getAgencyName(),
                "area"       =>              $this->provider_id->getArea(),
            )
        );
    }

    public function getRouteAndTrafic(): ?array
    {
        $reports = $this->trafics;
        // ---
        $trafic = [];
        $trafic = $this->getRoute();
        $trafic['severity'] = 0;
        $trafic['reports']['future_work'] = [];
        $trafic['reports']['current_work'] = [];
        $trafic['reports']['current_trafic'] = [];

        foreach ($reports as $report) {
            $route_id = $this->route_id;
            
            $r = array(
                "id"            =>  (string)    $report->getId(),
                "status"        =>  (string)    $report->getStatus(),
                "cause"         =>  (string)    $report->getCause(),
                "category"      =>  (string)    $report->getCategory(),
                "severity"      =>  (int)       $report->getSeverity(),
                "effect"        =>  (string)    $report->getEffect(),
                "updated_at"    =>  (string)    $report->getUpdatedAt()->format("Y-m-d\TH:i:sP"),
                "message"       =>  array(
                    "title"     =>      $report->getTitle(),
                    "text"      =>      $report->getText(),
                ),
            );

            $severity = $trafic['severity'] > $report->getSeverity() ? $trafic['severity'] : $report->getSeverity();
            
            $trafic['severity'] = $severity;
            
            if ( $report->getCause() == 'future' ) {
                $trafic['reports']['future_work'][] = $r;

            } else if ( $report->getSeverity() == 2 ) {
                $trafic['reports']['future_work'][] = $r;

            } else if ( $report->getSeverity() == 3 ) {
                $trafic['reports']['current_work'][] = $r;
                
            } else {
                $trafic['reports']['current_trafic'][] = $r;
            }
        }
        return $trafic;
    }

    public function getTrafic(): ?array
    {
        $reports = $this->trafics;
        // ---
        $trafic = [];
        $trafic['severity'] = 0;
        $trafic['reports']['future_work'] = [];
        $trafic['reports']['current_work'] = [];
        $trafic['reports']['current_trafic'] = [];

        foreach ($reports as $report) {
            $route_id = $this->route_id;
            
            $r = array(
                "id"            =>  (string)    $report->getId(),
                "status"        =>  (string)    $report->getStatus(),
                "cause"         =>  (string)    $report->getCause(),
                "category"      =>  (string)    $report->getCategory(),
                "severity"      =>  (int)       $report->getSeverity(),
                "effect"        =>  (string)    $report->getEffect(),
                "updated_at"    =>  (string)    $report->getUpdatedAt()->format("Y-m-d\TH:i:sP"),
                "message"       =>  array(
                    "title"     =>      $report->getTitle(),
                    "text"      =>      $report->getText(),
                ),
            );

            $severity = $trafic['severity'] > $report->getSeverity() ? $trafic['severity'] : $report->getSeverity();
            
            $trafic['severity'] = $severity;
            
            if ( $report->getCause() == 'future' ) {
                $trafic['reports']['future_work'][] = $r;

            } else if ( $report->getSeverity() == 2 ) {
                $trafic['reports']['future_work'][] = $r;

            } else if ( $report->getSeverity() == 3 ) {
                $trafic['reports']['current_work'][] = $r;
                
            } else {
                $trafic['reports']['current_trafic'][] = $r;
            }
        }
        return $trafic;
    }

    /**
     * @return Collection<int, StopRoute>
     */
    public function getStopRoutes(): Collection
    {
        return $this->stopRoutes;
    }

    public function addStopRoute(StopRoute $stopRoute): static
    {
        if (!$this->stopRoutes->contains($stopRoute)) {
            $this->stopRoutes->add($stopRoute);
            $stopRoute->setRouteId($this);
        }

        return $this;
    }

    public function removeStopRoute(StopRoute $stopRoute): static
    {
        if ($this->stopRoutes->removeElement($stopRoute)) {
            // set the owning side to null (unless already changed)
            if ($stopRoute->getRouteId() === $this) {
                $stopRoute->setRouteId(null);
            }
        }

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
            $trip->setRouteId($this);
        }

        return $this;
    }

    public function removeTrip(Trips $trip): static
    {
        if ($this->trips->removeElement($trip)) {
            // set the owning side to null (unless already changed)
            if ($trip->getRouteId() === $this) {
                $trip->setRouteId(null);
            }
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

    public function addTrafic(Trafic $trafic): static
    {
        if (!$this->trafics->contains($trafic)) {
            $this->trafics->add($trafic);
            $trafic->setRouteId($this);
        }

        return $this;
    }

    public function removeTrafic(Trafic $trafic): static
    {
        if ($this->trafics->removeElement($trafic)) {
            // set the owning side to null (unless already changed)
            if ($trafic->getRouteId() === $this) {
                $trafic->setRouteId(null);
            }
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
            $fareRule->setRouteId($this);
        }

        return $this;
    }

    public function removeFareRule(FareRules $fareRule): static
    {
        if ($this->fareRules->removeElement($fareRule)) {
            // set the owning side to null (unless already changed)
            if ($fareRule->getRouteId() === $this) {
                $fareRule->setRouteId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Timetable>
     */
    public function getTimetables(): Collection
    {
        return $this->timetables;
    }

    public function addTimetable(Timetable $timetable): static
    {
        if (!$this->timetables->contains($timetable)) {
            $this->timetables->add($timetable);
            $timetable->setRouteId($this);
        }

        return $this;
    }

    public function removeTimetable(Timetable $timetable): static
    {
        if ($this->timetables->removeElement($timetable)) {
            // set the owning side to null (unless already changed)
            if ($timetable->getRouteId() === $this) {
                $timetable->setRouteId(null);
            }
        }

        return $this;
    }
}
