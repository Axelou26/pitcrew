<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;

class CompanyVerificationService
{
    private SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Vérifie si l'entreprise a droit au badge vérifié.
     */
    public function hasVerifiedBadge(User $user): bool
    {
        // Vérifier si l'utilisateur a accès à la fonctionnalité "verified_badge"
        return $this->subscriptionService->hasAccessToPremiumFeature($user, 'verified_badge');
    }

    /**
     * Renvoie les détails de vérification de l'entreprise.
     *
     * @return array<string, mixed>
     */
    public function getVerificationDetails(User $user): array
    {
        if (!$this->hasVerifiedBadge($user)) {
            return [
                'verified' => false,
                'message'  => 'Cette fonctionnalité est disponible uniquement avec l\'abonnement Business',
                'level'    => 'none',
            ];
        }

        // Pour un abonné Business, renvoyer les détails de vérification
        $subscription = $this->subscriptionService->getActiveSubscription($user);
        $startDate    = $subscription?->getStartDate();

        return [
            'verified'   => true,
            'message'    => 'Entreprise vérifiée par PitCrew',
            'level'      => 'business',
            'since'      => $startDate ? $startDate->format('d/m/Y') : 'N/A',
            'badge_info' => 'Ce badge certifie que cette entreprise est un partenaire de confiance de PitCrew',
        ];
    }

    /**
     * Renvoie le HTML du badge vérifié pour affichage.
     */
    public function getVerifiedBadgeHtml(User $user): string
    {
        if (!$this->hasVerifiedBadge($user)) {
            return '';
        }

        return '<span class="verified-badge" title="Entreprise vérifiée par PitCrew">
                    <i class="bi bi-patch-check-fill text-info"></i>
                </span>';
    }
}
