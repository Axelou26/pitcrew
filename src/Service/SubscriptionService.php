<?php

namespace App\Service;

use App\Config\SubscriptionFeatures;
use App\Entity\User;
use App\Entity\JobOffer;
use App\Entity\RecruiterSubscription;
use App\Repository\RecruiterSubscriptionRepository;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Recruiter;
use InvalidArgumentException;
use App\Entity\Subscription;
use DateTime;

class SubscriptionService
{
    private EntityManagerInterface $entityManager;
    private RecruiterSubscriptionRepository $recruiterSubRepo;
    private JobOfferRepository $jobOfferRepository;
    private Security $security;
    private SubscriptionFeatures $subscriptionFeatures;

    public function __construct(
        EntityManagerInterface $entityManager,
        RecruiterSubscriptionRepository $recruiterSubRepo,
        JobOfferRepository $jobOfferRepository,
        Security $security,
        SubscriptionFeatures $subscriptionFeatures
    ) {
        $this->entityManager = $entityManager;
        $this->recruiterSubRepo = $recruiterSubRepo;
        $this->jobOfferRepository = $jobOfferRepository;
        $this->security = $security;
        $this->subscriptionFeatures = $subscriptionFeatures;
    }

    /**
     * Vérifie si l'utilisateur a un abonnement actif
     */
    public function hasActiveSubscription(User $user): bool
    {
        $subscription = $this->recruiterSubRepo->findActiveSubscription($user);
        return $subscription !== null;
    }

    /**
     * Récupère l'abonnement actif de l'utilisateur
     */
    public function getActiveSubscription(User $user): ?RecruiterSubscription
    {
        $subscription = $this->recruiterSubRepo->findActiveSubscription($user);

        if ($subscription && $subscription->getSubscription()) {
            // Normaliser le nom de l'abonnement pour correspondre aux constantes
            $name = strtolower($subscription->getSubscription()->getName());
            if ($name === 'business') {
                $subscription->getSubscription()->setName(SubscriptionFeatures::LEVEL_BUSINESS);
            } elseif ($name === 'premium') {
                $subscription->getSubscription()->setName(SubscriptionFeatures::LEVEL_PREMIUM);
            } elseif ($name === 'basic') {
                $subscription->getSubscription()->setName(SubscriptionFeatures::LEVEL_BASIC);
            }
        }

        return $subscription;
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

        $subscriptionName = strtolower($subscription->getSubscription()->getName());

        // Si c'est un abonnement Premium ou Business, pas de limite
        if (in_array($subscriptionName, [SubscriptionFeatures::LEVEL_PREMIUM, SubscriptionFeatures::LEVEL_BUSINESS])) {
            return true;
        }

        // Pour l'abonnement Basic, vérifier le nombre d'offres restantes
        if ($subscriptionName === SubscriptionFeatures::LEVEL_BASIC) {
            return $subscription->getRemainingJobOffers() > 0;
        }

        return false;
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

        $subscriptionName = strtolower($subscription->getSubscription()->getName());

        // Si c'est un abonnement Premium ou Business, pas besoin de décrémenter
        if (in_array($subscriptionName, [SubscriptionFeatures::LEVEL_PREMIUM, SubscriptionFeatures::LEVEL_BUSINESS])) {
            return true;
        }

        // Pour l'abonnement Basic, décrémenter le nombre d'offres restantes
        if ($subscriptionName === SubscriptionFeatures::LEVEL_BASIC) {
            $remainingJobOffers = $subscription->getRemainingJobOffers();
            if ($remainingJobOffers > 0) {
                $subscription->setRemainingJobOffers($remainingJobOffers - 1);
                $this->entityManager->persist($subscription);
                $this->entityManager->flush();
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur a accès à une fonctionnalité
     */
    public function hasFeatureAccess(User $user, string $feature): bool
    {
        $subscription = $this->getActiveSubscription($user);

        if (!$subscription) {
            return false;
        }

        $subscriptionName = strtolower($subscription->getSubscription()->getName());
        return $this->subscriptionFeatures->isFeatureAvailableForLevel($feature, $subscriptionName);
    }

    /**
     * Crée un nouvel abonnement pour un recruteur
     */
    public function createSubscription(
        Recruiter $recruiter,
        string $subscriptionLevel,
        array $options = []
    ) {
        if (!$this->subscriptionFeatures->isValidSubscriptionLevel($subscriptionLevel)) {
            throw new InvalidArgumentException('Niveau d\'abonnement invalide');
        }

        // Annuler l'abonnement actif si il existe
        $currentSubscription = $this->getActiveSubscription($recruiter);
        if ($currentSubscription) {
            $this->cancelSubscription($currentSubscription);
        }

        $subscriptionType = new Subscription();
        $subscriptionType->setName(ucfirst($subscriptionLevel));
        $subscriptionType->setFeatures($this->subscriptionFeatures->getAvailableFeatures($subscriptionLevel));

        // Configurer les options de l'abonnement selon le niveau
        switch ($subscriptionLevel) {
            case SubscriptionFeatures::LEVEL_BASIC:
                $subscriptionType->setPrice(0);
                $subscriptionType->setMaxJobOffers(3);
                break;
            case SubscriptionFeatures::LEVEL_PREMIUM:
                $subscriptionType->setPrice(49);
                $subscriptionType->setMaxJobOffers(null);
                break;
            case SubscriptionFeatures::LEVEL_BUSINESS:
                $subscriptionType->setPrice(99);
                $subscriptionType->setMaxJobOffers(null);
                break;
        }

        // Durée par défaut de 30 jours, peut être surchargée par les options
        $subscriptionType->setDuration($options['duration'] ?? 30);
        $subscriptionType->setIsActive(true);

        // Persister le type d'abonnement
        $this->entityManager->persist($subscriptionType);
        $this->entityManager->flush();

        // Créer l'abonnement du recruteur
        $subscription = new RecruiterSubscription();
        $subscription->setRecruiter($recruiter);
        $subscription->setSubscription($subscriptionType);
        $subscription->setStartDate(new DateTime());

        // Calculer la date de fin
        $endDate = new DateTime();
        $endDate->modify('+' . $subscriptionType->getDuration() . ' days');
        $subscription->setEndDate($endDate);

        $subscription->setIsActive(true);
        $subscription->setPaymentStatus('completed');

        // Définir le nombre d'offres d'emploi restantes pour l'abonnement Basic
        if ($subscriptionType->getMaxJobOffers() !== null) {
            $subscription->setRemainingJobOffers($subscriptionType->getMaxJobOffers());
        }

        // Configurer les options supplémentaires
        if (isset($options['auto_renew'])) {
            $subscription->setAutoRenew($options['auto_renew']);
        }
        if (isset($options['stripe_subscription_id'])) {
            $subscription->setStripeSubscriptionId($options['stripe_subscription_id']);
        }

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
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
        $expiredSubscriptions = $this->recruiterSubRepo->findExpiredSubscriptions();

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->setIsActive(false);
            $this->entityManager->persist($subscription);
        }

        $this->entityManager->flush();
    }
}
