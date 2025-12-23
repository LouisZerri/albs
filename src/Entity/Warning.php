<?php

namespace App\Entity;

use App\Repository\WarningRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WarningRepository::class)]
class Warning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'warnings')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $moderator = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $reason = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $acknowledgedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $relatedPostId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $relatedPostType = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getModerator(): ?User
    {
        return $this->moderator;
    }

    public function setModerator(?User $moderator): static
    {
        $this->moderator = $moderator;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAcknowledgedAt(): ?\DateTimeImmutable
    {
        return $this->acknowledgedAt;
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledgedAt !== null;
    }

    public function setAcknowledgedAt(?\DateTimeImmutable $acknowledgedAt): static
    {
        $this->acknowledgedAt = $acknowledgedAt;

        return $this;
    }

    public function getRelatedPostId(): ?int
    {
        return $this->relatedPostId;
    }

    public function setRelatedPostId(?int $relatedPostId): self
    {
        $this->relatedPostId = $relatedPostId;
        return $this;
    }

    public function getRelatedPostType(): ?string
    {
        return $this->relatedPostType;
    }

    public function setRelatedPostType(?string $relatedPostType): static
    {
        $this->relatedPostType = $relatedPostType;

        return $this;
    }
}
