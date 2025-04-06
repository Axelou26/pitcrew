<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CompanyVerificationService
{
    private $entityManager;
    private $security;
    private $subscriptionService;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        SubscriptionService $subscriptionService
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->subscriptionService = $subscriptionService;
    }
    
    /**
     * Vérifie si l'entreprise a droit au badge vérifié
     */
    public function hasVerifiedBadge(User $user): bool
    {
        // Vérifier si l'utilisateur a accès à la fonctionnalité "verified_badge"
        return $this->subscriptionService->hasAccessToPremiumFeature($user, 'verified_badge');
    }
    
    /**
     * Renvoie les détails de vérification de l'entreprise
     */
    public function getVerificationDetails(User $user): array
    {
        if (!$this->hasVerifiedBadge($user)) {
            return [
                'verified' => false,
                'message' => 'Cette fonctionnalité est disponible uniquement avec l\'abonnement Business',
                'level' => 'none'
            ];
        }
        
        // Pour un abonné Business, renvoyer les détails de vérification
        $subscription = $this->subscriptionService->getActiveSubscription($user);
        
        return [
            'verified' => true,
            'message' => 'Entreprise vérifiée par PitCrew',
            'level' => 'business',
            'since' => $subscription ? $subscription->getStartDate()->format('d/m/Y') : 'N/A',
            'badge_info' => 'Ce badge certifie que cette entreprise est un partenaire de confiance de PitCrew'
        ];
    }
    
    /**
     * Renvoie le HTML du badge vérifié pour affichage 
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