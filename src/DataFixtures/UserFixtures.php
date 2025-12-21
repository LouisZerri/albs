<?php

namespace App\DataFixtures;

use App\Entity\Badge;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $usersData = [
            // Admin
            [
                'username' => 'Louis',
                'email' => 'l.zerri@gmail.com',
                'roles' => ['ROLE_ADMIN'],
                'days' => 60,
            ],
            // Modérateur
            [
                'username' => 'ModérateurPaul',
                'email' => 'moderator@test.com',
                'roles' => ['ROLE_MODERATOR'],
                'days' => 45,
            ],
            // Utilisateurs normaux
            [
                'username' => 'MetroExplorer',
                'email' => 'explorer@yopmail.com',
                'roles' => ['ROLE_USER'],
                'days' => 30,
            ],
            [
                'username' => 'ParisienneJulie',
                'email' => 'julie@yopmail.com',
                'roles' => ['ROLE_USER'],
                'days' => 25,
            ],
            [
                'username' => 'RaymondDuRail',
                'email' => 'raymond@yopmail.com',
                'roles' => ['ROLE_USER'],
                'days' => 20,
            ],
            [
                'username' => 'StationHunter',
                'email' => 'hunter@yopmail.com',
                'roles' => ['ROLE_USER'],
                'days' => 18,
            ],
            [
                'username' => 'MétropoleMarie',
                'email' => 'marie@yopmail.com',
                'roles' => ['ROLE_USER'],
                'days' => 15,
            ],
            [
                'username' => 'TunnelVision',
                'email' => 'tunnel@yopmail.com',
                'roles' => ['ROLE_USER'],
                'days' => 12,
            ],
            [
                'username' => 'SubwayFan75',
                'email' => 'subway@yopmail.com',
                'roles' => ['ROLE_USER'],
                'days' => 10,
            ],
            [
                'username' => 'CorrespondanceKing',
                'email' => 'king@yopmail.com',
                'roles' => ['ROLE_USER'],
                'days' => 7,
            ],
            [
                'username' => 'NouveauVoyageur',
                'email' => 'nouveau@yopmail.com',
                'roles' => ['ROLE_USER'],
                'days' => 2,
            ],
        ];

        /** @var Badge $newAccountBadge */
        $newAccountBadge = $this->getReference(
            BadgeFixtures::BADGE_NEW_ACCOUNT,
            Badge::class
        );

        foreach ($usersData as $data) {
            $user = new User();
            $user->setUsername($data['username']);
            $user->setEmail($data['email']);
            $user->setRoles($data['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'Jeux-video9'));
            $user->setIsEmailVerified(true);
            $user->setAccountStatus('active');
            $user->setCreatedAt(new \DateTimeImmutable('-' . $data['days'] . ' days'));

            $user->addBadge($newAccountBadge);

            $manager->persist($user);
        }

        $manager->flush();

        echo "✅ " . count($usersData) . " utilisateurs créés\n";
    }

    public function getDependencies(): array
    {
        return [
            BadgeFixtures::class,
        ];
    }
}
