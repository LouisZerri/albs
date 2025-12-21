<?php

namespace App\Controller;

use App\Repository\LineRepository;
use App\Repository\LineDiscussionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forum')]
class ForumController extends AbstractController
{
    #[Route('', name: 'app_forum')]
    public function index(
        Request $request,
        LineRepository $lineRepository,
        LineDiscussionRepository $discussionRepository
    ): Response {
        $lines = $lineRepository->findAll();
        $query = $request->query->get('q', '');

        // Si recherche
        if ($query) {
            $searchResults = $discussionRepository->search($query);

            return $this->render('forum/index.html.twig', [
                'lines' => $lines,
                'totalDiscussions' => $discussionRepository->count([]),
                'recentDiscussions' => $searchResults,
                'searchQuery' => $query,
            ]);
        }

        // Statistiques globales
        $totalDiscussions = $discussionRepository->count([]);
        $recentDiscussions = $discussionRepository->findRecentWithRelations(10);

        return $this->render('forum/index.html.twig', [
            'lines' => $lines,
            'totalDiscussions' => $totalDiscussions,
            'recentDiscussions' => $recentDiscussions,
            'searchQuery' => '',
        ]);
    }

    #[Route('/line/{lineNumber}', name: 'app_forum_line')]
    public function lineDiscussions(
        string $lineNumber,
        LineRepository $lineRepository,
        LineDiscussionRepository $discussionRepository
    ): Response {
        $line = $lineRepository->findOneBy(['number' => $lineNumber]);

        if (!$line) {
            throw $this->createNotFoundException('Ligne introuvable');
        }

        // Discussions de la ligne (épinglées en premier)
        $discussions = $discussionRepository->findByLineOrdered($line);

        return $this->render('forum/line.html.twig', [
            'line' => $line,
            'discussions' => $discussions,
        ]);
    }
}
