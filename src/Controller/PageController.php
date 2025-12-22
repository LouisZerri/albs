<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/regles-du-forum', name: 'app_forum_rules')]
    public function forumRules(): Response
    {
        return $this->render('page/forum_rules.html.twig');
    }
}