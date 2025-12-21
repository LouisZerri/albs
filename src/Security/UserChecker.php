<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isBanned()) {
            throw new CustomUserMessageAccountStatusException('🚫 Votre compte a été banni.');
        }

        if ($user->isDeleted()) {
            throw new CustomUserMessageAccountStatusException('Ce compte a été supprimé.');
        }

        if (!$user->isEmailVerified()) {
            throw new CustomUserMessageAccountStatusException('Veuillez vérifier votre email avant de vous connecter.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Vérifications après authentification si nécessaire
    }
}