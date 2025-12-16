<?php

namespace App\DataFixtures;

use App\Entity\Line;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LineFixtures extends Fixture
{
    public const LINE_1_REFERENCE = 'line-1';
    public const LINE_2_REFERENCE = 'line-2';
    public const LINE_3_REFERENCE = 'line-3';
    public const LINE_3BIS_REFERENCE = 'line-3bis';
    public const LINE_4_REFERENCE = 'line-4';
    public const LINE_5_REFERENCE = 'line-5';
    public const LINE_6_REFERENCE = 'line-6';
    public const LINE_7_REFERENCE = 'line-7';
    public const LINE_7BIS_REFERENCE = 'line-7bis';
    public const LINE_8_REFERENCE = 'line-8';
    public const LINE_9_REFERENCE = 'line-9';
    public const LINE_10_REFERENCE = 'line-10';
    public const LINE_11_REFERENCE = 'line-11';
    public const LINE_12_REFERENCE = 'line-12';
    public const LINE_13_REFERENCE = 'line-13';
    public const LINE_14_REFERENCE = 'line-14';

    public function load(ObjectManager $manager): void
    {
        $lines = [
            [
                'number' => '1',
                'name' => 'La Défense - Château de Vincennes',
                'color' => '#FACD00',
                'textColor' => '#000000',
                'reference' => self::LINE_1_REFERENCE
            ],
            [
                'number' => '2',
                'name' => 'Porte Dauphine - Nation',
                'color' => '#2264AF',
                'textColor' => '#FFFFFF',
                'reference' => self::LINE_2_REFERENCE
            ],
            [
                'number' => '3',
                'name' => 'Pont de Levallois (Bécon) - Gallieni',
                'color' => '#9F9823',
                'textColor' => '#FFFFFF',
                'reference' => self::LINE_3_REFERENCE
            ],
            [
                'number' => '3bis',
                'name' => 'Porte des Lilas - Gambetta',
                'color' => '#97D5E2',
                'textColor' => '#000000',
                'reference' => self::LINE_3BIS_REFERENCE
            ],
            [
                'number' => '4',
                'name' => 'Porte de Clignancourt - Bagneux (Lucie Aubrac)',
                'color' => '#C14191',
                'textColor' => '#FFFFFF',
                'reference' => self::LINE_4_REFERENCE
            ],
            [
                'number' => '5',
                'name' => 'Bobigny (Pablo Picasso) - Place d\'Italie',
                'color' => '#f28E42',
                'textColor' => '#000000',
                'reference' => self::LINE_5_REFERENCE
            ],
            [
                'number' => '6',
                'name' => 'Charles de Gaulle (Étoile) - Nation',
                'color' => '#83C591',
                'textColor' => '#000000',
                'reference' => self::LINE_6_REFERENCE
            ],
            [
                'number' => '7',
                'name' => 'La Courneuve (8 mai 1945) - Villejuif (Louis Aragon) / Mairie d\'Ivry',
                'color' => '#F3A4BB',
                'textColor' => '#000000',
                'reference' => self::LINE_7_REFERENCE
            ],
            [
                'number' => '7bis',
                'name' => 'Louis Blanc - Pré-Saint-Gervais',
                'color' => '#83C591',
                'textColor' => '#000000',
                'reference' => self::LINE_7BIS_REFERENCE
            ],
            [
                'number' => '8',
                'name' => 'Balard - Pointe du Lac',
                'color' => '#CEADD2',
                'textColor' => '#000000',
                'reference' => self::LINE_8_REFERENCE
            ],
            [
                'number' => '9',
                'name' => 'Pont de Sèvres - Mairie de Montreuil',
                'color' => '#D5C900',
                'textColor' => '#000000',
                'reference' => self::LINE_9_REFERENCE
            ],
            [
                'number' => '10',
                'name' => 'Boulogne (Pont de Saint-Cloud) - Gare d\'Austerlitz',
                'color' => '#E3B32A',
                'textColor' => '#000000',
                'reference' => self::LINE_10_REFERENCE
            ],
            [
                'number' => '11',
                'name' => 'Châtelet - Rosny-Bois-Perrier',
                'color' => '#8D5E28',
                'textColor' => '#FFFFFF',
                'reference' => self::LINE_11_REFERENCE
            ],
            [
                'number' => '12',
                'name' => 'Mairie d\'Aubervilliers - Mairie d\'Issy',
                'color' => '#25814F',
                'textColor' => '#FFFFFF',
                'reference' => self::LINE_12_REFERENCE
            ],
            [
                'number' => '13',
                'name' => 'Saint-Denis (Université) / Asnières-Gennevilliers (Les Courtilles) - Châtillon-Montrouge',
                'color' => '#98D5E2',
                'textColor' => '#000000',
                'reference' => self::LINE_13_REFERENCE
            ],
            [
                'number' => '14',
                'name' => 'Saint-Denis (Pleyel) - Aéroport d\'Orly',
                'color' => '#672483',
                'textColor' => '#FFFFFF',
                'reference' => self::LINE_14_REFERENCE
            ],
        ];

        foreach ($lines as $lineData) {
            $line = new Line();
            $line->setNumber($lineData['number']);
            $line->setName($lineData['name']);
            $line->setColor($lineData['color']);
            $line->setTextColor($lineData['textColor']);

            $manager->persist($line);
            $this->addReference($lineData['reference'], $line);
        }

        $manager->flush();
    }
}