<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class BadgeFlashSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var Session $session */
        $session = $this->requestStack->getSession();
        
        // Vérifier s'il y a des badges en attente
        $pendingBadges = $session->get('pending_badges', []);
        
        if (!empty($pendingBadges)) {
            // Utiliser FlashBag comme pour le login
            foreach ($pendingBadges as $badge) {
                $session->getFlashBag()->add(
                    'badge', 
                    '🏆 Nouveau badge débloqué : ' . $badge['icon'] . ' ' . $badge['name']
                );
            }
            
            // Supprimer les badges en attente
            $session->remove('pending_badges');
        }
    }
}