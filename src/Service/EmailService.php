<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Envoie un email de vérification
     */
    public function sendEmailVerification(User $user): void
    {
        $verificationUrl = $this->urlGenerator->generate('app_verify_email', [
            'token' => $user->getEmailVerificationToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@alabonnestation.fr', 'À la bonne station'))
            ->to($user->getEmail())
            ->subject('Vérifiez votre adresse email - À la bonne station')
            ->htmlTemplate('emails/verify_email.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'expiresAt' => $user->getEmailVerificationTokenExpiresAt(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     */
    public function sendPasswordReset(User $user): void
    {
        $resetUrl = $this->urlGenerator->generate('app_reset_password', [
            'token' => $user->getPasswordResetToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@alabonnestation.fr', 'À la bonne station'))
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expiresAt' => $user->getPasswordResetTokenExpiresAt(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de bienvenue
     */
    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@alabonnestation.fr', 'À la bonne station'))
            ->to($user->getEmail())
            ->subject('Bienvenue sur À la bonne station ! 🚇')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}