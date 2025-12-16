<?php

namespace App\Command;

use App\Repository\UserStationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-user-station-dates',
    description: 'Met à jour les dates des UserStations qui n\'en ont pas',
)]
class UpdateUserStationDatesCommand extends Command
{
    public function __construct(
        private UserStationRepository $userStationRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Mise à jour des dates des UserStations');

        $userStations = $this->userStationRepository->findAll();
        $updated = 0;

        foreach ($userStations as $userStation) {
            $needsUpdate = false;

            // Si la station est marquée comme "passed" mais n'a pas de date
            if ($userStation->isPassed() && $userStation->getFirstPassedAt() === null) {
                $userStation->setFirstPassedAt($userStation->getUpdatedAt() ?? new \DateTimeImmutable());
                $needsUpdate = true;
            }

            // Si la station est marquée comme "stopped" mais n'a pas de date
            if ($userStation->isStopped() && $userStation->getFirstStoppedAt() === null) {
                $userStation->setFirstStoppedAt($userStation->getUpdatedAt() ?? new \DateTimeImmutable());
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $updated++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('✓ %d UserStation(s) mis à jour avec succès !', $updated));

        return Command::SUCCESS;
    }
}