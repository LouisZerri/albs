<?php

namespace App\DataFixtures;

use App\Entity\LineDiscussion;
use App\Entity\LineDiscussionReply;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ForumFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            LineFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Récupérer les utilisateurs
        $users = $manager->getRepository(User::class)->findAll();
        $lines = $manager->getRepository(\App\Entity\Line::class)->findAll();

        if (empty($users) || empty($lines)) {
            echo "⚠️ Pas d'utilisateurs ou de lignes trouvés\n";
            return;
        }

        $discussions = [];

        // Discussions variées
        $discussionData = [
            [
                'title' => 'Meilleure station pour les photos ?',
                'content' => "Salut à tous ! Je cherche des stations avec une belle architecture pour faire des photos. Vous avez des recommandations ? J'ai déjà fait Arts et Métiers qui est magnifique avec son style steampunk !",
                'replies' => [
                    "Arts et Métiers c'est top ! Sinon je te conseille Cité, elle a un charme fou.",
                    "Louvre-Rivoli est superbe aussi avec ses reproductions d'œuvres.",
                    "N'oublie pas Abbesses avec ses fresques murales !",
                    "Perso je trouve que Saint-Lazare a un côté très photogénique avec sa verrière.",
                ]
            ],
            [
                'title' => 'Challenge : compléter une ligne en une journée',
                'content' => "Qui a déjà tenté de faire toutes les stations d'une ligne en une seule journée ? Je pensais essayer avec la ligne 1, ça vous dit de partager vos expériences ?",
                'replies' => [
                    "J'ai fait la ligne 6 en un après-midi ! Les vues sur la Tour Eiffel valent le détour.",
                    "La ligne 1 c'est faisable, il y a 25 stations. Compte environ 3-4h si tu t'arrêtes vraiment à chaque station.",
                    "Moi j'ai fait la 14, c'est la plus rapide mais aussi la moins intéressante vu qu'elle est toute récente.",
                    "Pro tip : commence tôt le matin, moins de monde !",
                    "J'ai essayé la 13... jamais plus 😅 Trop longue et bondée.",
                ]
            ],
            [
                'title' => 'Les stations fantômes, vous connaissez ?',
                'content' => "Je viens de découvrir qu'il existe des stations fermées au public ! Genre Saint-Martin ou Haxo. Quelqu'un a des infos là-dessus ?",
                'replies' => [
                    "Oui ! Il y a aussi Porte Molitor et Croix-Rouge. Parfois la RATP organise des visites.",
                    "Arsenal aussi est une station fantôme, on peut l'apercevoir depuis la ligne 5.",
                    "Le plus fou c'est Haxo : elle a été construite mais jamais ouverte au public !",
                ]
            ],
            [
                'title' => 'Rencontre métro-explorateurs ce weekend ?',
                'content' => "Hello ! Ça vous dirait qu'on organise une sortie groupée ce weekend pour explorer une ligne ensemble ? On pourrait faire la ligne 11 qui vient d'être prolongée !",
                'replies' => [
                    "Trop bien comme idée ! Je suis dispo samedi après-midi.",
                    "Partant aussi ! On se retrouve où ?",
                    "Je propose Châtelet comme point de départ, c'est central pour tout le monde.",
                    "Super initiative, j'amène des croissants 🥐",
                    "Moi je peux venir dimanche si vous refaites une session !",
                    "On crée un groupe WhatsApp pour s'organiser ?",
                ]
            ],
            [
                'title' => 'Bug avec le badge "Marathonien" ?',
                'content' => "J'ai visité 15 stations hier mais je n'ai pas eu le badge Marathonien. C'est normal ? Il faut combien de stations exactement ?",
                'replies' => [
                    "Il faut 20 stations en une journée pour le badge Marathonien je crois.",
                    "Vérifie que tu as bien marqué toutes les stations comme 'visitées' et pas juste 'passées'.",
                    "Moi j'ai eu le même souci, il faut attendre quelques minutes parfois.",
                ]
            ],
            [
                'title' => 'La ligne 14 enfin complète !',
                'content' => "Ça y est, j'ai fait toutes les stations de la ligne 14 avec le nouveau prolongement jusqu'à Saint-Denis Pleyel ! Les nouvelles stations sont vraiment modernes.",
                'replies' => [
                    "GG ! C'est ma prochaine target. Les stations sont comment ?",
                    "Saint-Denis Pleyel est immense, on se croirait dans un aéroport.",
                    "J'ai hâte d'y aller ! Tu as mis combien de temps pour tout faire ?",
                    "Mairie de Saint-Ouen est sympa aussi avec ses œuvres d'art.",
                ]
            ],
            [
                'title' => 'Astuces pour les correspondances ?',
                'content' => "Certaines correspondances sont interminables (coucou Montparnasse). Vous avez des tips pour optimiser les trajets ?",
                'replies' => [
                    "Châtelet-Les Halles : toujours prendre la sortie côté forum, c'est plus rapide.",
                    "À République, les correspondances sont assez courtes en fait.",
                    "Montparnasse c'est l'enfer, pas de solution miracle malheureusement 😭",
                    "Pro tip : l'appli Citymapper te donne le bon wagon pour les correspondances !",
                    "Saint-Lazare aussi c'est un labyrinthe, mais on s'y fait.",
                ]
            ],
            [
                'title' => 'Votre station préférée et pourquoi ?',
                'content' => "Simple question : c'est quoi votre station coup de cœur ? Moi c'est Liège pour ses faïences belges magnifiques !",
                'replies' => [
                    "Concorde avec ses lettres sur les murs, j'adore le concept !",
                    "Moi c'est Bastille côté ligne 1, l'histoire de la Révolution française sur les murs.",
                    "Arts et Métiers, sans hésitation. On se croirait dans un sous-marin de Jules Verne !",
                    "Cluny - La Sorbonne pour son ambiance médiévale.",
                    "Pont Neuf est sous-cotée, la mosaïque est magnifique.",
                    "Personnellement j'adore Jaurès avec sa partie aérienne.",
                ]
            ],
        ];

        foreach ($discussionData as $index => $data) {
            $discussion = new LineDiscussion();
            $discussion->setTitle($data['title']);
            $discussion->setContent($data['content']);
            $discussion->setLine($lines[array_rand($lines)]);
            $discussion->setAuthor($users[array_rand($users)]);
            $discussion->setCreatedAt(new \DateTimeImmutable('-' . rand(1, 30) . ' days'));
            $discussion->setViewCount(rand(10, 500));

            // Épingler la première discussion
            if ($index === 0) {
                $discussion->setIsPinned(true);
            }

            $manager->persist($discussion);
            $discussions[] = $discussion;

            // Ajouter les réponses
            foreach ($data['replies'] as $replyIndex => $replyContent) {
                $reply = new LineDiscussionReply();
                $reply->setContent($replyContent);
                $reply->setDiscussion($discussion);
                $reply->setAuthor($users[array_rand($users)]);
                $reply->setCreatedAt(new \DateTimeImmutable('-' . rand(0, 29) . ' days -' . rand(1, 23) . ' hours'));

                $manager->persist($reply);
            }
        }

        $manager->flush();

        echo "✅ " . count($discussions) . " discussions créées avec leurs réponses\n";
    }
}