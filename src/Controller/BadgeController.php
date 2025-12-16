<?php

namespace App\Controller;

use App\Repository\BadgeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/badges')]
class BadgeController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'app_badge_toggle', methods: ['POST'])]
    public function toggle(
        int $id,
        BadgeRepository $badgeRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $badge = $badgeRepository->find($id);
        
        if (!$badge) {
            return new JsonResponse(['error' => 'Badge non trouvé'], 404);
        }

        // Vérifier que l'utilisateur possède ce badge
        if (!$user->getBadges()->contains($badge)) {
            return new JsonResponse(['error' => 'Badge non débloqué'], 403);
        }

        $displayedBadges = $user->getDisplayedBadges();
        
        if (in_array($id, $displayedBadges)) {
            // Retirer le badge
            $user->removeDisplayedBadge($id);
            $displayed = false;
        } else {
            // Limiter à 3 badges affichés maximum
            if (count($displayedBadges) >= 3) {
                return new JsonResponse([
                    'error' => 'Vous ne pouvez afficher que 3 badges maximum'
                ], 400);
            }
            $user->addDisplayedBadge($id);
            $displayed = true;
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'displayed' => $displayed,
            'badgeId' => $id
        ]);
    }
}