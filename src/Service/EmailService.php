<?php

namespace App\Service;

use App\Entity\RecruiterSubscription;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\TemplatedEmail;

class EmailService
{
    private $mailer;
    private $twig;
    private $urlGenerator;
    private $senderEmail;
    private $senderName;
    private $isDevMode;
    private $logger;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger,
        string $senderEmail = 'contact@pitcrew.fr',
        string $senderName = 'PitCrew',
        string $appEnv = 'dev'
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
        $this->isDevMode = $appEnv === 'dev';
    }

    /**
     * Envoie un email de confirmation d'abonnement
     */
    public function sendSubscriptionConfirmation(User $user, RecruiterSubscription $subscription): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Confirmation de votre abonnement')
            ->htmlTemplate('emails/subscription/confirmation.html.twig')
            ->context([
                'user' => $user,
                'subscription' => $subscription
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie un email de rappel d'expiration d'abonnement
     */
    public function sendSubscriptionExpirationReminder(User $user, RecruiterSubscription $subscription): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Votre abonnement expire bientôt')
            ->htmlTemplate('emails/subscription/expiration_reminder.html.twig')
            ->context([
                'user' => $user,
                'subscription' => $subscription,
                'expirationDate' => $subscription->getEndDate()->format('d/m/Y')
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie un email de confirmation d'annulation d'abonnement
     */
    public function sendSubscriptionCancellationConfirmation(User $user, RecruiterSubscription $subscription): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Confirmation d\'annulation de votre abonnement')
            ->htmlTemplate('emails/subscription/cancellation.html.twig')
            ->context([
                'user' => $user,
                'subscription' => $subscription,
                'endDate' => $subscription->getEndDate()->format('d/m/Y')
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie un reçu de paiement
     */
    public function sendPaymentReceipt(User $user, RecruiterSubscription $subscription): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Reçu de paiement')
            ->htmlTemplate('emails/subscription/payment_receipt.html.twig')
            ->context([
                'user' => $user,
                'subscription' => $subscription,
                'amount' => $subscription->getSubscription()->getPrice(),
                'date' => $subscription->getStartDate()->format('d/m/Y')
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie une notification d'échec de paiement
     */
    public function sendPaymentFailedNotification(User $user, RecruiterSubscription $subscription): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Échec du paiement')
            ->htmlTemplate('emails/subscription/payment_failed.html.twig')
            ->context([
                'user' => $user,
                'subscription' => $subscription,
                'amount' => $subscription->getSubscription()->getPrice()
            ]);

        $this->sendEmail($email);
    }

    /**
     * Méthode privée pour envoyer l'email et gérer les erreurs.
     */
    private function sendEmail(TemplatedEmail $email): void
    {
        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            if ($this->isDevMode) {
                $this->logger->info('Email non envoyé (mode développement): {subject}', [
                    'subject' => $email->getSubject(),
                    'to' => implode(', ', array_map(fn($addr) => $addr->toString(), $email->getTo())),
                    'error' => $e->getMessage()
                ]);
                return; // Sortir en mode dev après log
            }
            // En production, on propage l'erreur
            throw $e;
        }
    }
}
