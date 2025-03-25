<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\JobOffer;
use App\Entity\RecruiterSubscription;
use App\Repository\RecruiterSubscriptionRepository;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Recruiter;

class SubscriptionService
{
    private $recruiterSubscriptionRepository;
    private $jobOfferRepository;
    private $entityManager;
    private $security;

    public function __construct(
        RecruiterSubscriptionRepository $recruiterSubscriptionRepository,
        JobOfferRepository $jobOfferRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ) {
        $this->recruiterSubscriptionRepository = $recruiterSubscriptionRepository;
        $this->jobOfferRepository = $jobOfferRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * Vérifie si l'utilisateur a un abonnement actif
     */
    public function hasActiveSubscription(User $user): bool
    {
        $subscription = $this->recruiterSubscriptionRepository->findActiveSubscription($user);
        return $subscription !== null;
    }

    /**
     * Récupère l'abonnement actif de l'utilisateur
     */
    public function getActiveSubscription(User $user): ?RecruiterSubscription
    {
        return $this->recruiterSubscriptionRepository->findActiveSubscription($user);
    }

    /**
     * Vérifie si l'utilisateur peut publier une nouvelle offre d'emploi
     */
    public function canPostJobOffer(User $user): bool
    {
        $subscription = $this->getActiveSubscription($user);
        
        if (!$subscription) {
            return false;
        }
        
        // Si c'est un abonnement Premium ou Business, pas de limite
        $subscriptionName = strtolower($subscription->getSubscription()->getName());
        if (in_array($subscriptionName, ['premium', 'business'])) {
            return true;
        }
        
        // Pour l'abonnement Basic, vérifier le nombre d'offres restantes
        $remainingJobOffers = $subscription->getRemainingJobOffers();
        return $remainingJobOffers > 0;
    }

    /**
     * Décrémente le nombre d'offres d'emploi restantes pour un abonnement Basic
     */
    public function decrementRemainingJobOffers(User $user): bool
    {
        $subscription = $this->getActiveSubscription($user);
        
        if (!$subscription) {
            return false;
        }
        
        // Si c'est un abonnement Premium ou Business, pas besoin de décrémenter
        $subscriptionName = strtolower($subscription->getSubscription()->getName());
        if (in_array($subscriptionName, ['premium', 'business'])) {
            return true;
        }
        
        // Pour l'abonnement Basic, décrémenter le nombre d'offres restantes
        $remainingJobOffers = $subscription->getRemainingJobOffers();
        if ($remainingJobOffers > 0) {
            $subscription->setRemainingJobOffers($remainingJobOffers - 1);
            $this->entityManager->persist($subscription);
            $this->entityManager->flush();
            return true;
        }
        
        return false;
    }

    /**
     * Vérifie si l'utilisateur a accès à une fonctionnalité premium
     */
    public function hasAccessToPremiumFeature(User $user, string $feature): bool
    {
        $subscription = $this->getActiveSubscription($user);
        
        if (!$subscription) {
            return false;
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
        
        // Vérifier si la fonctionnalité est disponible pour le niveau d'abonnement
        if ($subscriptionName === 'premium') {
            return in_array($feature, array_merge($featuresByLevel['basic'], $featuresByLevel['premium']));
        } elseif ($subscriptionName === 'business') {
            return in_array($feature, array_merge($featuresByLevel['basic'], $featuresByLevel['premium'], $featuresByLevel['business']));
        } else {
            return in_array($feature, $featuresByLevel['basic']);
        }
    }

    /**
     * Crée un nouvel abonnement pour un recruteur
     */
    public function createSubscription(User $user, $subscriptionType): RecruiterSubscription
    {
        // Vérification si l'utilisateur est un recruteur ou conversion si nécessaire
        $recruiter = null;
        
        if ($user instanceof Recruiter) {
            // Si c'est déjà un objet Recruiter, on l'utilise tel quel
            $recruiter = $user;
        } else {
            // Si c'est un objet User standard, vérifier s'il a un ID (s'il est persisté)
            if ($user->getId()) {
                // L'utilisateur existe en base, nous devons utiliser une requête pour récupérer l'instance Recruiter correspondante
                $recruiterRepository = $this->entityManager->getRepository(Recruiter::class);
                
                // Premièrement, essayons de trouver un Recruiter avec le même ID
                $recruiter = $recruiterRepository->find($user->getId());
                
                if (!$recruiter) {
                    // Si nous n'avons pas trouvé de Recruiter, nous devons créer une requête directe à la base
                    // Cette requête récupère l'entité User en tant que Recruiter
                    $conn = $this->entityManager->getConnection();
                    $sql = 'UPDATE user SET discr = :discr WHERE id = :id';
                    $stmt = $conn->prepare($sql);
                    $stmt->executeStatement([
                        'discr' => 'recruiter',
                        'id' => $user->getId(),
                    ]);

                    // Ensuite, nous devons vider l'unité de travail pour que Doctrine recharge l'entité
                    $this->entityManager->clear();
                    
                    // Récupérer l'entité nouvellement modifiée en tant que Recruiter
                    $recruiter = $recruiterRepository->find($user->getId());
                    
                    // Si nous n'avons toujours pas de Recruiter, c'est une erreur
                    if (!$recruiter) {
                        throw new \RuntimeException("Impossible de convertir l'utilisateur en recruteur.");
                    }
                    
                    // Assurons-nous que le recruteur a le rôle ROLE_RECRUTEUR
                    if (!in_array('ROLE_RECRUTEUR', $recruiter->getRoles())) {
                        $roles = $recruiter->getRoles();
                        $roles[] = 'ROLE_RECRUTEUR';
                        $recruiter->setRoles($roles);
                    }
                    
                    // Si le recruteur n'a pas de nom d'entreprise, lui en assigner un
                    if (!$recruiter->getCompanyName()) {
                        $recruiter->setCompanyName($recruiter->getLastName() . ' Company');
                    }
                    
                    $this->entityManager->flush();
                }
            } else {
                // L'utilisateur n'est pas encore persisté, c'est un cas spécial
                throw new \InvalidArgumentException("L'utilisateur doit être persisté avant de créer un abonnement.");
            }
        }
        
        // S'assurer que l'objet Subscription est persisté
        if ($subscriptionType->getId() === null) {
            $this->entityManager->persist($subscriptionType);
            $this->entityManager->flush();
        } else {
            // Si l'objet a déjà un ID, vérifions qu'il est bien géré par l'EntityManager
            $subscriptionRepository = $this->entityManager->getRepository(\App\Entity\Subscription::class);
            $managedSubscription = $subscriptionRepository->find($subscriptionType->getId());
            if (!$managedSubscription) {
                // Si l'objet n'est pas géré, on le persiste
                $this->entityManager->persist($subscriptionType);
                $this->entityManager->flush();
            } else {
                // Utiliser l'objet géré par l'EntityManager
                $subscriptionType = $managedSubscription;
            }
        }
        
        $recruiterSubscription = new RecruiterSubscription();
        $recruiterSubscription->setRecruiter($recruiter); // Maintenant, $recruiter est une instance de Recruiter
        $recruiterSubscription->setSubscription($subscriptionType);
        $recruiterSubscription->setStartDate(new \DateTime());
        
        // Calcul de la date de fin en fonction de la durée de l'abonnement
        $endDate = new \DateTime();
        $endDate->modify('+' . $subscriptionType->getDuration() . ' days');
        $recruiterSubscription->setEndDate($endDate);
        
        $recruiterSubscription->setIsActive(true);
        $recruiterSubscription->setPaymentStatus('completed');
        
        // Définir le nombre d'offres d'emploi restantes si c'est un abonnement Basic
        if ($subscriptionType->getMaxJobOffers() !== null) {
            $recruiterSubscription->setRemainingJobOffers($subscriptionType->getMaxJobOffers());
        }
        
        $this->entityManager->persist($recruiterSubscription);
        $this->entityManager->flush();
        
        return $recruiterSubscription;
    }

    /**
     * Annule un abonnement
     */
    public function cancelSubscription(RecruiterSubscription $subscription): void
    {
        $subscription->setCancelled(true);
        $subscription->setAutoRenew(false);
        
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

    /**
     * Vérifie et met à jour le statut des abonnements expirés
     */
    public function checkExpiredSubscriptions(): void
    {
        $expiredSubscriptions = $this->recruiterSubscriptionRepository->findExpiredSubscriptions();
        
        foreach ($expiredSubscriptions as $subscription) {
            $subscription->setIsActive(false);
            $this->entityManager->persist($subscription);
        }
        
        $this->entityManager->flush();
    }
} 