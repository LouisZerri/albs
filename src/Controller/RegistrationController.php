<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\BadgeService;
use App\Service\EmailService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    /**
     * Page d'inscription
     */
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        // Si déjà connecté, rediriger
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Hasher le mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Générer le token de vérification
            $user->generateEmailVerificationToken();

            try {
                $entityManager->persist($user);
                $entityManager->flush();

                // Envoyer l'email de vérification
                try {
                    $emailService->sendEmailVerification($user);
                    $this->addFlash('success', '🎉 Inscription réussie ! Un email de vérification a été envoyé à ' . $user->getEmail());
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Compte créé mais l\'email n\'a pas pu être envoyé.');
                }

                $request->getSession()->save();
                return $this->redirectToRoute('app_login');
            } catch (UniqueConstraintViolationException $e) {
                // Déterminer quel champ est en doublon
                if (str_contains($e->getMessage(), 'email')) {
                    $this->addFlash('error', 'Cet email est déjà utilisé.');
                } elseif (str_contains($e->getMessage(), 'username')) {
                    $this->addFlash('error', 'Ce pseudo est déjà utilisé.');
                } else {
                    $this->addFlash('error', 'Ces informations sont déjà utilisées.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du compte.');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Vérification de l'email via le token
     */
    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        BadgeService $badgeService,
        EmailService $emailService
    ): Response {
        $user = $userRepository->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Lien de vérification invalide.');
            return $this->redirectToRoute('app_login');
        }

        if (!$user->isEmailVerificationTokenValid()) {
            $this->addFlash('error', 'Ce lien a expiré. Veuillez demander un nouveau lien.');
            return $this->redirectToRoute('app_resend_verification');
        }

        // Vérifier l'email et activer le compte
        $user->verifyEmail();
        $entityManager->flush();

        // Attribuer les badges de bienvenue
        $badgeService->checkAndAwardBadges($user);

        // Envoyer l'email de bienvenue
        try {
            $emailService->sendWelcomeEmail($user);
        } catch (\Exception $e) {
            // Pas grave si ça échoue
        }

        $this->addFlash('success', 'Email vérifié ! Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }

    /**
     * Renvoyer l'email de vérification
     */
    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user && !$user->isEmailVerified()) {
                // Régénérer le token
                $user->generateEmailVerificationToken();
                $entityManager->flush();

                // Renvoyer l'email
                try {
                    $emailService->sendEmailVerification($user);
                    $this->addFlash('success', 'Email de vérification renvoyé');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
                }
            } else {
                // Sécurité : ne pas révéler si le compte existe
                $this->addFlash('success', 'Si un compte existe, un email a été envoyé.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/resend_verification.html.twig');
    }
}
