<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Doctrine\ORM\EntityManagerInterface;

class CheckVerifiedUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['onCheckPassport', -10],
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $user = $passport->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Vérifier si l'email est validé
        if (!$user->isEmailVerified()) {
            throw new CustomUserMessageAuthenticationException(
                'Veuillez vérifier votre email avant de vous connecter'
            );
        }

        // Vérifier le statut du compte
        if ($user->isSuspended()) {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte a été suspendu. Contactez le support.'
            );
        }

        if ($user->isDeleted()) {
            throw new CustomUserMessageAuthenticationException(
                'Ce compte a été supprimé.'
            );
        }

        // Mettre à jour la dernière connexion
        $user->updateLastLogin();
        $this->entityManager->flush();
    }
}