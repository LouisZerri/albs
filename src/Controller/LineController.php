<?php

namespace App\Controller;

use App\Repository\LineRepository;
use App\Repository\StationRepository;
use App\Repository\UserStationRepository;
use App\Service\BadgeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/lines')]
class LineController extends AbstractController
{
    #[Route('', name: 'app_lines')]
    public function index(LineRepository $lineRepository): Response
    {
        $lines = $lineRepository->findAll();

        return $this->render('line/index.html.twig', [
            'lines' => $lines,
        ]);
    }

    #[Route('/{id}', name: 'app_line_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        LineRepository $lineRepository,
        UserStationRepository $userStationRepository
    ): Response {
        $line = $lineRepository->find($id);

        if (!$line) {
            throw $this->createNotFoundException('Ligne non trouvée');
        }

        $user = $this->getUser();
        $allStations = $line->getStations();

        // Séparer les stations par branche
        $mainStations = [];
        $branchStations = [];
        $hasFork = false;

        foreach ($allStations as $station) {
            $branch = $station->getBranch();

            // Pour les lignes 7 et 13 : gérer les fourches
            if ($branch === 'fork') {
                $mainStations[] = $station;
                $hasFork = true;
            } elseif (in_array($branch, ['villejuif', 'ivry', 'saint-denis', 'asnieres'])) {
                if (!isset($branchStations[$branch])) {
                    $branchStations[$branch] = [];
                }
                $branchStations[$branch][] = $station;
            } else {
                // Toutes les autres stations (y compris celles avec branch 'direction' ou 'retour' pour la 7bis et 10)
                $mainStations[] = $station;
            }
        }

        // Trier les stations par position
        usort($mainStations, function ($a, $b) {
            return $a->getPosition() <=> $b->getPosition();
        });

        foreach ($branchStations as $branch => $stations) {
            usort($branchStations[$branch], function ($a, $b) {
                return $a->getPosition() <=> $b->getPosition();
            });
        }

        // Récupérer les stations marquées par l'utilisateur
        $userStations = [];
        if ($user) {
            $userStationsData = $userStationRepository->findBy(['user' => $user]);
            foreach ($userStationsData as $userStation) {
                $userStations[$userStation->getStation()->getId()] = $userStation;
            }
        }

        // Calculer les statistiques
        $totalStations = count($allStations);
        $passedCount = 0;
        $stoppedCount = 0;

        foreach ($allStations as $station) {
            if (isset($userStations[$station->getId()])) {
                if ($userStations[$station->getId()]->isPassed()) {
                    $passedCount++;
                }
                if ($userStations[$station->getId()]->isStopped()) {
                    $stoppedCount++;
                }
            }
        }

        $passedPercentage = $totalStations > 0 ? round(($passedCount / $totalStations) * 100, 1) : 0;
        $stoppedPercentage = $totalStations > 0 ? round(($stoppedCount / $totalStations) * 100, 1) : 0;

        return $this->render('line/show.html.twig', [
            'line' => $line,
            'mainStations' => $mainStations,
            'branchStations' => $branchStations,
            'hasFork' => $hasFork,
            'userStations' => $userStations,
            'stats' => [
                'total' => $totalStations,
                'passed' => $passedCount,
                'stopped' => $stoppedCount,
                'passedPercentage' => $passedPercentage,
                'stoppedPercentage' => $stoppedPercentage,
            ],
        ]);
    }

    #[Route('/{lineId}/station/{stationId}/toggle', name: 'app_station_toggle', methods: ['POST'])]
    public function toggleStation(
        int $lineId,
        int $stationId,
        Request $request,
        StationRepository $stationRepository,
        UserStationRepository $userStationRepository,
        EntityManagerInterface $em,
        BadgeService $badgeService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $station = $stationRepository->find($stationId);
        if (!$station) {
            return new JsonResponse(['error' => 'Station non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? null;
        $checked = $data['checked'] ?? false;
        $now = new \DateTimeImmutable();

        // Trouver ou créer UserStation
        $userStation = $userStationRepository->findOneBy([
            'user' => $user,
            'station' => $station,
        ]);

        if (!$userStation) {
            $userStation = new \App\Entity\UserStation();
            $userStation->setUser($user);
            $userStation->setStation($station);
            $userStation->setPassed(false);
            $userStation->setStopped(false);
        }

        if ($type === 'passed') {
            $userStation->setPassed($checked);

            if ($checked && $userStation->getFirstPassedAt() === null) {
                $userStation->setFirstPassedAt($now);
            }

            // RÈGLE : Si on décoche Passé, décocher automatiquement Visité
            if (!$checked) {
                $userStation->setStopped(false);
            }
        } elseif ($type === 'stopped') {
            $userStation->setStopped($checked);

            if ($checked && $userStation->getFirstStoppedAt() === null) {
                $userStation->setFirstStoppedAt($now);
            }

            // RÈGLE : Si on coche Visité, cocher automatiquement Passé
            if ($checked) {
                $userStation->setPassed(true);
                if ($userStation->getFirstPassedAt() === null) {
                    $userStation->setFirstPassedAt($now);
                }
            }
        }

        $userStation->setUpdatedAt($now);
        $em->persist($userStation);
        $em->flush();

        // Vérifier et attribuer de nouveaux badges
        $newBadges = $badgeService->checkAndAwardBadges($user);

        // Stocker les badges dans la session pour les afficher après le reload
        if (!empty($newBadges)) {
            $session = $request->getSession();
            $pendingBadges = $session->get('pending_badges', []);

            foreach ($newBadges as $badge) {
                $pendingBadges[] = [
                    'icon' => $badge->getIcon(),
                    'name' => $badge->getName(),
                ];
            }

            $session->set('pending_badges', $pendingBadges);
        }

        // Préparer les données pour le JSON
        $badgesData = [];
        foreach ($newBadges as $badge) {
            $badgesData[] = [
                'id' => $badge->getId(),
                'name' => $badge->getName(),
                'icon' => $badge->getIcon(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'passed' => $userStation->isPassed(),
            'stopped' => $userStation->isStopped(),
            'newBadges' => $badgesData,
        ]);
    }
}
