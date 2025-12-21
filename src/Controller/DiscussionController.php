<?php

namespace App\Controller;

use App\Entity\LineDiscussion;
use App\Entity\LineDiscussionReply;
use App\Entity\User;
use App\Form\DiscussionType;
use App\Form\ReplyType;
use App\Repository\LineDiscussionReplyRepository;
use App\Repository\LineRepository;
use App\Repository\WarningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/discussion')]
class DiscussionController extends AbstractController
{
    #[Route('/new/{lineNumber}', name: 'app_discussion_new')]
    #[IsGranted('ROLE_USER')]
    public function new(
        string $lineNumber,
        Request $request,
        LineRepository $lineRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isBanned()) {
            $this->addFlash('error', '🚫 Votre compte est banni. Vous ne pouvez pas créer de discussion.');
            return $this->redirectToRoute('app_forum');
        }

        $line = $lineRepository->findOneBy(['number' => $lineNumber]);
        if (!$line) {
            throw $this->createNotFoundException('Ligne introuvable');
        }

        $discussion = new LineDiscussion();
        $discussion->setLine($line);
        $discussion->setAuthor($user);

        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($discussion);
            $entityManager->flush();

            $this->addFlash('success', '✅ Discussion créée avec succès !');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        return $this->render('discussion/new.html.twig', [
            'form' => $form,
            'line' => $line,
        ]);
    }

    #[Route('/{id}', name: 'app_discussion_show', requirements: ['id' => '\d+'])]
    public function show(
        LineDiscussion $discussion,
        Request $request,
        EntityManagerInterface $entityManager,
        WarningRepository $warningRepository,
        LineDiscussionReplyRepository $replyRepository,
        PaginatorInterface $paginator
    ): Response {
        // Incrémenter le compteur de vues UNIQUEMENT si ce n'est pas l'auteur
        $currentUser = $this->getUser();
        $shouldCount = true;

        if ($currentUser instanceof User && $currentUser->getId() === $discussion->getAuthor()->getId()) {
            $shouldCount = false;
        }

        if ($shouldCount) {
            $discussion->incrementViewCount();
            $entityManager->flush();
        }

        $reply = new LineDiscussionReply();
        $reply->setDiscussion($discussion);

        $form = null;
        if ($this->getUser()) {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user->isBanned() && !$discussion->isLocked()) {
                $reply->setAuthor($user);
                $form = $this->createForm(ReplyType::class, $reply);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $discussion->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->persist($reply);
                    $entityManager->flush();

                    // Calculer la dernière page
                    $totalReplies = count($discussion->getReplies());
                    $lastPage = (int) ceil($totalReplies / 15);

                    // Redirection simple vers la dernière page
                    return $this->redirectToRoute('app_discussion_show', [
                        'id' => $discussion->getId(),
                        'page' => $lastPage,
                    ]);
                }
            }
        }

        // Pagination des réponses
        $queryBuilder = $replyRepository->createQueryForDiscussionReplies($discussion);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        // Récupérer tous les IDs de messages déjà avertis
        $warnedPostIds = [];
        foreach ($pagination as $replyItem) {
            $warnings = $warningRepository->findBy([
                'user' => $replyItem->getAuthor(),
                'relatedPostId' => $replyItem->getId()
            ]);
            if (!empty($warnings)) {
                $warnedPostIds[] = $replyItem->getId();
            }
        }

        return $this->render('discussion/show.html.twig', [
            'discussion' => $discussion,
            'pagination' => $pagination,
            'form' => $form?->createView(),
            'warnedPostIds' => $warnedPostIds,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_discussion_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(
        LineDiscussion $discussion,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $discussion->getId(), $request->request->get('_token'))) {
            $lineNumber = $discussion->getLine()->getNumber();
            $entityManager->remove($discussion);
            $entityManager->flush();

            $this->addFlash('success', '✅ Discussion supprimée.');
            return $this->redirectToRoute('app_forum_line', ['lineNumber' => $lineNumber]);
        }

        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }

    #[Route('/{id}/lock', name: 'app_discussion_lock', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function lock(
        LineDiscussion $discussion,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('lock' . $discussion->getId(), $request->request->get('_token'))) {
            $discussion->setIsLocked(!$discussion->isLocked());
            $entityManager->flush();

            $message = $discussion->isLocked() ? '🔒 Discussion verrouillée.' : '🔓 Discussion déverrouillée.';
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }

    #[Route('/{id}/pin', name: 'app_discussion_pin', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function pin(
        LineDiscussion $discussion,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('pin' . $discussion->getId(), $request->request->get('_token'))) {
            $discussion->setIsPinned(!$discussion->isPinned());
            $entityManager->flush();

            $message = $discussion->isPinned() ? '📌 Discussion épinglée.' : '📍 Discussion désépinglée.';
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }

    #[Route('/reply/{id}/delete', name: 'app_reply_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function deleteReply(
        LineDiscussionReply $reply,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete_reply' . $reply->getId(), $request->request->get('_token'))) {
            $discussionId = $reply->getDiscussion()->getId();
            $entityManager->remove($reply);
            $entityManager->flush();

            $this->addFlash('success', '✅ Message supprimé.');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussionId]);
        }

        return $this->redirectToRoute('app_discussion_show', ['id' => $reply->getDiscussion()->getId()]);
    }
}
