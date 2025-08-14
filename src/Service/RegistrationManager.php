<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Recruiter;
use App\Entity\RecruiterSubscription;
use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Stripe\StripeClient;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationManager
{
    private StripeClient $stripe;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly StripeService $stripeService,
        private readonly SubscriptionService $subscriptionService,
        private readonly EmailService $emailService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $stripeSecretKey
    ) {
        $this->stripe = new StripeClient($this->stripeSecretKey);
    }

    public function createUser(User $user, string $plainPassword, string $userType): User
    {
        // Hash le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Définir les rôles en fonction du type d'utilisateur
        if ($userType === User::ROLE_RECRUTEUR) {
            $user->setRoles(['ROLE_USER', 'ROLE_RECRUTEUR']);
        }

        if ($userType !== User::ROLE_RECRUTEUR) {
            $user->setRoles(['ROLE_USER', 'ROLE_POSTULANT']);
        }

        // Générer un token de vérification
        $user->setVerificationToken(bin2hex(random_bytes(32)));
        $user->setIsVerified(false);

        // Persister l'utilisateur
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoyer un email de vérification
        try {
            $this->emailService->sendVerificationEmail($user);
        } catch (Exception $e) {
            // Log l'erreur mais ne pas bloquer l'inscription
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    public function handleSubscriptionSelection(
        User $user,
        Subscription $subscription,
        bool $testMode = false,
        bool $offlineMode = false
    ): array {
        try {
            // Extraire le niveau d'abonnement à partir du nom
            $subscriptionLevel = $this->getSubscriptionLevelFromName($subscription->getName());

            if ($offlineMode) {
                // En mode hors ligne, créer directement l'abonnement sans paiement
                $this->subscriptionService->createSubscription($user, $subscriptionLevel);

                return [
                    'redirect' => 'subscription_success',
                    'message'  => 'Votre abonnement a été activé en mode hors ligne.',
                ];
            }

            // Créer ou récupérer le client Stripe
            $stripeCustomerId = $user->getStripeCustomerId();
            if (!$stripeCustomerId) {
                $stripeCustomerId = $this->stripeService->createCustomer($user);
                $user->setStripeCustomerId($stripeCustomerId);
                $this->entityManager->flush();
            }

            // Créer une session de checkout Stripe
            $session = $this->stripeService->createCheckoutSession($user, $subscription);

            return [
                'redirect_url' => $session->url,
                'message'      => 'Redirection vers la page de paiement...',
            ];
        } catch (Exception $e) {
            return [
                'redirect' => 'subscription',
                'error'    => 'Une erreur est survenue lors de la création de la session de paiement: ' .
                    $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function handleSubscriptionSuccess(int $userId, int $subscriptionId, bool $offlineMode = false): array
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                throw new RuntimeException('Utilisateur non trouvé');
            }

            $subscription = $this->entityManager->getRepository(Subscription::class)->find($subscriptionId);
            if (!$subscription) {
                throw new RuntimeException('Abonnement non trouvé');
            }

            // Extraire le niveau d'abonnement à partir du nom
            $subscriptionLevel = $this->getSubscriptionLevelFromName($subscription->getName());

            // Créer l'abonnement pour l'utilisateur
            $this->subscriptionService->createSubscription($user, $subscriptionLevel);

            return [
                'message' => 'Votre abonnement a été activé avec succès. Bienvenue !',
                'user'    => $user,
            ];
        } catch (Exception $e) {
            return [
                'error' => 'Une erreur est survenue lors de l\'activation de l\'abonnement: ' . $e->getMessage(),
            ];
        }
    }

    public function createRecruiterSubscription(User $user, string $subscriptionType): RecruiterSubscription
    {
        if (!$user instanceof Recruiter) {
            throw new InvalidArgumentException('User must be a Recruiter');
        }

        return $this->subscriptionService->createSubscription($user, $subscriptionType);
    }

    /**
     * @return array<string, mixed>
     */
    public function processDirectSubscription(User $user, string $subscriptionType): array
    {
        // Logique de traitement de l'abonnement direct
        return [
            'user'             => $user,
            'subscriptionType' => $subscriptionType,
            'status'           => 'direct',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function processStripeSubscription(User $user, string $subscriptionType): array
    {
        // Logique de traitement de l'abonnement Stripe
        return [
            'user'             => $user,
            'subscriptionType' => $subscriptionType,
            'status'           => 'stripe',
        ];
    }

    /**
     * Extrait le niveau d'abonnement à partir du nom de l'abonnement.
     */
    private function getSubscriptionLevelFromName(?string $name): string
    {
        if (!$name) {
            throw new InvalidArgumentException('Nom d\'abonnement invalide');
        }

        // Convertir le nom en minuscules pour la comparaison
        $nameLower = strtolower($name);

        // Mapper les noms vers les niveaux d'abonnement
        if (
            str_contains($nameLower, 'basic') || str_contains($nameLower, 'gratuit') ||
            str_contains($nameLower, 'free')
        ) {
            return 'basic';
        }
        if (str_contains($nameLower, 'premium')) {
            return 'premium';
        }
        if (str_contains($nameLower, 'business') || str_contains($nameLower, 'pro')) {
            return 'business';
        }

        // Par défaut, retourner 'basic'
        return 'basic';
    }
}
