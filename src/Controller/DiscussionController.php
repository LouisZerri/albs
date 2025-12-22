<?php

namespace App\Controller;

use App\Entity\ForumImage;
use App\Entity\LineDiscussion;
use App\Entity\LineDiscussionReply;
use App\Entity\User;
use App\Form\DiscussionType;
use App\Form\ReplyType;
use App\Repository\LineDiscussionReplyRepository;
use App\Repository\LineDiscussionRepository;
use App\Repository\LineRepository;
use App\Repository\WarningRepository;
use App\Service\ForumImageUploader;
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
        ForumImageUploader $imageUploader
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

            // Gérer les images uploadées
            $uploadedFiles = $request->files->get('images', []);
            foreach ($uploadedFiles as $file) {
                if ($file) {
                    $result = $imageUploader->upload($file);
                    if ($result['success']) {
                        $forumImage = new ForumImage();
                        $forumImage->setFilename($result['filename']);
                        $forumImage->setOriginalFilename($result['originalFilename']);
                        $forumImage->setFileSize($result['fileSize']);
                        $forumImage->setUploadedBy($user);
                        $forumImage->setDiscussion($discussion);
                        $entityManager->persist($forumImage);
                    } else {
                        $this->addFlash('warning', $result['error']);
                    }
                }
            }

            $entityManager->flush();

            $this->addFlash('success', '✅ Discussion créée avec succès !');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        return $this->render('discussion/new.html.twig', [
            'form' => $form,
            'line' => $line,
        ]);
    }

    #[Route('/new', name: 'app_discussion_new_general')]
    #[IsGranted('ROLE_USER')]
    public function newGeneral(
        Request $request,
        EntityManagerInterface $entityManager,
        ForumImageUploader $imageUploader
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isBanned()) {
            $this->addFlash('error', '🚫 Votre compte est banni. Vous ne pouvez pas créer de discussion.');
            return $this->redirectToRoute('app_forum');
        }

        $discussion = new LineDiscussion();
        $discussion->setAuthor($user);
        // Pas de ligne associée = discussion générale

        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($discussion);

            // Gérer les images uploadées
            $uploadedFiles = $request->files->get('images', []);
            foreach ($uploadedFiles as $file) {
                if ($file) {
                    $result = $imageUploader->upload($file);
                    if ($result['success']) {
                        $forumImage = new ForumImage();
                        $forumImage->setFilename($result['filename']);
                        $forumImage->setOriginalFilename($result['originalFilename']);
                        $forumImage->setFileSize($result['fileSize']);
                        $forumImage->setUploadedBy($user);
                        $forumImage->setDiscussion($discussion);
                        $entityManager->persist($forumImage);
                    } else {
                        $this->addFlash('warning', $result['error']);
                    }
                }
            }

            $entityManager->flush();

            $this->addFlash('success', '✅ Discussion créée avec succès !');
            return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
        }

        return $this->render('discussion/new_general.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_discussion_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        WarningRepository $warningRepository,
        LineDiscussionReplyRepository $replyRepository,
        PaginatorInterface $paginator,
        ForumImageUploader $imageUploader,
        LineDiscussionRepository $discussionRepository
    ): Response {
        
        // Charger la discussion avec ses relations
        $discussion = $discussionRepository->findWithRelations($id);

        if (!$discussion) {
            throw $this->createNotFoundException('Discussion introuvable');
        }

        // Incrémenter le compteur de vues UNIQUEMENT si ce n'est pas l'auteur
        // et si l'utilisateur n'a pas déjà vu cette discussion récemment
        $currentUser = $this->getUser();
        $shouldCount = true;

        if ($currentUser instanceof User && $currentUser->getId() === $discussion->getAuthor()->getId()) {
            $shouldCount = false;
        }

        // Vérifier si déjà vu dans cette session
        $viewedDiscussions = $request->getSession()->get('viewed_discussions', []);
        if (in_array($discussion->getId(), $viewedDiscussions)) {
            $shouldCount = false;
        }

        if ($shouldCount) {
            $discussion->incrementViewCount();
            $entityManager->flush();

            // Marquer comme vu dans la session
            $viewedDiscussions[] = $discussion->getId();
            $request->getSession()->set('viewed_discussions', $viewedDiscussions);
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

                    // Gérer les images uploadées
                    $uploadedFiles = $request->files->get('images', []);
                    foreach ($uploadedFiles as $file) {
                        if ($file) {
                            $result = $imageUploader->upload($file);
                            if ($result['success']) {
                                $forumImage = new ForumImage();
                                $forumImage->setFilename($result['filename']);
                                $forumImage->setOriginalFilename($result['originalFilename']);
                                $forumImage->setFileSize($result['fileSize']);
                                $forumImage->setUploadedBy($user);
                                $forumImage->setReply($reply);
                                $entityManager->persist($forumImage);
                            } else {
                                $this->addFlash('warning', $result['error']);
                            }
                        }
                    }

                    $entityManager->flush();

                    // Calculer la dernière page
                    $totalReplies = $replyRepository->count(['discussion' => $discussion]);
                    $lastPage = (int) ceil($totalReplies / 15);

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

        // Récupérer les IDs des réponses en une seule fois
        $replyIds = array_map(fn($reply) => $reply->getId(), iterator_to_array($pagination));
        $warnedPostIds = $replyIds ? $warningRepository->findWarnedPostIds($replyIds) : [];

        return $this->render('discussion/show.html.twig', [
            'discussion' => $discussion,
            'pagination' => $pagination,
            'form' => $form?->createView(),
            'warnedPostIds' => $warnedPostIds,
            'replyCount' => $pagination->getTotalItemCount(),
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
            $line = $discussion->getLine();
            $entityManager->remove($discussion);
            $entityManager->flush();

            $this->addFlash('success', '✅ Discussion supprimée.');
            
            if ($line) {
                return $this->redirectToRoute('app_forum_line', ['lineNumber' => $line->getNumber()]);
            }
            return $this->redirectToRoute('app_forum_general');
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
