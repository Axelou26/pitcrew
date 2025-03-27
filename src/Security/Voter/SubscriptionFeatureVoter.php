<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\SubscriptionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Psr\Log\LoggerInterface;

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
    private $logger;
    
    public function __construct(SubscriptionService $subscriptionService, LoggerInterface $logger = null)
    {
        $this->subscriptionService = $subscriptionService;
        $this->logger = $logger;
    }
    
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support spécial pour VERIFIED_BADGE quand utilisé avec un User
        if ($attribute === 'VERIFIED_BADGE' && $subject instanceof User) {
            return true;
        }
        
        return in_array($attribute, array_keys(self::FEATURES));
    }
    
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // Cas spécial pour VERIFIED_BADGE avec un User comme sujet
        if ($attribute === 'VERIFIED_BADGE' && $subject instanceof User) {
            // Si l'utilisateur n'est pas un recruteur, il n'a pas besoin de badge vérifié
            if (!$subject->isRecruiter()) {
                return false;
            }
            
            // Si l'utilisateur a un abonnement Business, il devrait avoir accès à cette fonctionnalité
            $activeSubscription = $this->subscriptionService->getActiveSubscription($subject);
            if ($activeSubscription && strtolower($activeSubscription->getSubscription()->getName()) === 'business') {
                // Forcer l'accès si l'abonnement est Business
                $this->log('info', sprintf(
                    'Accès accordé à la fonctionnalité "VERIFIED_BADGE" pour l\'utilisateur #%d avec abonnement Business',
                    $subject->getId()
                ));
                return true;
            }
            
            // Méthode normale de vérification
            $hasAccess = in_array('verified_badge', $this->getFeatures($subject));
            
            if (!$hasAccess) {
                $subscriptionName = $activeSubscription ? $activeSubscription->getSubscription()->getName() : 'Aucun abonnement';
                $this->log('warning', sprintf(
                    'Accès refusé à la fonctionnalité "VERIFIED_BADGE" pour l\'utilisateur #%d. Abonnement actuel: %s',
                    $subject->getId(),
                    $subscriptionName
                ));
            }
            
            return $hasAccess;
        }
        
        // Traitement normal pour les autres cas
        $user = $token->getUser();
        
        if (!$user instanceof UserInterface) {
            // L'utilisateur n'est pas authentifié
            return false;
        }
        
        // Convertir l'attribut en snake_case pour la vérification
        $feature = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $attribute));
        
        // Obtenir les fonctionnalités disponibles pour l'utilisateur
        $features = $this->getFeatures($user);
        
        // Vérifier si l'utilisateur a accès à cette fonctionnalité
        $hasAccess = in_array($feature, $features);
        
        if (!$hasAccess) {
            $activeSubscription = $this->subscriptionService->getActiveSubscription($user);
            $subscriptionName = $activeSubscription ? $activeSubscription->getSubscription()->getName() : 'Aucun abonnement';
            
            $this->log('warning', sprintf(
                'Accès refusé à la fonctionnalité "%s" pour l\'utilisateur #%d. Abonnement actuel: %s',
                $attribute,
                $user->getId(),
                $subscriptionName
            ));
        }
        
        return $hasAccess;
    }
    
    /**
     * Obtient les fonctionnalités disponibles pour un utilisateur en fonction de son abonnement
     */
    private function getFeatures(User $user): array
    {
        $subscription = $this->subscriptionService->getActiveSubscription($user);
        
        if (!$subscription) {
            return [];
        }
        
        // Liste des fonctionnalités par niveau d'abonnement
        $featuresByLevel = [
            'basic' => [
                'post_job_offer',
                'basic_applications',
                'limited_messaging',
                'standard_profile'
            ],
            'premium' => [
                'unlimited_job_offers',
                'highlighted_offers',
                'full_cv_access',
                'unlimited_messaging',
                'basic_statistics',
                'enhanced_profile'
            ],
            'business' => [
                'advanced_candidate_search',
                'automatic_recommendations',
                'detailed_statistics',
                'verified_badge',
                'priority_support'
            ]
        ];
        
        $subscriptionName = strtolower($subscription->getSubscription()->getName());
        
        // Créer les tableaux de fonctionnalités disponibles par niveau en incluant les niveaux inférieurs
        $availableFeatures = [];
        
        if ($subscriptionName === 'basic' || $subscriptionName === 'premium' || $subscriptionName === 'business') {
            $availableFeatures = array_merge($availableFeatures, $featuresByLevel['basic']);
        }
        
        if ($subscriptionName === 'premium' || $subscriptionName === 'business') {
            $availableFeatures = array_merge($availableFeatures, $featuresByLevel['premium']);
        }
        
        if ($subscriptionName === 'business') {
            $availableFeatures = array_merge($availableFeatures, $featuresByLevel['business']);
        }
        
        return $availableFeatures;
    }
    
    /**
     * Journalise un message si un logger est disponible
     */
    private function log(string $level, string $message): void
    {
        if ($this->logger) {
            $this->logger->$level($message);
        }
    }
} 