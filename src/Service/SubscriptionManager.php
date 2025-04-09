<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Subscription;
use App\Entity\RecruiterSubscription;
use App\Repository\SubscriptionRepository;
use App\Repository\RecruiterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SubscriptionManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SubscriptionRepository $subscriptionRepo,
        private readonly RecruiterSubscriptionRepository $recruiterSubRepo,
        private readonly StripeService $stripeService,
        private readonly SubscriptionService $subscriptionService,
        private readonly EmailService $emailService,
        private readonly NotificationService $notificationService
    ) {
    }

    public function handleSubscriptionSelection(
        User $user,
        Subscription $subscription,
        SessionInterface $session,
        bool $isTestMode = false,
        bool $isOfflineMode = false
    ): array {
        $activeSubscription = $this->subscriptionService->getActiveSubscription($user);

        if ($this->isAlreadySubscribed($activeSubscription, $subscription)) {
            return ['redirect' => 'manage', 'message' => 'Vous êtes déjà abonné à ce plan.'];
        }

        if ($this->shouldProcessDirectly($subscription, $isTestMode, $isOfflineMode)) {
            return $this->processDirectSubscription($user, $subscription, $isTestMode, $isOfflineMode);
        }

        return $this->processStripeSubscription($subscription, $activeSubscription, $session);
    }

    public function handleSubscriptionSuccess(
        string $subscriptionId,
        User $user,
        SessionInterface $session,
        bool $isOfflineMode = false
    ): array {
        $subscription = $this->subscriptionRepo->find($subscriptionId);
        if (!$subscription) {
            throw new \RuntimeException('Abonnement non trouvé');
        }

        $isChange = $session->get('is_subscription_change', false);
        $activeSubscription = $this->subscriptionService->getActiveSubscription($user);

        if ($activeSubscription && $isChange) {
            $this->subscriptionService->cancelSubscription($activeSubscription);
        }

        $recruiterSub = $this->subscriptionService->createSubscription($user, $subscription);

        if ($isOfflineMode) {
            $recruiterSub->setPaymentStatus('offline_mode');
            $this->entityManager->flush();
        }

        $this->emailService->sendSubscriptionConfirmation($user, $recruiterSub);

        if ($subscription->getPrice() > 0) {
            $this->emailService->sendPaymentReceipt($user, $recruiterSub);
        }

        $message = $isChange
            ? sprintf('Votre abonnement a été mis à jour avec succès vers %s !', $subscription->getName())
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

    private function isAlreadySubscribed(?RecruiterSubscription $activeSubscription, Subscription $subscription): bool
    {
        return $activeSubscription && $activeSubscription->getSubscription()->getId() === $subscription->getId();
    }

    private function shouldProcessDirectly(
        Subscription $subscription,
        bool $isTestMode,
        bool $isOfflineMode
    ): bool {
        return $subscription->getPrice() == 0 || $isTestMode || $isOfflineMode;
    }

    private function processDirectSubscription(
        User $user,
        Subscription $subscription,
        bool $isTestMode,
        bool $isOfflineMode
    ): array {
        $recruiterSub = $this->subscriptionService->createSubscription($user, $subscription);

        if ($isTestMode || $isOfflineMode) {
            return [
                'redirect' => 'manage',
                'message' => 'Votre abonnement a été activé en mode test !'
            ];
        }

        return [
            'redirect' => 'manage',
            'message' => 'Votre abonnement gratuit a été activé avec succès !'
        ];
    }

    private function processStripeSubscription(
        Subscription $subscription,
        ?RecruiterSubscription $activeSubscription,
        SessionInterface $session
    ): array {
        try {
            $session->set('pending_subscription_id', $subscription->getId());
            $session->set('is_subscription_change', $activeSubscription ? true : false);

            $stripeSession = $this->stripeService->createCheckoutSession($subscription);
            return ['redirect_url' => $stripeSession->url];
        } catch (\Exception $e) {
            return [
                'redirect' => 'plans',
                'error' => 'Erreur lors de la création de la session de paiement : ' . $e->getMessage()
            ];
        }
    }
} 