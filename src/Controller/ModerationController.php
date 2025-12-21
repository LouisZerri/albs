<?php

namespace App\Controller;

use App\Entity\LineDiscussionReply;
use App\Entity\User;
use App\Entity\Warning;
use App\Repository\LineDiscussionReplyRepository;
use App\Repository\UserRepository;
use App\Repository\WarningRepository;
use App\Service\ModerationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderation')]
#[IsGranted('ROLE_MODERATOR')] // Accessible aux modos ET admins
class ModerationController extends AbstractController
{
    #[Route('', name: 'app_moderation')]
    public function index(
        LineDiscussionReplyRepository $replyRepository,
        WarningRepository $warningRepository,
        UserRepository $userRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        // Query pour tous les messages récents
        $queryBuilder = $replyRepository->createQueryForRecentReplies();

        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        // IDs des messages déjà avertis
        $postIds = array_map(
            fn($message) => $message->getId(),
            iterator_to_array($pagination)
        );
        $warnedPostIds = $postIds
            ? $warningRepository->findWarnedPostIds($postIds)
            : [];

        // Statistiques
        $warnedUsersCount = $userRepository->count(['warningCount' => 1]);
        $bannedUsersCount = $userRepository->count(['accountStatus' => 'banned']);
        $todayMessagesCount = $replyRepository->countMessagesToday();

        return $this->render('moderation/index.html.twig', [
            'pagination' => $pagination,
            'warnedPostIds' => $warnedPostIds,
            'warnedUsersCount' => $warnedUsersCount,
            'bannedUsersCount' => $bannedUsersCount,
            'todayMessagesCount' => $todayMessagesCount,
        ]);
    }

    #[Route('/warn-user', name: 'app_moderation_warn_user', methods: ['POST'])]
    public function warnUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ModerationEmailService $moderationEmailService
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        if (!$this->isCsrfTokenValid('warn_user', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_moderation');
        }

        $userId = $request->request->get('user_id');
        $postId = $request->request->get('post_id');
        $reason = $request->request->get('reason');

        $user = $userRepository->find($userId);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_moderation');
        }

        // Vérifications de permissions
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        assert($currentUser instanceof User);


        // Personne ne peut avertir un admin
        if ($user->isAdmin()) {
            $this->addFlash('error', 'Vous ne pouvez pas avertir un administrateur.');
            return $this->redirectToRoute('app_moderation');
        }

        // Un modo ne peut pas avertir un autre modo (seul l'admin peut)
        if ($user->isModerator() && !$currentUser->isAdmin()) {
            $this->addFlash('error', 'Seul un administrateur peut avertir un modérateur.');
            return $this->redirectToRoute('app_moderation');
        }

        // Créer l'avertissement
        $warning = new Warning();
        $warning->setUser($user);
        $warning->setModerator($currentUser);
        $warning->setReason($reason);
        $warning->setRelatedPostId($postId);
        $warning->setRelatedPostType('discussion_reply');

        // Incrémenter le compteur d'avertissements
        $user->incrementWarningCount();

        $entityManager->persist($warning);

        // Si 3 avertissements → bannissement automatique
        if ($user->getWarningCount() >= 3) {
            $user->ban();

            // Supprimer tous les messages de l'utilisateur
            $replyRepository = $entityManager->getRepository(LineDiscussionReply::class);
            $deletedCount = $replyRepository->deleteAllByUser($user);

            // Envoyer l'email de bannissement
            try {
                $moderationEmailService->sendBanEmail($user);
            } catch (\Exception $e) {
                error_log('Erreur envoi email bannissement : ' . $e->getMessage());
            }

            $this->addFlash('warning', "⛔ L'utilisateur {$user->getUsername()} a été banni automatiquement (3 avertissements). {$deletedCount} message(s) supprimé(s). Un email lui a été envoyé.");
        } else {
            // Envoyer l'email d'avertissement
            try {
                $moderationEmailService->sendWarningEmail($user, $warning);
            } catch (\Exception $e) {
                error_log('Erreur envoi email avertissement : ' . $e->getMessage());
            }

            $this->addFlash('success', "✅ Avertissement donné à {$user->getUsername()} ({$user->getWarningCount()}/3). Un email lui a été envoyé.");
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_moderation');
    }

    #[Route('/remove-warning/{id}', name: 'app_moderation_remove_warning', methods: ['POST'])]
    public function removeWarning(
        Warning $warning,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        if (!$this->isCsrfTokenValid('remove_warning_' . $warning->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_moderation');
        }

        $user = $warning->getUser();

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérifications de permissions
        if ($user->isAdmin()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier les avertissements d\'un administrateur.');
            return $this->redirectToRoute('app_profile_public', ['username' => $user->getUsername()]);
        }

        if ($user->isModerator() && !$currentUser->isAdmin()) {
            $this->addFlash('error', 'Seul un administrateur peut retirer l\'avertissement d\'un modérateur.');
            return $this->redirectToRoute('app_profile_public', ['username' => $user->getUsername()]);
        }

        // Décrémenter le compteur
        if ($user->getWarningCount() > 0) {
            $user->setWarningCount($user->getWarningCount() - 1);
        }

        // Supprimer l'avertissement
        $entityManager->remove($warning);
        $entityManager->flush();

        $this->addFlash('success', "✅ Avertissement retiré pour {$user->getUsername()} ({$user->getWarningCount()}/3).");

        return $this->redirectToRoute('app_profile_public', ['username' => $user->getUsername()]);
    }

    #[Route('/delete-post/{id}', name: 'app_moderation_delete_post', methods: ['POST'])]
    public function deletePost(
        int $id,
        EntityManagerInterface $entityManager,
        LineDiscussionReplyRepository $replyRepository
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $post = $replyRepository->find($id);
        if (!$post) {
            return $this->json(['error' => 'Message introuvable'], 404);
        }

        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', '✅ Message supprimé.');

        return $this->json(['success' => true]);
    }

    #[Route('/ban-user', name: 'app_moderation_ban_user', methods: ['POST'])]
    public function banUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ModerationEmailService $moderationEmailService
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        if (!$this->isCsrfTokenValid('ban_user', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_moderation');
        }

        $userId = $request->request->get('user_id');
        $reason = $request->request->get('reason');

        $user = $userRepository->find($userId);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_moderation');
        }

        // Vérifications de permissions
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        assert($currentUser instanceof User);

        // Personne ne peut bannir un admin
        if ($user->isAdmin()) {
            $this->addFlash('error', 'Vous ne pouvez pas bannir un administrateur.');
            return $this->redirectToRoute('app_moderation');
        }

        // Un modo ne peut pas bannir un autre modo (seul l'admin peut)
        if ($user->isModerator() && !$currentUser->isAdmin()) {
            $this->addFlash('error', 'Seul un administrateur peut bannir un modérateur.');
            return $this->redirectToRoute('app_moderation');
        }

        // Créer un avertissement pour tracer le bannissement
        $warning = new Warning();
        $warning->setUser($user);
        $warning->setModerator($currentUser);
        $warning->setReason("BANNISSEMENT DIRECT : " . $reason);
        $warning->setRelatedPostType('direct_ban');

        $entityManager->persist($warning);

        // Bannir l'utilisateur
        $user->ban();
        $user->setWarningCount(3); // Pour indiquer que c'est un ban définitif

        $replyRepository = $entityManager->getRepository(LineDiscussionReply::class);
        $deletedCount = $replyRepository->deleteAllByUser($user);

        // Envoyer l'email de bannissement
        try {
            $moderationEmailService->sendBanEmail($user);
        } catch (\Exception $e) {
            // Ne pas bloquer l'action si l'email échoue
        }

        $entityManager->flush();

        $this->addFlash('success', "🚫 L'utilisateur {$user->getUsername()} a été banni définitivement. {$deletedCount} message(s) supprimé(s). Un email lui a été envoyé.");

        return $this->redirectToRoute('app_moderation');
    }
}
