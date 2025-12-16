<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\BadgeService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager,
        BadgeService $badgeService
    ): Response {
        // Si l'utilisateur est déjà connecté, rediriger vers la home
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Encoder le mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            
            // Définir la date de création
            $user->setCreatedAt(new \DateTimeImmutable());

            try {
                $entityManager->persist($user);
                $entityManager->flush();

                // Attribuer le badge de bienvenue
                $badgeService->checkAndAwardBadges($user);

                // Message flash de succès
                $this->addFlash('success', '🎉 Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');

                return $this->redirectToRoute('app_login');
            } catch (UniqueConstraintViolationException $e) {
                // L'email existe déjà
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre compte.');
            } catch (\Exception $e) {
                // Autre erreur
                $this->addFlash('error', 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}