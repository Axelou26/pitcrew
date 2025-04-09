<?php

namespace App\Security\Voter;

use App\Config\SubscriptionFeatures;
use App\Entity\User;
use App\Service\SubscriptionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Psr\Log\LoggerInterface;

class SubscriptionFeatureVoter extends Voter
{
    private const VERIFIED_BADGE_FEATURE = 'verified_badge';

    private $subscriptionService;
    private $logger;
    private $features;
    private $businessLevel;

    public function __construct(
        SubscriptionService $subscriptionService,
        LoggerInterface $logger = null,
        array $features = null
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->logger = $logger;
        $this->features = $features ?? SubscriptionFeatures::FEATURES_DESCRIPTIONS;
        $this->businessLevel = SubscriptionFeatures::LEVEL_BUSINESS;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support spécial pour VERIFIED_BADGE quand utilisé avec un User
        if (strtolower($attribute) === self::VERIFIED_BADGE_FEATURE && $subject instanceof User) {
            return true;
        }

        // Vérifier si la fonctionnalité existe dans au moins un niveau d'abonnement
        foreach (SubscriptionFeatures::SUBSCRIPTION_LEVELS as $level) {
            $features = SubscriptionFeatures::getAvailableFeatures($level);
            if (in_array(strtolower($attribute), array_map('strtolower', $features))) {
                return true;
            }
        }

        return false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        $feature = $this->normalizeFeatureName($attribute);
        $activeSubscription = $this->subscriptionService->getActiveSubscription($user);
        $subscriptionName = $this->getSubscriptionName($activeSubscription);

        if ($this->isVerifiedBadgeFeature($feature, $subject)) {
            return $this->handleVerifiedBadgeAccess($subject, $subscriptionName);
        }

        return $this->handleFeatureAccess($user, $feature, $subscriptionName);
    }

    private function normalizeFeatureName(string $attribute): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $attribute));
    }

    private function getSubscriptionName(?object $subscription): ?string
    {
        return $subscription ? strtolower($subscription->getSubscription()->getName()) : null;
    }

    private function isVerifiedBadgeFeature(string $feature, mixed $subject): bool
    {
        return $feature === self::VERIFIED_BADGE_FEATURE && $subject instanceof User;
    }

    private function handleVerifiedBadgeAccess(User $subject, ?string $subscriptionName): bool
    {
        if (!$subject->isRecruiter()) {
            return false;
        }

        if ($subscriptionName === $this->businessLevel) {
            $this->logAccess($subject->getId(), self::VERIFIED_BADGE_FEATURE, true);
            return true;
        }

        return false;
    }

    private function handleFeatureAccess(UserInterface $user, string $feature, ?string $subscriptionName): bool
    {
        if (!$subscriptionName || !$this->isValidSubscriptionLevel($subscriptionName)) {
            $this->logAccess($user->getId(), $feature, false, 'Aucun abonnement valide');
            return false;
        }

        $hasAccess = $this->isFeatureAvailableForLevel($feature, $subscriptionName);

        if (!$hasAccess) {
            $this->logAccess($user->getId(), $feature, false, $subscriptionName);
        }

        return $hasAccess;
    }

    private function isValidSubscriptionLevel(string $level): bool
    {
        return in_array(strtolower($level), array_map('strtolower', SubscriptionFeatures::SUBSCRIPTION_LEVELS));
    }

    private function isFeatureAvailableForLevel(string $feature, string $level): bool
    {
        return SubscriptionFeatures::isFeatureAvailableForLevel($feature, $level);
    }

    private function getFeatures(User $user): array
    {
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        if (!$subscription) {
            return [];
        }

        $subscriptionName = strtolower($subscription->getSubscription()->getName());
        return $this->features[$subscriptionName] ?? [];
    }

    private function logAccess(int $userId, string $feature, bool $granted, ?string $subscriptionName = null): void
    {
        if (!$this->logger) {
            return;
        }

        $message = $granted
            ? sprintf('Accès accordé à la fonctionnalité "%s" pour l\'utilisateur #%d', $feature, $userId)
            : sprintf(
                'Accès refusé à la fonctionnalité "%s" pour l\'utilisateur #%d. Abonnement actuel: %s',
                $feature,
                $userId,
                $subscriptionName ?? 'Aucun abonnement'
            );

        $this->logger->{$granted ? 'info' : 'warning'}($message);
    }
}
