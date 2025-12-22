<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Repository\LineDiscussionReplyRepository;
use App\Repository\LineDiscussionRepository;
use App\Repository\UserRepository;
use App\Repository\UserStationRepository;
use App\Repository\WarningRepository;
use App\Service\ModerationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin')]
    public function index(
        UserRepository $userRepository,
        LineDiscussionRepository $discussionRepository,
        LineDiscussionReplyRepository $replyRepository,
        UserStationRepository $userStationRepository,
        BadgeRepository $badgeRepository,
        WarningRepository $warningRepository
    ): Response {
        // Statistiques utilisateurs
        $totalUsers = $userRepository->count([]);
        $newUsersWeek = $userRepository->countNewUsersSince(new \DateTimeImmutable('-7 days'));
        $activeUsers = $userRepository->countActiveUsers();
        $bannedUsers = $userRepository->findBy(['accountStatus' => 'banned'], ['bannedAt' => 'DESC']);
        $moderators = $userRepository->findModerators();

        // Statistiques forum
        $totalDiscussions = $discussionRepository->count([]);
        $totalReplies = $replyRepository->count([]);

        // Stations les plus visitées
        $topStations = $userStationRepository->findMostVisitedStations(10);

        // Badges les plus débloqués
        $topBadges = $badgeRepository->findMostUnlockedBadges(10);

        // Derniers inscrits
        $latestUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 10);

        // Logs d'activité (derniers avertissements)
        $latestWarnings = $warningRepository->findLatestWithRelations(20);

        return $this->render('admin/index.html.twig', [
            'totalUsers' => $totalUsers,
            'newUsersWeek' => $newUsersWeek,
            'activeUsers' => $activeUsers,
            'bannedUsers' => $bannedUsers,
            'moderators' => $moderators,
            'totalDiscussions' => $totalDiscussions,
            'totalReplies' => $totalReplies,
            'topStations' => $topStations,
            'topBadges' => $topBadges,
            'latestUsers' => $latestUsers,
            'latestWarnings' => $latestWarnings,
        ]);
    }

    #[Route('/user/{id}/promote', name: 'app_admin_promote', methods: ['POST'])]
    public function promoteUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('promote_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin');
        }

        if ($user->isAdmin()) {
            $this->addFlash('error', 'Impossible de modifier un administrateur.');
            return $this->redirectToRoute('app_admin');
        }

        if ($user->isModerator()) {
            // Rétrograder
            $user->setRoles(['ROLE_USER']);
            $this->addFlash('success', "✅ {$user->getUsername()} a été rétrogradé en utilisateur.");
        } else {
            // Promouvoir
            $user->setRoles(['ROLE_MODERATOR']);
            $this->addFlash('success', "✅ {$user->getUsername()} a été promu modérateur.");
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/user/{id}/unban', name: 'app_admin_unban', methods: ['POST'])]
    public function unbanUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        ModerationEmailService $moderationEmailService,
    ): Response {
        if (!$this->isCsrfTokenValid('unban_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin');
        }

        if (!$user->isBanned()) {
            $this->addFlash('error', 'Cet utilisateur n\'est pas banni.');
            return $this->redirectToRoute('app_admin');
        }

        // Utiliser la nouvelle méthode unban()
        $user->unban();

        $entityManager->flush();

        // Envoyer l'email de débannissement
        try {
            $moderationEmailService->sendUnbanEmail($user);
        } catch (\Exception $e) {
            error_log('Erreur envoi email débannissement : ' . $e->getMessage());
        }

        $this->addFlash('success', "✅ {$user->getUsername()} a été débanni.");

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/search', name: 'app_admin_search', methods: ['GET'])]
    public function searchUser(
        Request $request,
        UserRepository $userRepository
    ): Response {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json(['users' => []]);
        }

        $users = $userRepository->searchByUsernameOrEmail($query, 10);

        $results = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'status' => $user->getAccountStatus(),
                'isModerator' => $user->isModerator(),
                'isAdmin' => $user->isAdmin(),
                'createdAt' => $user->getCreatedAt()->format('d/m/Y'),
                'profileUrl' => $this->generateUrl('app_profile_public', ['username' => $user->getUsername()]),
            ];
        }, $users);

        return $this->json(['users' => $results]);
    }
}