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

    public function __construct(SubscriptionService $subscriptionService, LoggerInterface $logger = null)
    {
        $this->subscriptionService = $subscriptionService;
        $this->logger = $logger;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support spécial pour VERIFIED_BADGE quand utilisé avec un User
        if (strtolower($attribute) === self::VERIFIED_BADGE_FEATURE && $subject instanceof User) {
            return true;
        }

        return array_key_exists(strtolower($attribute), SubscriptionFeatures::FEATURES_DESCRIPTIONS);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        $feature = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $attribute));
        $activeSubscription = $this->subscriptionService->getActiveSubscription($user);
        $subscriptionName = $activeSubscription ? strtolower($activeSubscription->getSubscription()->getName()) : null;

        // Cas spécial pour VERIFIED_BADGE avec un User comme sujet
        if ($feature === self::VERIFIED_BADGE_FEATURE && $subject instanceof User) {
            if (!$subject->isRecruiter()) {
                return false;
            }

            if ($subscriptionName === SubscriptionFeatures::LEVEL_BUSINESS) {
                $this->logAccess($subject->getId(), $feature, true);
                return true;
            }
        }

        if (!$subscriptionName || !SubscriptionFeatures::isValidSubscriptionLevel($subscriptionName)) {
            $this->logAccess($user->getId(), $feature, false, 'Aucun abonnement valide');
            return false;
        }

        $hasAccess = SubscriptionFeatures::isFeatureAvailableForLevel($feature, $subscriptionName);

        if (!$hasAccess) {
            $this->logAccess($user->getId(), $feature, false, $subscriptionName);
        }

        return $hasAccess;
    }

    private function getFeatures(User $user): array
    {
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        if (!$subscription) {
            return [];
        }

        $subscriptionName = strtolower($subscription->getSubscription()->getName());
        return SubscriptionFeatures::getAvailableFeatures($subscriptionName);
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
