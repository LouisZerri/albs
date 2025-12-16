<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email.')]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[Vich\UploadableField(mapping: 'user_avatars', fileNameProperty: 'avatar')]
    private ?File $avatarFile = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Line::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Line $favoriteLine = null;

    /**
     * @var Collection<int, UserStation>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserStation::class, orphanRemoval: true)]
    private Collection $userStations;

    #[ORM\ManyToMany(targetEntity: Badge::class, mappedBy: 'users')]
    private Collection $badges;

    #[ORM\Column(type: Types::JSON)]
    private array $displayedBadges = []; // IDs des badges à afficher

    public function __construct()
    {
        $this->userStations = new ArrayCollection();
        $this->badges = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?File $avatarFile = null): void
    {
        $this->avatarFile = $avatarFile;

        if (null !== $avatarFile) {
            // Mettre à jour updatedAt pour forcer Doctrine à persister
            $this->updatedAt = new \DateTimeImmutable();
        }
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getFavoriteLine(): ?Line
    {
        return $this->favoriteLine;
    }

    public function setFavoriteLine(?Line $favoriteLine): static
    {
        $this->favoriteLine = $favoriteLine;

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
            $userStation->setUser($this);
        }

        return $this;
    }

    public function removeUserStation(UserStation $userStation): static
    {
        if ($this->userStations->removeElement($userStation)) {
            if ($userStation->getUser() === $this) {
                $userStation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Badge>
     */
    public function getBadges(): Collection
    {
        return $this->badges;
    }

    public function addBadge(Badge $badge): static
    {
        if (!$this->badges->contains($badge)) {
            $this->badges->add($badge);
            $badge->addUser($this);
        }

        return $this;
    }

    public function removeBadge(Badge $badge): static
    {
        if ($this->badges->removeElement($badge)) {
            $badge->removeUser($this);
        }

        return $this;
    }

    public function getDisplayedBadges(): array
    {
        return $this->displayedBadges;
    }

    public function setDisplayedBadges(array $displayedBadges): static
    {
        $this->displayedBadges = $displayedBadges;
        return $this;
    }

    public function addDisplayedBadge(int $badgeId): static
    {
        if (!in_array($badgeId, $this->displayedBadges)) {
            $this->displayedBadges[] = $badgeId;
        }
        return $this;
    }

    public function removeDisplayedBadge(int $badgeId): static
    {
        $this->displayedBadges = array_values(array_filter(
            $this->displayedBadges,
            fn($id) => $id !== $badgeId
        ));
        return $this;
    }
}
