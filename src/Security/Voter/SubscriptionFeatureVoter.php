<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\SubscriptionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SubscriptionFeatureVoter extends Voter
{
    // Définir les features avec leurs descriptions
    public const FEATURES = [
        // Basic (gratuit ou niveau de base)
        'POST_JOB_OFFER' => 'Permet de publier des offres d\'emploi (limité pour Basic)',
        'BASIC_APPLICATIONS' => 'Accès aux candidatures de base',
        'LIMITED_MESSAGING' => 'Messagerie limitée',
        'STANDARD_PROFILE' => 'Profil entreprise standard',
        
        // Premium
        'UNLIMITED_JOB_OFFERS' => 'Permet de publier un nombre illimité d\'offres d\'emploi',
        'HIGHLIGHTED_OFFERS' => 'Offres d\'emploi mises en avant',
        'FULL_CV_ACCESS' => 'Accès complet aux CV des candidats',
        'UNLIMITED_MESSAGING' => 'Messagerie illimitée',
        'BASIC_STATISTICS' => 'Statistiques de base',
        'ENHANCED_PROFILE' => 'Profil entreprise amélioré',
        
        // Business
        'ADVANCED_CANDIDATE_SEARCH' => 'Recherche avancée de candidats',
        'AUTOMATIC_RECOMMENDATIONS' => 'Recommandations automatiques de candidats',
        'DETAILED_STATISTICS' => 'Statistiques détaillées',
        'VERIFIED_BADGE' => 'Badge vérifié "Entreprise vérifiée"',
        'PRIORITY_SUPPORT' => 'Support prioritaire'
    ];
    
    private $subscriptionService;
    
    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }
    
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, array_keys(self::FEATURES));
    }
    
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof UserInterface) {
            return false;
        }
        
        // Convertir l'attribut en snake_case pour la fonction hasAccessToPremiumFeature
        $feature = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $attribute));
        
        // Vérifier si l'utilisateur a accès à cette fonctionnalité
        return $this->subscriptionService->hasAccessToPremiumFeature($user, $feature);
    }
} 