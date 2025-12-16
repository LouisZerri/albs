<?php

namespace App\Entity;

use App\Repository\UserStationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserStationRepository::class)]
#[ORM\UniqueConstraint(name: 'user_station_unique', columns: ['user_id', 'station_id'])]
class UserStation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userStations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userStations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Station $station = null;

    #[ORM\Column]
    private ?bool $passed = null;

    #[ORM\Column]
    private ?bool $stopped = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $firstPassedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $firstStoppedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStation(): ?Station
    {
        return $this->station;
    }

    public function setStation(?Station $station): static
    {
        $this->station = $station;

        return $this;
    }

    public function isPassed(): ?bool
    {
        return $this->passed;
    }

    public function setPassed(bool $passed): static
    {
        $this->passed = $passed;

        return $this;
    }

    public function isStopped(): ?bool
    {
        return $this->stopped;
    }

    public function setStopped(bool $stopped): static
    {
        $this->stopped = $stopped;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

      public function getFirstPassedAt(): ?\DateTimeImmutable
    {
        return $this->firstPassedAt;
    }

    public function setFirstPassedAt(?\DateTimeImmutable $firstPassedAt): static
    {
        $this->firstPassedAt = $firstPassedAt;
        return $this;
    }

    public function getFirstStoppedAt(): ?\DateTimeImmutable
    {
        return $this->firstStoppedAt;
    }

    public function setFirstStoppedAt(?\DateTimeImmutable $firstStoppedAt): static
    {
        $this->firstStoppedAt = $firstStoppedAt;
        return $this;
    }
}
