<?php

namespace App\Controller;

use App\Form\PasswordResetRequestFormType;
use App\Form\PasswordResetFormType;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class PasswordResetController extends AbstractController
{
    /**
     * Page de demande de réinitialisation (email)
     */
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function request(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        $form = $this->createForm(PasswordResetRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user && $user->isActive()) {
                // Générer le token
                $user->generatePasswordResetToken();
                $entityManager->flush();

                // Envoyer l'email
                try {
                    $emailService->sendPasswordReset($user);
                    $this->addFlash('success', '✅ Email envoyé ! Vérifiez votre boîte mail.');
                } catch (\Exception $e) {
                    $this->addFlash('error', '❌ Erreur : ' . $e->getMessage());
                }
            } else {
                // Sécurité : ne pas révéler si le compte existe
                $this->addFlash('success', '✅ Si un compte existe, un email a été envoyé.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'requestForm' => $form,
        ]);
    }

    /**
     * Page de réinitialisation (avec token)
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $userRepository->findOneBy(['passwordResetToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Lien invalide');
            return $this->redirectToRoute('app_login');
        }

        if (!$user->isPasswordResetTokenValid()) {
            $this->addFlash('error', 'Lien expiré. Refaites une demande.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(PasswordResetFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->resetPassword($hashedPassword);
            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe réinitialisé ! Vous pouvez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form,
        ]);
    }
}