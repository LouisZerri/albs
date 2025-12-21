<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Warning;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ModerationEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail = 'no-reply@alabonnestation.fr',
        private string $fromName = 'À la bonne station'
    ) {
    }

    /**
     * Envoie un email d'avertissement à l'utilisateur
     */
    public function sendWarningEmail(User $user, Warning $warning): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('⚠️ Avertissement - À la bonne station')
            ->htmlTemplate('emails/moderation/warning.html.twig')
            ->context([
                'user' => $user,
                'warning' => $warning,
                'warningCount' => $user->getWarningCount(),
                'remainingWarnings' => 3 - $user->getWarningCount(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de bannissement à l'utilisateur
     */
    public function sendBanEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('🚫 Votre compte a été banni - À la bonne station')
            ->htmlTemplate('emails/moderation/ban.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}