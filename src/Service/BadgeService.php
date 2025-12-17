<?php

namespace App\Service;

use App\Entity\Badge;
use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Repository\LineRepository;
use App\Repository\StationRepository;
use App\Repository\UserStationRepository;
use Doctrine\ORM\EntityManagerInterface;

class BadgeService
{
    public function __construct(
        private BadgeRepository $badgeRepository,
        private UserStationRepository $userStationRepository,
        private LineRepository $lineRepository,
        private StationRepository $stationRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Vérifie et attribue les badges à un utilisateur
     */
    public function checkAndAwardBadges(User $user): array
    {
        $newBadges = [];
        $allBadges = $this->badgeRepository->findAll();
        $userBadges = $user->getBadges();

        foreach ($allBadges as $badge) {
            // Si l'utilisateur a déjà ce badge, on passe
            if ($userBadges->contains($badge)) {
                continue;
            }

            // Vérifier si l'utilisateur remplit les critères
            if ($this->checkBadgeCriteria($user, $badge)) {
                $user->addBadge($badge);
                $newBadges[] = $badge;
            }
        }

        if (!empty($newBadges)) {
            $this->entityManager->flush();
        }

        return $newBadges;
    }

    /**
     * Vérifie si un utilisateur remplit les critères d'un badge
     */
    private function checkBadgeCriteria(User $user, Badge $badge): bool
    {
        $criteria = $badge->getCriteria();
        $userStations = $this->userStationRepository->findBy(['user' => $user]);

        // Compte des stations passées et visitées
        $passedCount = 0;
        $stoppedCount = 0;

        foreach ($userStations as $userStation) {
            if ($userStation->isPassed()) {
                $passedCount++;
            }
            if ($userStation->isStopped()) {
                $stoppedCount++;
            }
        }

        // Vérification selon le type de critère
        if (isset($criteria['stopped']) && $stoppedCount >= $criteria['stopped']) {
            return true;
        }

        if (isset($criteria['passed']) && $passedCount >= $criteria['passed']) {
            return true;
        }

        if (isset($criteria['line_complete'])) {
            $completedLines = $this->getCompletedLinesCount($user);
            if ($completedLines >= $criteria['line_complete']) {
                return true;
            }
        }

        if (isset($criteria['all_stations']) && $criteria['all_stations']) {
            $totalStations = $this->stationRepository->count([]);
            if ($stoppedCount >= $totalStations) {
                return true;
            }
        }

        if (isset($criteria['account_created']) && $criteria['account_created']) {
            return true;
        }

        // Badge Noctambule : visité une station après minuit (00h-06h)
        if (isset($criteria['night_visit']) && $criteria['night_visit']) {
            if ($this->hasNightVisit($userStations)) {
                return true;
            }
        }

        // Badge Lève-tôt : visité une station avant 6h
        if (isset($criteria['early_visit']) && $criteria['early_visit']) {
            if ($this->hasEarlyVisit($userStations)) {
                return true;
            }
        }

        // Badge Marathonien : 10+ stations dans une journée
        if (isset($criteria['daily_marathon'])) {
            if ($this->hasDailyMarathon($userStations, $criteria['daily_marathon'])) {
                return true;
            }
        }

        // Badge Fidèle de la ligne : X stations passées sur la même ligne
        if (isset($criteria['line_passed_same'])) {
            if ($this->hasLinePassedSame($user, $criteria['line_passed_same'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur a visité une station pendant la nuit (22h-05h59 OU 00h-05h59)
     */
    private function hasNightVisit(array $userStations): bool
    {
        foreach ($userStations as $userStation) {
            $stoppedAt = $userStation->getFirstStoppedAt();
            if ($stoppedAt) {
                $hour = (int) $stoppedAt->format('H');
                // Entre 22h-23h59 OU entre 00h-05h59
                if (($hour >= 22 && $hour <= 23) || ($hour >= 0 && $hour <= 5)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur a visité une station tôt le matin (06h-08h59)
     */
    private function hasEarlyVisit(array $userStations): bool
    {
        foreach ($userStations as $userStation) {
            $stoppedAt = $userStation->getFirstStoppedAt();
            if ($stoppedAt) {
                $hour = (int) $stoppedAt->format('H');
                // Entre 06h et 08h59
                if ($hour >= 6 && $hour <= 8) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur a visité X stations ou plus dans une même journée
     */
    private function hasDailyMarathon(array $userStations, int $requiredCount): bool
    {
        // Grouper les stations par jour
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

        // Vérifier si un jour a au moins le nombre requis
        foreach ($stationsByDay as $count) {
            if ($count >= $requiredCount) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur a passé X stations ou plus sur une même ligne
     */
    private function hasLinePassedSame(User $user, int $requiredCount): bool
    {
        $lines = $this->lineRepository->findAll();

        foreach ($lines as $line) {
            $passedCountForLine = 0;
            $stations = $line->getStations();

            foreach ($stations as $station) {
                $userStation = $this->userStationRepository->findOneBy([
                    'user' => $user,
                    'station' => $station
                ]);

                if ($userStation && $userStation->isPassed()) {
                    $passedCountForLine++;
                }
            }

            if ($passedCountForLine >= $requiredCount) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compte le nombre de lignes complétées par l'utilisateur
     */
    private function getCompletedLinesCount(User $user): int
    {
        $lines = $this->lineRepository->findAll();
        $completedCount = 0;

        foreach ($lines as $line) {
            $stations = $line->getStations();
            $totalStations = count($stations);
            $visitedCount = 0;

            foreach ($stations as $station) {
                $userStation = $this->userStationRepository->findOneBy([
                    'user' => $user,
                    'station' => $station
                ]);

                if ($userStation && $userStation->isStopped()) {
                    $visitedCount++;
                }
            }

            if ($visitedCount === $totalStations && $totalStations > 0) {
                $completedCount++;
            }
        }

        return $completedCount;
    }

    /**
     * Récupère les badges disponibles avec leur statut pour un utilisateur
     */
    public function getBadgesStatus(User $user): array
    {
        $allBadges = $this->badgeRepository->findAll();
        $userBadges = $user->getBadges();
        $badgesStatus = [];

        foreach ($allBadges as $badge) {
            $unlocked = $userBadges->contains($badge);
            $progress = $this->calculateBadgeProgress($user, $badge);

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

        return $badgesStatus;
    }

    /**
     * Calcule la progression vers un badge (en %)
     */
    private function calculateBadgeProgress(User $user, Badge $badge): int
    {
        $criteria = $badge->getCriteria();
        $userStations = $this->userStationRepository->findBy(['user' => $user]);

        $passedCount = 0;
        $stoppedCount = 0;

        foreach ($userStations as $userStation) {
            if ($userStation->isPassed()) {
                $passedCount++;
            }
            if ($userStation->isStopped()) {
                $stoppedCount++;
            }
        }

        // Badge basé sur nombre de stations visitées
        if (isset($criteria['stopped'])) {
            return min(100, round(($stoppedCount / $criteria['stopped']) * 100));
        }

        // Badge basé sur nombre de stations passées
        if (isset($criteria['passed'])) {
            return min(100, round(($passedCount / $criteria['passed']) * 100));
        }

        // Badge basé sur lignes complétées
        if (isset($criteria['line_complete'])) {
            $completedLines = $this->getCompletedLinesCount($user);
            return min(100, round(($completedLines / $criteria['line_complete']) * 100));
        }

        // Badge basé sur toutes les stations
        if (isset($criteria['all_stations'])) {
            $totalStations = $this->stationRepository->count([]);
            return min(100, round(($stoppedCount / $totalStations) * 100));
        }

        // Badge marathonien : stations visitées en une journée
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
            $lines = $this->lineRepository->findAll();
            $maxPassedOnLine = 0;

            foreach ($lines as $line) {
                $passedCountForLine = 0;
                $stations = $line->getStations();

                foreach ($stations as $station) {
                    $userStation = $this->userStationRepository->findOneBy([
                        'user' => $user,
                        'station' => $station
                    ]);

                    if ($userStation && $userStation->isPassed()) {
                        $passedCountForLine++;
                    }
                }

                $maxPassedOnLine = max($maxPassedOnLine, $passedCountForLine);
            }

            return min(100, round(($maxPassedOnLine / $criteria['line_passed_same']) * 100));
        }

        // Badges temporels (noctambule, lève-tôt)
        if (isset($criteria['night_visit']) || isset($criteria['early_visit'])) {
            return $this->hasNightVisit($userStations) || $this->hasEarlyVisit($userStations) ? 100 : 0;
        }

        // Badge compte créé
        if (isset($criteria['account_created'])) {
            return 100;
        }

        return 0;
    }
}
