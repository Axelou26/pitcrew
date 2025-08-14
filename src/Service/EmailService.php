<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RecruiterSubscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;
    private string $senderEmail;
    private string $senderName;
    private bool $isDevMode;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        string $senderEmail = 'contact@pitcrew.fr',
        string $senderName = 'PitCrew',
        string $appEnv = 'dev'
    ) {
        $this->mailer        = $mailer;
        $this->urlGenerator  = $urlGenerator;
        $this->logger        = $logger;
        $this->entityManager = $entityManager;
        $this->senderEmail   = $senderEmail;
        $this->senderName    = $senderName;
        $this->isDevMode     = $appEnv === 'dev';

        // Log au démarrage du service
        $this->logger->info('Service EmailService initialisé', [
            'mailer_dsn' => $_SERVER['MAILER_DSN'] ?? 'non défini',
            'dev_mode'   => $this->isDevMode ? 'oui' : 'non',
            'sender'     => "$senderName <$senderEmail>",
        ]);
    }

    /**
     * Envoie un email de confirmation d'inscription avec le lien de vérification.
     */
    public function sendRegistrationConfirmation(User $user): void
    {
        if (!$user->getEmail()) {
            return;
        }

        // Générer un token de vérification unique
        $verificationToken = bin2hex(random_bytes(32));
        $user->setVerificationToken($verificationToken);
        $this->entityManager->flush();

        $verificationUrl = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $verificationToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Confirmez votre inscription')
            ->htmlTemplate('emails/registration/confirmation.html.twig')
            ->context([
                'user'             => $user,
                'verification_url' => $verificationUrl,
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie un email de confirmation d'abonnement.
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
                'user'         => $user,
                'subscription' => $subscription,
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie un email de rappel d'expiration d'abonnement.
     */
    public function sendSubscriptionExpirationReminder(User $user, RecruiterSubscription $subscription): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $endDate = $subscription->getEndDate();
        if ($endDate === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Votre abonnement expire bientôt')
            ->htmlTemplate('emails/subscription/expiration_reminder.html.twig')
            ->context([
                'user'           => $user,
                'subscription'   => $subscription,
                'expirationDate' => $endDate->format('d/m/Y'),
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie un email de confirmation d'annulation d'abonnement.
     */
    public function sendSubscriptionCancellationConfirmation(User $user, RecruiterSubscription $subscription): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $endDate = $subscription->getEndDate();
        if ($endDate === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Confirmation d\'annulation de votre abonnement')
            ->htmlTemplate('emails/subscription/cancellation.html.twig')
            ->context([
                'user'         => $user,
                'subscription' => $subscription,
                'endDate'      => $endDate->format('d/m/Y'),
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie un reçu de paiement.
     */
    public function sendPaymentReceipt(User $user, RecruiterSubscription $subscription): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $subscriptionEntity = $subscription->getSubscription();
        $startDate          = $subscription->getStartDate();

        if ($subscriptionEntity === null || $startDate === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Reçu de paiement')
            ->htmlTemplate('emails/subscription/payment_receipt.html.twig')
            ->context([
                'user'         => $user,
                'subscription' => $subscription,
                'amount'       => $subscriptionEntity->getPrice(),
                'date'         => $startDate->format('d/m/Y'),
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie une notification d'échec de paiement.
     */
    public function sendPaymentFailedNotification(User $user, RecruiterSubscription $subscription): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $subscriptionEntity = $subscription->getSubscription();
        if ($subscriptionEntity === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Échec du paiement')
            ->htmlTemplate('emails/subscription/payment_failed.html.twig')
            ->context([
                'user'         => $user,
                'subscription' => $subscription,
                'amount'       => $subscriptionEntity->getPrice(),
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie un email de test pour vérifier la configuration.
     */
    public function sendTestEmail(string $toEmail): void
    {
        $email = (new Email())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($toEmail)
            ->subject('Test de configuration email PitCrew')
            ->html('<p>Ceci est un email de test pour vérifier la configuration SMTP.</p>');

        $this->sendEmail($email);
    }

    /**
     * Envoie un email de réinitialisation de mot de passe.
     */
    public function sendPasswordResetEmail(User $user, string $resetToken): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'user'       => $user,
                'resetToken' => $resetToken,
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie un email de bienvenue.
     */
    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($user->getEmail())
            ->subject('Bienvenue sur PitCrew !')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->sendEmail($email);
    }

    /**
     * Envoie un email de vérification de compte.
     */
    public function sendVerificationEmail(User $user): void
    {
        if (!$user->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Vérification de votre compte')
            ->htmlTemplate('emails/registration/confirmation.html.twig')
            ->context([
                'user'             => $user,
                'verification_url' => $this->urlGenerator->generate(
                    'app_verify_email',
                    ['id' => $user->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]);

        $this->sendEmail($email);
    }

    /**
     * Méthode privée pour envoyer l'email et gérer les erreurs.
     */
    private function sendEmail(Email $email): void
    {
        try {
            // Log avant l'envoi
            $this->logger->info('Tentative d\'envoi d\'email', [
                'subject'     => $email->getSubject(),
                'to'          => implode(', ', array_map(fn ($addr) => $addr->toString(), $email->getTo())),
                'is_dev_mode' => $this->isDevMode ? 'oui' : 'non',
                'mailer_dsn'  => $_SERVER['MAILER_DSN'] ?? 'non défini',
            ]);

            $this->mailer->send($email);

            // Log après l'envoi réussi
            $this->logger->info('Email envoyé avec succès', [
                'subject' => $email->getSubject(),
                'to'      => implode(', ', array_map(fn ($addr) => $addr->toString(), $email->getTo())),
            ]);
        } catch (\Exception $e) {
            // Log détaillé de l'erreur
            $this->logger->error('Erreur d\'envoi d\'email', [
                'subject'       => $email->getSubject(),
                'to'            => implode(', ', array_map(fn ($addr) => $addr->toString(), $email->getTo())),
                'error_message' => $e->getMessage(),
                'error_code'    => $e->getCode(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'mailer_dsn'    => $_SERVER['MAILER_DSN'] ?? 'non défini',
            ]);

            if ($this->isDevMode) {
                $this->logger->info('Email non envoyé (mode développement): {subject}', [
                    'subject' => $email->getSubject(),
                    'to'      => implode(', ', array_map(fn ($addr) => $addr->toString(), $email->getTo())),
                    'error'   => $e->getMessage(),
                ]);

                return; // Sortir en mode dev après log
            }
            // En production, on propage l'erreur
            throw $e;
        }
    }
}
