<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Recruiter;
use App\Entity\RecruiterSubscription;
use App\Entity\User;
use App\Repository\RecruiterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SubscriptionManager
{
    private EntityManagerInterface $entityManager;
    private SubscriptionService $subscriptionService;
    private StripeService $stripeService;
    private EmailService $emailService;
    private RecruiterSubscriptionRepository $recruiterSubRepo;
    private NotificationService $notificationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionService $subscriptionService,
        StripeService $stripeService,
        EmailService $emailService,
        RecruiterSubscriptionRepository $recruiterSubRepo,
        NotificationService $notificationService
    ) {
        $this->entityManager       = $entityManager;
        $this->subscriptionService = $subscriptionService;
        $this->stripeService       = $stripeService;
        $this->emailService        = $emailService;
        $this->recruiterSubRepo    = $recruiterSubRepo;
        $this->notificationService = $notificationService;
    }

    /**
     * Gère la sélection d'un abonnement.
     *
     * @return array<string, mixed>
     */
    public function handleSubscriptionSelection(
        User $user,
        $subscription,
        $session = null,
        bool $testMode = false,
        bool $offlineMode = false
    ): array {
        // Vérifier si l'utilisateur est un recruteur
        if (!$user instanceof Recruiter) {
            // Récupérer le Recruiter correspondant
            $recruiter = $this->entityManager->getRepository(Recruiter::class)->find($user->getId());
            if (!$recruiter) {
                throw new InvalidArgumentException('L\'utilisateur doit être un recruteur pour créer un abonnement.');
            }
            $user = $recruiter;
        }

        // Extraire le niveau d'abonnement en fonction du type de l'argument
        $subscriptionLevel = '';
        if (is_string($subscription)) {
            $subscriptionLevel = $subscription;
        } elseif ($subscription instanceof \App\Entity\Subscription) {
            $subscriptionLevel = $subscription->getName();
        }

        if (empty($subscriptionLevel)) {
            throw new InvalidArgumentException('Le niveau d\'abonnement doit être une
                                                 chaîne ou un objet Subscription.');
        }

        $activeSubscription = $this->subscriptionService->getActiveSubscription($user);

        if ($this->isAlreadySubscribed($activeSubscription, $subscriptionLevel)) {
            return ['redirect' => 'manage', 'message' => 'Vous êtes déjà abonné à ce plan.'];
        }

        if ($this->shouldProcessDirectly($subscriptionLevel)) {
            return $this->processDirectSubscription($user, $subscriptionLevel);
        }

        return $this->processStripeSubscription($user, $subscriptionLevel);
    }

    /**
     * Gère le succès d'un abonnement.
     *
     * @return array<string, mixed>
     */
    public function handleSubscriptionSuccess(
        string $sessionId,
        ?User $user = null,
        ?SessionInterface $session = null,
        bool $offlineMode = false
    ): array {
        $subscription = $this->subscriptionService->find($sessionId);
        if (!$subscription) {
            throw new RuntimeException('Abonnement non trouvé');
        }

        $isChange           = $this->stripeService->isOfflineMode();
        $activeSubscription = $this->subscriptionService->getActiveSubscription($subscription->getUser());

        if ($activeSubscription && $isChange) {
            $this->subscriptionService->cancelSubscription($activeSubscription);
        }

        $recruiterSub = $this->subscriptionService->createSubscription(
            $subscription->getUser(),
            $subscription->getName()
        );

        if ($isChange) {
            $recruiterSub->setPaymentStatus('offline_mode');
            $this->entityManager->flush();
        }

        $this->emailService->sendSubscriptionConfirmation($subscription->getUser(), $recruiterSub);

        if ($subscription->getPrice() > 0) {
            $this->emailService->sendPaymentReceipt($subscription->getUser(), $recruiterSub);
        }

        $message = $isChange
            ? sprintf(
                'Votre abonnement a été mis à jour avec succès vers %s !',
                $subscription->getName()
            )
            : sprintf('Votre abonnement %s a été activé avec succès !', $subscription->getName());

        return ['message' => $message];
    }

    public function cancelSubscription(
        RecruiterSubscription $subscription,
        User $user
    ): void {
        if ($subscription->getStripeSubscriptionId() && !$this->stripeService->isOfflineMode()) {
            $this->stripeService->cancelRecurringSubscription($subscription);
        }

        $subscription->setCancelled(true)
            ->setAutoRenew(false);

        $this->entityManager->flush();
        $this->emailService->sendSubscriptionCancellationConfirmation($user, $subscription);
    }

    /**
     * Traite un abonnement direct (sans Stripe).
     *
     * @return array<string, mixed>
     */
    public function processDirectSubscription(User $user, string $subscriptionLevel): array
    {
        if (!$user instanceof Recruiter) {
            throw new InvalidArgumentException(
                'L\'utilisateur doit être un recruteur pour créer un abonnement.'
            );
        }

        $subscriptionLevel = strtolower($subscriptionLevel);
        if ($subscriptionLevel === null) {
            throw new RuntimeException('Niveau d\'abonnement invalide');
        }

        $recruiterSub = $this->subscriptionService->createSubscription(
            $user,
            $subscriptionLevel
        );

        if ($this->stripeService->isOfflineMode()) {
            return [
                'redirect' => 'manage',
                'message'  => 'Votre abonnement a été activé en mode test !',
            ];
        }

        return [
            'redirect' => 'manage',
            'message'  => 'Votre abonnement gratuit a été activé avec succès !',
        ];
    }

    /**
     * Traite un abonnement Stripe.
     *
     * @return array<string, mixed>
     */
    public function processStripeSubscription(User $user, string $subscriptionLevel): array
    {
        try {
            $sessionId = $this->stripeService->createCheckoutSession($user, $subscriptionLevel);

            return [
                'success'      => true,
                'session_id'   => $sessionId,
                'redirect_url' => $this->stripeService->generateCheckoutUrl($sessionId),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    private function isAlreadySubscribed(
        ?RecruiterSubscription $activeSubscription,
        string $subscriptionLevel
    ): bool {
        return $activeSubscription && strtolower($activeSubscription->getSubscription()->getName())
        === strtolower($subscriptionLevel);
    }

    private function shouldProcessDirectly(string $subscriptionLevel): bool
    {
        return $subscriptionLevel === 'free' || $this->stripeService->isOfflineMode();
    }
}
