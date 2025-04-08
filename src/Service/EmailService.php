<?php

namespace App\Service;

use App\Entity\RecruiterSubscription;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Psr\Log\LoggerInterface;

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
        $invoiceUrl = $this->urlGenerator->generate('app_subscription_invoice', [
            'id' => $subscription->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from(sprintf('"%s" <%s>', $this->senderName, $this->senderEmail))
            ->to($user->getEmail())
            ->subject('Confirmation de votre abonnement ' . $subscription->getSubscription()->getName())
            ->html(
                $this->twig->render('emails/subscription_confirmation.html.twig', [
                    'user' => $user,
                    'subscription' => $subscription,
                    'invoiceUrl' => $invoiceUrl
                ])
            );

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // En mode développement, on log simplement l'erreur au lieu de la propager
            if ($this->isDevMode) {
                $this->logger->info('Email non envoyé (mode développement): {subject}', [
                    'subject' => $email->getSubject(),
                    'to' => $email->getTo(),
                    'error' => $e->getMessage()
                ]);
            } else {
                // En production, on propage l'erreur
                throw $e;
            }
        }
    }

    /**
     * Envoie un email de rappel d'expiration d'abonnement
     */
    public function sendSubscriptionExpirationReminder(User $user, RecruiterSubscription $subscription): void
    {
        $manageUrl = $this->urlGenerator->generate('app_subscription_manage', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $daysLeft = (new \DateTime())->diff($subscription->getEndDate())->days;

        $email = (new Email())
            ->from(sprintf('"%s" <%s>', $this->senderName, $this->senderEmail))
            ->to($user->getEmail())
            ->subject('Votre abonnement expire bientôt')
            ->html(
                $this->twig->render('emails/subscription_expiration.html.twig', [
                    'user' => $user,
                    'subscription' => $subscription,
                    'daysLeft' => $daysLeft,
                    'manageUrl' => $manageUrl
                ])
            );

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            if ($this->isDevMode) {
                $this->logger->info('Email non envoyé (mode développement): {subject}', [
                    'subject' => $email->getSubject(),
                    'to' => $email->getTo(),
                    'error' => $e->getMessage()
                ]);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Envoie un email de confirmation d'annulation d'abonnement
     */
    public function sendSubscriptionCancellationConfirmation(User $user, RecruiterSubscription $subscription): void
    {
        $plansUrl = $this->urlGenerator->generate('app_subscription_plans', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from(sprintf('"%s" <%s>', $this->senderName, $this->senderEmail))
            ->to($user->getEmail())
            ->subject('Confirmation d\'annulation de votre abonnement')
            ->html(
                $this->twig->render('emails/subscription_cancellation.html.twig', [
                    'user' => $user,
                    'subscription' => $subscription,
                    'plansUrl' => $plansUrl
                ])
            );

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            if ($this->isDevMode) {
                $this->logger->info('Email non envoyé (mode développement): {subject}', [
                    'subject' => $email->getSubject(),
                    'to' => $email->getTo(),
                    'error' => $e->getMessage()
                ]);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Envoie un reçu de paiement
     */
    public function sendPaymentReceipt(User $user, RecruiterSubscription $subscription): void
    {
        $invoiceUrl = $this->urlGenerator->generate('app_subscription_invoice', [
            'id' => $subscription->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from(sprintf('"%s" <%s>', $this->senderName, $this->senderEmail))
            ->to($user->getEmail())
            ->subject('Reçu de paiement - Abonnement ' . $subscription->getSubscription()->getName())
            ->html(
                $this->twig->render('emails/payment_receipt.html.twig', [
                    'user' => $user,
                    'subscription' => $subscription,
                    'invoiceUrl' => $invoiceUrl
                ])
            );

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            if ($this->isDevMode) {
                $this->logger->info('Email non envoyé (mode développement): {subject}', [
                    'subject' => $email->getSubject(),
                    'to' => $email->getTo(),
                    'error' => $e->getMessage()
                ]);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Envoie une notification d'échec de paiement
     */
    public function sendPaymentFailedNotification(User $user): void
    {
        $manageUrl = $this->urlGenerator->generate('app_subscription_manage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from(sprintf('"%s" <%s>', $this->senderName, $this->senderEmail))
            ->to($user->getEmail())
            ->subject('Échec de paiement pour votre abonnement')
            ->html(
                $this->twig->render('emails/payment_failed.html.twig', [
                    'user' => $user,
                    'manageUrl' => $manageUrl
                ])
            );

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            if ($this->isDevMode) {
                $this->logger->info('Email non envoyé (mode développement): {subject}', [
                    'subject' => $email->getSubject(),
                    'to' => $email->getTo(),
                    'error' => $e->getMessage()
                ]);
            } else {
                throw $e;
            }
        }
    }
}
