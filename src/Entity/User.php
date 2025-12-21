<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Impossible d\'utiliser cet adresse email.')]
#[UniqueEntity(fields: ['username'], message: 'Ce pseudo est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // ========== CONSTANTES ==========

    private const ALLOWED_STATUSES = ['pending', 'active', 'banned', 'deleted'];

    // ========== PROPRIÉTÉS : IDENTITÉ ==========

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    // ========== PROPRIÉTÉS : PROFIL ==========

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\ManyToOne(targetEntity: Line::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Line $favoriteLine = null;

    // ========== PROPRIÉTÉS : STATUT DU COMPTE ==========

    /**
     * Statut du compte : pending, active, banned, deleted
     */
    #[ORM\Column(length: 20)]
    private string $accountStatus = 'pending';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $bannedAt = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $warningCount = 0;

    // ========== PROPRIÉTÉS : VÉRIFICATION EMAIL ==========

    #[ORM\Column]
    private bool $isEmailVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailVerificationTokenExpiresAt = null;

    // ========== PROPRIÉTÉS : RÉINITIALISATION MOT DE PASSE ==========

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

    // ========== PROPRIÉTÉS : CONNEXION ==========

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rememberMeToken = null;

    // ========== PROPRIÉTÉS : TIMESTAMPS ==========

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // ========== PROPRIÉTÉS : RELATIONS ==========

    /**
     * @var Collection<int, UserStation>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserStation::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $userStations;

    /**
     * @var Collection<int, Badge>
     */
    #[ORM\ManyToMany(targetEntity: Badge::class, mappedBy: 'users')]
    private Collection $badges;

    /**
     * IDs des badges à afficher sur le profil (max 3)
     */
    #[ORM\Column(type: Types::JSON)]
    private array $displayedBadges = [];

    /**
     * @var Collection<int, Warning>
     */
    #[ORM\OneToMany(targetEntity: Warning::class, mappedBy: 'user')]
    private Collection $warnings;

    // ========== CONSTRUCTEUR & LIFECYCLE ==========

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->userStations = new ArrayCollection();
        $this->badges = new ArrayCollection();
        $this->warnings = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ========== MÉTHODES : IDENTITÉ ==========

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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Effacer les données temporaires sensibles si nécessaire
    }

    // ========== MÉTHODES : RÔLES ==========

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function addRole(string $role): static
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function removeRole(string $role): static
    {
        $this->roles = array_values(array_diff($this->roles, [$role]));
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles, true);
    }

    public function isModerator(): bool
    {
        return in_array('ROLE_MODERATOR', $this->roles, true) || $this->isAdmin();
    }

    // ========== MÉTHODES : PROFIL ==========

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;
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

    // ========== MÉTHODES : STATUT DU COMPTE ==========

    public function getAccountStatus(): string
    {
        return $this->accountStatus;
    }

    public function setAccountStatus(string $accountStatus): static
    {
        if (!in_array($accountStatus, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException('Statut de compte invalide : ' . $accountStatus);
        }
        $this->accountStatus = $accountStatus;
        return $this;
    }

    public function isPending(): bool
    {
        return $this->accountStatus === 'pending';
    }

    public function isActive(): bool
    {
        return $this->accountStatus === 'active';
    }

    public function isBanned(): bool
    {
        return $this->accountStatus === 'banned';
    }

    public function isDeleted(): bool
    {
        return $this->accountStatus === 'deleted';
    }

    public function activate(): static
    {
        $this->accountStatus = 'active';
        return $this;
    }

    public function ban(): static
    {
        $this->accountStatus = 'banned';
        $this->bannedAt = new \DateTimeImmutable();
        return $this;
    }

    public function unban(): static
    {
        $this->accountStatus = 'active';
        $this->bannedAt = null;
        $this->warningCount = 0;
        return $this;
    }

    public function markAsDeleted(): static
    {
        $this->accountStatus = 'deleted';
        return $this;
    }

    public function getBannedAt(): ?\DateTimeImmutable
    {
        return $this->bannedAt;
    }

    public function setBannedAt(?\DateTimeImmutable $bannedAt): static
    {
        $this->bannedAt = $bannedAt;
        return $this;
    }

    // ========== MÉTHODES : AVERTISSEMENTS ==========

    public function getWarningCount(): int
    {
        return $this->warningCount;
    }

    public function setWarningCount(int $count): static
    {
        $this->warningCount = $count;
        return $this;
    }

    public function incrementWarningCount(): static
    {
        $this->warningCount++;
        return $this;
    }

    /**
     * @return Collection<int, Warning>
     */
    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    public function addWarning(Warning $warning): static
    {
        if (!$this->warnings->contains($warning)) {
            $this->warnings->add($warning);
            $warning->setUser($this);
        }
        return $this;
    }

    public function removeWarning(Warning $warning): static
    {
        if ($this->warnings->removeElement($warning)) {
            if ($warning->getUser() === $this) {
                $warning->setUser(null);
            }
        }
        return $this;
    }

    // ========== MÉTHODES : VÉRIFICATION EMAIL ==========

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function setIsEmailVerified(bool $isEmailVerified): static
    {
        $this->isEmailVerified = $isEmailVerified;
        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): static
    {
        $this->emailVerificationToken = $emailVerificationToken;
        return $this;
    }

    public function getEmailVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailVerificationTokenExpiresAt;
    }

    public function setEmailVerificationTokenExpiresAt(?\DateTimeImmutable $emailVerificationTokenExpiresAt): static
    {
        $this->emailVerificationTokenExpiresAt = $emailVerificationTokenExpiresAt;
        return $this;
    }

    public function generateEmailVerificationToken(): static
    {
        $this->emailVerificationToken = bin2hex(random_bytes(32));
        $this->emailVerificationTokenExpiresAt = new \DateTimeImmutable('+24 hours');
        return $this;
    }

    public function isEmailVerificationTokenValid(): bool
    {
        if (!$this->emailVerificationToken || !$this->emailVerificationTokenExpiresAt) {
            return false;
        }
        return $this->emailVerificationTokenExpiresAt > new \DateTimeImmutable();
    }

    public function verifyEmail(): static
    {
        $this->isEmailVerified = true;
        $this->emailVerificationToken = null;
        $this->emailVerificationTokenExpiresAt = null;
        $this->activate();
        return $this;
    }

    // ========== MÉTHODES : RÉINITIALISATION MOT DE PASSE ==========

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): static
    {
        $this->passwordResetToken = $passwordResetToken;
        return $this;
    }

    public function getPasswordResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetTokenExpiresAt;
    }

    public function setPasswordResetTokenExpiresAt(?\DateTimeImmutable $passwordResetTokenExpiresAt): static
    {
        $this->passwordResetTokenExpiresAt = $passwordResetTokenExpiresAt;
        return $this;
    }

    public function generatePasswordResetToken(): static
    {
        $this->passwordResetToken = bin2hex(random_bytes(32));
        $this->passwordResetTokenExpiresAt = new \DateTimeImmutable('+1 hour');
        return $this;
    }

    public function isPasswordResetTokenValid(): bool
    {
        if (!$this->passwordResetToken || !$this->passwordResetTokenExpiresAt) {
            return false;
        }
        return $this->passwordResetTokenExpiresAt > new \DateTimeImmutable();
    }

    public function resetPassword(string $newPassword): static
    {
        $this->password = $newPassword;
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiresAt = null;
        return $this;
    }

    // ========== MÉTHODES : CONNEXION ==========

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function updateLastLogin(): static
    {
        $this->lastLoginAt = new \DateTimeImmutable();
        return $this;
    }

    public function getRememberMeToken(): ?string
    {
        return $this->rememberMeToken;
    }

    public function setRememberMeToken(?string $rememberMeToken): static
    {
        $this->rememberMeToken = $rememberMeToken;
        return $this;
    }

    // ========== MÉTHODES : TIMESTAMPS ==========

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

    // ========== MÉTHODES : STATIONS ==========

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

    // ========== MÉTHODES : BADGES ==========

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
        $this->displayedBadges = array_slice($displayedBadges, 0, 3);
        return $this;
    }

    public function addDisplayedBadge(int $badgeId): static
    {
        if (!in_array($badgeId, $this->displayedBadges, true) && count($this->displayedBadges) < 3) {
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

    // ========== MÉTHODES : SÉRIALISATION ==========

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password' => $this->password,
            'roles' => $this->roles,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->roles = $data['roles'];
    }
}