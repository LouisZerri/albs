<?php

namespace App\Controller;

use App\Form\ProfileEditFormType;
use App\Repository\LineRepository;
use App\Repository\UserStationRepository;
use App\Repository\BadgeRepository;
use App\Service\BadgeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(
        UserStationRepository $userStationRepository,
        LineRepository $lineRepository,
        BadgeRepository $badgeRepository
    ): Response {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // OPTIMISATION : Récupérer les UserStations avec les Stations en une seule requête
        $userStations = $userStationRepository->findByUserWithStations($user);

        // Calculer les statistiques globales
        $totalPassed = 0;
        $totalStopped = 0;

        foreach ($userStations as $userStation) {
            if ($userStation->isPassed()) {
                $totalPassed++;
            }
            if ($userStation->isStopped()) {
                $totalStopped++;
            }
        }

        // OPTIMISATION : Récupérer les lignes avec leurs stations en une seule requête
        $lines = $lineRepository->findAllWithStations();

        // Créer un index des UserStations par Station ID pour un accès rapide
        $userStationsByStationId = [];
        foreach ($userStations as $userStation) {
            $userStationsByStationId[$userStation->getStation()->getId()] = $userStation;
        }

        // Statistiques par ligne
        $lineStats = [];

        foreach ($lines as $line) {
            $stations = $line->getStations();
            $totalStations = count($stations);
            $passed = 0;
            $stopped = 0;

            foreach ($stations as $station) {
                $stationId = $station->getId();

                if (isset($userStationsByStationId[$stationId])) {
                    $userStation = $userStationsByStationId[$stationId];

                    if ($userStation->isPassed()) {
                        $passed++;
                    }
                    if ($userStation->isStopped()) {
                        $stopped++;
                    }
                }
            }

            if ($passed > 0 || $stopped > 0) {
                $lineStats[] = [
                    'line' => $line,
                    'total' => $totalStations,
                    'passed' => $passed,
                    'stopped' => $stopped,
                    'passedPercentage' => $totalStations > 0 ? round(($passed / $totalStations) * 100, 1) : 0,
                    'stoppedPercentage' => $totalStations > 0 ? round(($stopped / $totalStations) * 100, 1) : 0,
                ];
            }
        }

        $allBadges = $badgeRepository->findAll();
        $userBadges = $user->getBadges()->toArray();
        $userBadgeIds = array_map(fn($badge) => $badge->getId(), $userBadges);

        $badgesStatus = [];
        foreach ($allBadges as $badge) {
            $unlocked = in_array($badge->getId(), $userBadgeIds);

            // Calcul de la progression (sans requêtes supplémentaires)
            $progress = 0;
            if (!$unlocked) {
                $progress = $this->calculateBadgeProgressOptimized(
                    $badge,
                    $totalPassed,
                    $totalStopped,
                    $lineStats,
                    $userStations
                );
            }

            $badgesStatus[] = [
                'badge' => $badge,
                'unlocked' => $unlocked,
                'progress' => $progress,
            ];
        }

        // Trier : débloqués en premier
        usort($badgesStatus, function ($a, $b) {
            if ($a['unlocked'] === $b['unlocked']) {
                return $b['progress'] <=> $a['progress'];
            }
            return $b['unlocked'] <=> $a['unlocked'];
        });

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'totalPassed' => $totalPassed,
            'totalStopped' => $totalStopped,
            'lineStats' => $lineStats,
            'badgesStatus' => $badgesStatus,
            'newBadges' => [], // Pas de nouveaux badges à afficher ici
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileEditFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de l'avatar
            $avatarFile = $form->get('avatarFile')->getData();

            if ($avatarFile) {
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();

                try {
                    $avatarFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                        $newFilename
                    );

                    // Supprimer l'ancien avatar s'il existe
                    if ($user->getAvatar()) {
                        $oldAvatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatar();
                        if (file_exists($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                        }
                    }

                    $user->setAvatar($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', '✓ Votre profil a été mis à jour avec succès !');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/profile/delete-avatar', name: 'app_profile_delete_avatar', methods: ['POST'])]
    public function deleteAvatar(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete-avatar', $request->request->get('_token'))) {
            if ($user->getAvatar()) {
                $avatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatar();
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
                $user->setAvatar(null);
                $entityManager->flush();

                $this->addFlash('success', '✓ Votre photo de profil a été supprimée.');
            }
        }

        return $this->redirectToRoute('app_profile_edit');
    }

    #[Route('/profile/delete-account', name: 'app_profile_delete_account', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete-account', $request->request->get('_token'))) {
            // Supprimer l'avatar s'il existe
            if ($user->getAvatar()) {
                $avatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatar();
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
            }

            // Déconnecter l'utilisateur
            $request->getSession()->invalidate();
            $this->container->get('security.token_storage')->setToken(null);

            // Supprimer l'utilisateur
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été supprimé avec succès.');

            return $this->redirectToRoute('app_home');
        }

        return $this->redirectToRoute('app_profile');
    }

    /**
     * Calcul optimisé de la progression d'un badge (sans requêtes DB supplémentaires)
     */
    private function calculateBadgeProgressOptimized(
        $badge,
        int $totalPassed,
        int $totalStopped,
        array $lineStats,
        array $userStations
    ): int {
        $criteria = $badge->getCriteria();

        // Badge basé sur stations visitées
        if (isset($criteria['stopped'])) {
            return min(100, round(($totalStopped / $criteria['stopped']) * 100));
        }

        // Badge basé sur stations passées
        if (isset($criteria['passed'])) {
            return min(100, round(($totalPassed / $criteria['passed']) * 100));
        }

        // Badge basé sur lignes complétées
        if (isset($criteria['line_complete'])) {
            $completedLines = 0;
            foreach ($lineStats as $stat) {
                if ($stat['stopped'] === $stat['total'] && $stat['total'] > 0) {
                    $completedLines++;
                }
            }
            return min(100, round(($completedLines / $criteria['line_complete']) * 100));
        }

        // Badge toutes les stations
        if (isset($criteria['all_stations'])) {
            // Tu devras passer le nombre total de stations, ou le calculer
            $totalStations = 309; // Nombre approximatif de stations du métro parisien
            return min(100, round(($totalStopped / $totalStations) * 100));
        }

        // Badge marathonien : stations en une journée
        if (isset($criteria['daily_marathon'])) {
            $stationsByDay = [];
            foreach ($userStations as $userStation) {
                $stoppedAt = $userStation->getFirstStoppedAt();
                if ($stoppedAt) {
                    $day = $stoppedAt->format('Y-m-d');
                    if (!isset($stationsByDay[$day])) {
                        $stationsByDay[$day] = 0;
                    }
                    $stationsByDay[$day]++;
                }
            }
            $maxInOneDay = !empty($stationsByDay) ? max($stationsByDay) : 0;
            return min(100, round(($maxInOneDay / $criteria['daily_marathon']) * 100));
        }

        // Badge fidèle de la ligne : stations passées sur une même ligne
        if (isset($criteria['line_passed_same'])) {
            $maxPassedOnLine = 0;
            foreach ($lineStats as $stat) {
                $maxPassedOnLine = max($maxPassedOnLine, $stat['passed']);
            }
            return min(100, round(($maxPassedOnLine / $criteria['line_passed_same']) * 100));
        }

        // Badges temporels (noctambule, lève-tôt)
        if (isset($criteria['night_visit']) || isset($criteria['early_visit'])) {
            foreach ($userStations as $userStation) {
                $stoppedAt = $userStation->getFirstStoppedAt();
                if ($stoppedAt) {
                    $hour = (int) $stoppedAt->format('H');
                    if ($hour >= 0 && $hour < 6) {
                        return 100; // Badge débloqué dès qu'on a une visite la nuit
                    }
                }
            }
            return 0;
        }

        // Badge compte créé
        if (isset($criteria['account_created'])) {
            return 100;
        }

        return 0;
    }
}
