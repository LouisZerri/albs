<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BannedUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 6], // Priorité haute
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        if ($user->isBanned()) {
            $route = $event->getRequest()->attributes->get('_route');
            
            // Autoriser logout et login
            if (in_array($route, ['app_logout', 'app_login', '_wdt', '_profiler'], true)) {
                return;
            }

            // Déconnecter
            $this->tokenStorage->setToken(null);
            
            /** @var Session $session */
            $session = $this->requestStack->getSession();
            $session->invalidate();
            $session->getFlashBag()->add('error', 'Votre compte a été banni.');
            
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));
        }
    }
}