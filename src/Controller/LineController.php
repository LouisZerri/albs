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
        $lines = $lineRepository->findAllWithStations();

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

            if ($branch === 'fork') {
                $mainStations[] = $station;
                $hasFork = true;
            } elseif (in_array($branch, ['villejuif', 'ivry', 'saint-denis', 'asnieres'])) {
                if (!isset($branchStations[$branch])) {
                    $branchStations[$branch] = [];
                }
                $branchStations[$branch][] = $station;
            } else {
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

        // Nouveau format : passed et stopped envoyés directement
        if (isset($data['passed']) && isset($data['stopped'])) {
            $newPassed = (bool) $data['passed'];
            $newStopped = (bool) $data['stopped'];

            if ($newPassed && !$userStation->isPassed()) {
                $userStation->setFirstPassedAt($now);
            }
            $userStation->setPassed($newPassed);

            if ($newStopped && !$userStation->isStopped()) {
                $userStation->setFirstStoppedAt($now);
            }
            $userStation->setStopped($newStopped);
        }
        // Ancien format (rétrocompatibilité)
        elseif (isset($data['type'])) {
            $type = $data['type'];
            $checked = $data['checked'] ?? false;

            if ($type === 'passed') {
                $userStation->setPassed($checked);
                if ($checked && $userStation->getFirstPassedAt() === null) {
                    $userStation->setFirstPassedAt($now);
                }
                if (!$checked) {
                    $userStation->setStopped(false);
                }
            } elseif ($type === 'stopped') {
                $userStation->setStopped($checked);
                if ($checked && $userStation->getFirstStoppedAt() === null) {
                    $userStation->setFirstStoppedAt($now);
                }
                if ($checked) {
                    $userStation->setPassed(true);
                    if ($userStation->getFirstPassedAt() === null) {
                        $userStation->setFirstPassedAt($now);
                    }
                }
            }
        }

        $userStation->setUpdatedAt($now);
        $em->persist($userStation);
        $em->flush();

        // Vérifier et attribuer de nouveaux badges
        $newBadges = $badgeService->checkAndAwardBadges($user);

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