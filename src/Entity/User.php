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
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email.')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Statut du compte : active, pending, suspended, deleted
     */
    #[ORM\Column(length: 20)]
    private string $accountStatus = 'pending';

    /**
     * Email vérifié ou non
     */
    #[ORM\Column]
    private bool $isEmailVerified = false;

    /**
     * Token de vérification d'email
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailVerificationToken = null;

    /**
     * Date d'expiration du token de vérification
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailVerificationTokenExpiresAt = null;

    /**
     * Token de réinitialisation de mot de passe
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passwordResetToken = null;

    /**
     * Date d'expiration du token de réinitialisation
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

    /**
     * Date de la dernière connexion
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    /**
     * Remember me token
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rememberMeToken = null;

    #[ORM\ManyToOne(targetEntity: Line::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Line $favoriteLine = null;

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

    public function __construct()
    {
        $this->userStations = new ArrayCollection();
        $this->badges = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ========== GETTERS / SETTERS BASIQUES ==========

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

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantir que chaque utilisateur a au moins ROLE_USER
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
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
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
        // Si vous stockez des données temporaires sensibles sur l'utilisateur, effacez-les ici
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

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // ========== STATUT DU COMPTE ==========

    public function getAccountStatus(): string
    {
        return $this->accountStatus;
    }

    public function setAccountStatus(string $accountStatus): static
    {
        $allowedStatuses = ['active', 'pending', 'suspended', 'deleted'];
        if (!in_array($accountStatus, $allowedStatuses)) {
            throw new \InvalidArgumentException('Statut de compte invalide');
        }
        $this->accountStatus = $accountStatus;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->accountStatus === 'active';
    }

    public function isPending(): bool
    {
        return $this->accountStatus === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->accountStatus === 'suspended';
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

    public function suspend(): static
    {
        $this->accountStatus = 'suspended';
        return $this;
    }

    public function markAsDeleted(): static
    {
        $this->accountStatus = 'deleted';
        return $this;
    }

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

    /**
     * Génère un token de vérification d'email (valide 24h)
     */
    public function generateEmailVerificationToken(): static
    {
        $this->emailVerificationToken = bin2hex(random_bytes(32));
        $this->emailVerificationTokenExpiresAt = new \DateTimeImmutable('+24 hours');
        return $this;
    }

    /**
     * Vérifie si le token de vérification est valide
     */
    public function isEmailVerificationTokenValid(): bool
    {
        if (!$this->emailVerificationToken || !$this->emailVerificationTokenExpiresAt) {
            return false;
        }
        return $this->emailVerificationTokenExpiresAt > new \DateTimeImmutable();
    }

    /**
     * Valide l'email (après clic sur le lien)
     */
    public function verifyEmail(): static
    {
        $this->isEmailVerified = true;
        $this->emailVerificationToken = null;
        $this->emailVerificationTokenExpiresAt = null;
        
        // Si le compte était en attente, l'activer
        if ($this->isPending()) {
            $this->activate();
        }
        
        return $this;
    }

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

    /**
     * Génère un token de réinitialisation de mot de passe (valide 1h)
     */
    public function generatePasswordResetToken(): static
    {
        $this->passwordResetToken = bin2hex(random_bytes(32));
        $this->passwordResetTokenExpiresAt = new \DateTimeImmutable('+1 hour');
        return $this;
    }

    /**
     * Vérifie si le token de réinitialisation est valide
     */
    public function isPasswordResetTokenValid(): bool
    {
        if (!$this->passwordResetToken || !$this->passwordResetTokenExpiresAt) {
            return false;
        }
        return $this->passwordResetTokenExpiresAt > new \DateTimeImmutable();
    }

    /**
     * Réinitialise le mot de passe et supprime le token
     */
    public function resetPassword(string $newPassword): static
    {
        $this->password = $newPassword;
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiresAt = null;
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
        $this->displayedBadges = array_slice($displayedBadges, 0, 3); // Max 3
        return $this;
    }

    public function addDisplayedBadge(int $badgeId): static
    {
        if (!in_array($badgeId, $this->displayedBadges) && count($this->displayedBadges) < 3) {
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