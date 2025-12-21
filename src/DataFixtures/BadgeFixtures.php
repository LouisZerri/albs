<?php

namespace App\DataFixtures;

use App\Entity\Badge;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BadgeFixtures extends Fixture
{

    public const BADGE_NEW_ACCOUNT = 'badge_new_account';

    public function load(ObjectManager $manager): void
    {
        $badges = [
            // Badges de démarrage
            [
                'name' => 'Parisien en herbe',
                'description' => 'Félicitations ! Vous avez visité votre première station.',
                'icon' => '🌱',
                'type' => 'starter',
                'criteria' => ['stopped' => 1]
            ],
            [
                'name' => 'Touriste averti',
                'description' => 'Vous êtes passé par 10 stations différentes.',
                'icon' => '🗼',
                'type' => 'progression',
                'criteria' => ['passed' => 10]
            ],
            [
                'name' => 'Habitué du métro',
                'description' => 'Vous avez visité 5 stations.',
                'icon' => '🚇',
                'type' => 'progression',
                'criteria' => ['stopped' => 5]
            ],
            
            // Badges intermédiaires
            [
                'name' => 'Vrai Parisien',
                'description' => 'Vous avez visité 25 stations. Vous connaissez votre chemin !',
                'icon' => '🥐',
                'type' => 'progression',
                'criteria' => ['stopped' => 25]
            ],
            [
                'name' => 'Rat des quais',
                'description' => 'Vous êtes passé par 50 stations. Le métro n\'a plus de secrets pour vous !',
                'icon' => '🐀',
                'type' => 'progression',
                'criteria' => ['passed' => 50]
            ],
            [
                'name' => 'Explorateur urbain',
                'description' => 'Vous avez visité 50 stations différentes.',
                'icon' => '🗺️',
                'type' => 'progression',
                'criteria' => ['stopped' => 50]
            ],
            
            // Badges de ligne
            [
                'name' => 'Maître de ligne',
                'description' => 'Vous avez visité toutes les stations d\'une ligne.',
                'icon' => '👑',
                'type' => 'line_complete',
                'criteria' => ['line_complete' => 1]
            ],
            [
                'name' => 'Collectionneur de lignes',
                'description' => 'Vous avez complété 3 lignes entières.',
                'icon' => '🎯',
                'type' => 'line_complete',
                'criteria' => ['line_complete' => 3]
            ],
            [
                'name' => 'Seigneur du métro',
                'description' => 'Vous avez complété 5 lignes. Respect !',
                'icon' => '👨‍✈️',
                'type' => 'line_complete',
                'criteria' => ['line_complete' => 5]
            ],
            
            // Badges avancés
            [
                'name' => 'Globe-trotter parisien',
                'description' => 'Vous avez visité 100 stations. Impressionnant !',
                'icon' => '🌍',
                'type' => 'progression',
                'criteria' => ['stopped' => 100]
            ],
            [
                'name' => 'Légende du métro',
                'description' => 'Vous avez visité toutes les stations du métro parisien !',
                'icon' => '🏆',
                'type' => 'complete',
                'criteria' => ['all_stations' => true]
            ],
            
            // Badges spéciaux temporels
            [
                'name' => 'Oiseau de nuit',
                'description' => 'Vous avez visité une station entre 22h et 6h du matin.',
                'icon' => '🦉',
                'type' => 'special',
                'criteria' => ['night_visit' => true]
            ],
            [
                'name' => 'Lève-tôt',
                'description' => 'Vous avez visité une station entre 6h et 9h du matin.',
                'icon' => '🐓',
                'type' => 'special',
                'criteria' => ['early_visit' => true]
            ],
            [
                'name' => 'Jogger du métro',
                'description' => 'Vous avez visité 10 stations en une seule journée.',
                'icon' => '🚶',
                'type' => 'marathon',
                'criteria' => ['daily_marathon' => 10]
            ],
            [
                'name' => 'Marathonien du métro',
                'description' => 'Vous avez visité 20 stations en une seule journée. Impressionnant !',
                'icon' => '🏃',
                'type' => 'marathon',
                'criteria' => ['daily_marathon' => 20]
            ],
            [
                'name' => 'Ultra-marathonien',
                'description' => 'Vous avez visité 30 stations en une seule journée. Vous êtes exceptionnel !',
                'icon' => '🦸',
                'type' => 'marathon',
                'criteria' => ['daily_marathon' => 30]
            ],
            
            [
                'name' => 'Fidèle de la ligne',
                'description' => 'Vous êtes passé par 20 stations de la même ligne.',
                'icon' => '💙',
                'type' => 'line_loyalty',
                'criteria' => ['line_passed_same' => 20]
            ],
            [
                'name' => 'Nouveau départ',
                'description' => 'Bienvenue ! Vous venez de créer votre compte.',
                'icon' => '🎉',
                'type' => 'account',
                'criteria' => ['account_created' => true]
            ],
        ];

        foreach ($badges as $badgeData) {
            $badge = new Badge();
            $badge->setName($badgeData['name']);
            $badge->setDescription($badgeData['description']);
            $badge->setIcon($badgeData['icon']);
            $badge->setType($badgeData['type']);
            $badge->setCriteria($badgeData['criteria']);

            $manager->persist($badge);

            if ($badgeData['type'] === 'account') {
                $this->addReference(self::BADGE_NEW_ACCOUNT, $badge);
            }
        }

        $manager->flush();
    }
}