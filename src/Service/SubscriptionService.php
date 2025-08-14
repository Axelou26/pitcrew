<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\SubscriptionFeatures;
use App\Entity\Recruiter;
use App\Entity\RecruiterSubscription;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\JobOfferRepository;
use App\Repository\RecruiterSubscriptionRepository;
use App\Repository\SubscriptionRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;

class SubscriptionService
{
    private EntityManagerInterface $entityManager;
    private RecruiterSubscriptionRepository $recruiterSubscriptionRepository;
    private SubscriptionRepository $subscriptionRepository;
    private JobOfferRepository $jobOfferRepository;
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        RecruiterSubscriptionRepository $recruiterSubscriptionRepository,
        SubscriptionRepository $subscriptionRepository,
        JobOfferRepository $jobOfferRepository,
        Security $security
    ) {
        $this->entityManager                   = $entityManager;
        $this->recruiterSubscriptionRepository = $recruiterSubscriptionRepository;
        $this->subscriptionRepository          = $subscriptionRepository;
        $this->jobOfferRepository              = $jobOfferRepository;
        $this->security                        = $security;
    }

    /**
     * Vérifie si l'utilisateur a un abonnement actif.
     */
    public function hasActiveSubscription(User $user): bool
    {
        $subscription = $this->recruiterSubscriptionRepository->findActiveSubscription($user);

        return $subscription !== null;
    }

    /**
     * Récupère l'abonnement actif de l'utilisateur.
     */
    public function getActiveSubscription(User $user): ?RecruiterSubscription
    {
        $subscription = $this->recruiterSubscriptionRepository->findActiveSubscription($user);

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
     * Vérifie si l'utilisateur peut publier une nouvelle offre d'emploi.
     */
    public function canPostJobOffer(User $user): bool
    {
        $subscription = $this->getActiveSubscription($user);

        if (!$subscription) {
            return false;
        }

        $subscriptionName = strtolower($subscription->getSubscription()->getName());

        // Si c'est un abonnement Premium ou Business, pas de limite
        if (
            in_array(
                $subscriptionName,
                [SubscriptionFeatures::LEVEL_PREMIUM, SubscriptionFeatures::LEVEL_BUSINESS],
                true
            )
        ) {
            return true;
        }

        // Pour l'abonnement Basic, vérifier le nombre d'offres restantes
        if ($subscriptionName === SubscriptionFeatures::LEVEL_BASIC) {
            return $subscription->getRemainingJobOffers() > 0;
        }

        return false;
    }

    /**
     * Décrémente le nombre d'offres d'emploi restantes pour un abonnement Basic.
     */
    public function decrementRemainingJobOffers(User $user): bool
    {
        $subscription = $this->getActiveSubscription($user);

        if (!$subscription) {
            return false;
        }

        $subscriptionName = strtolower($subscription->getSubscription()->getName());

        // Si c'est un abonnement Premium ou Business, pas besoin de décrémenter
        if (
            in_array(
                $subscriptionName,
                [SubscriptionFeatures::LEVEL_PREMIUM, SubscriptionFeatures::LEVEL_BUSINESS],
                true
            )
        ) {
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
     * Vérifie si l'utilisateur a accès à une fonctionnalité.
     */
    public function hasFeatureAccess(User $user, string $feature): bool
    {
        $subscription = $this->getActiveSubscription($user);

        if (!$subscription) {
            return false;
        }

        $subscriptionName = strtolower($subscription->getSubscription()->getName());

        return SubscriptionFeatures::isFeatureAvailableForLevel($feature, $subscriptionName);
    }

    /**
     * Crée un abonnement pour un recruteur.
     *
     * @param array<string, mixed> $options
     */
    public function createSubscription(
        Recruiter $recruiter,
        string $subscriptionLevel,
        array $options = []
    ): RecruiterSubscription {
        if (!SubscriptionFeatures::isValidSubscriptionLevel($subscriptionLevel)) {
            throw new InvalidArgumentException('Niveau d\'abonnement invalide');
        }

        // Annuler l'abonnement actif si il existe
        $currentSubscription = $this->getActiveSubscription($recruiter);
        if ($currentSubscription) {
            $this->cancelSubscription($currentSubscription);
        }

        $subscriptionType = new Subscription();
        $subscriptionType->setName(ucfirst($subscriptionLevel));
        $subscriptionType->setFeatures(SubscriptionFeatures::getAvailableFeatures($subscriptionLevel));

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
     * Annule un abonnement.
     */
    public function cancelSubscription(RecruiterSubscription $subscription): void
    {
        $subscription->setCancelled(true);
        $subscription->setAutoRenew(false);

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

    /**
     * Vérifie et met à jour le statut des abonnements expirés.
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

    public function getActiveSubscriptionForRecruiter(Recruiter $recruiter): ?RecruiterSubscription
    {
        return $this->recruiterSubscriptionRepository->findActiveSubscription($recruiter);
    }

    public function hasAccessToPremiumFeature(User $user, string $feature): bool
    {
        if (!$user instanceof Recruiter) {
            return false;
        }

        $subscription = $this->getActiveSubscriptionForRecruiter($user);
        if (!$subscription) {
            return false;
        }

        $subscriptionLevel = $subscription->getSubscription()->getName();

        return SubscriptionFeatures::isFeatureAvailableForLevel($feature, $subscriptionLevel);
    }

    public function findByStripeSubscriptionId(string $stripeSubscriptionId): ?RecruiterSubscription
    {
        return $this->recruiterSubscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSubscriptionId]);
    }

    public function handlePaymentSucceeded(string $stripeSubscriptionId): void
    {
        $subscription = $this->findByStripeSubscriptionId($stripeSubscriptionId);
        if ($subscription) {
            $subscription->setPaymentStatus('paid');
            $subscription->setIsActive(true);
            $this->entityManager->flush();
        }
    }

    public function find(int $id): ?RecruiterSubscription
    {
        return $this->recruiterSubscriptionRepository->find($id);
    }
}
