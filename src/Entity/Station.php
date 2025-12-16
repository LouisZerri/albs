<?php

namespace App\Entity;

use App\Repository\StationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StationRepository::class)]
class Station
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'stations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Line $line = null;

    #[ORM\Column]
    private ?int $position = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $branch = null;

    /**
     * @var Collection<int, UserStation>
     */
    #[ORM\OneToMany(targetEntity: UserStation::class, mappedBy: 'station', orphanRemoval: true)]
    private Collection $userStations;

    public function __construct()
    {
        $this->userStations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLine(): ?Line
    {
        return $this->line;
    }

    public function setLine(?Line $line): static
    {
        $this->line = $line;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    public function setBranch(?string $branch): self
    {
        $this->branch = $branch;
        return $this;
    }

    /**
     * @return Collection<int, UserStation>
     */
    public function getUserStations(): Collection
    {
        return $this->userStations;
    }

    public function addUserStation(UserStation $userStation): static
    {
        if (!$this->userStations->contains($userStation)) {
            $this->userStations->add($userStation);
            $userStation->setStation($this);
        }

        return $this;
    }

    public function removeUserStation(UserStation $userStation): static
    {
        if ($this->userStations->removeElement($userStation)) {
            // set the owning side to null (unless already changed)
            if ($userStation->getStation() === $this) {
                $userStation->setStation(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
