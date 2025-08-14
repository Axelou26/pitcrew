<?php

declare(strict_types=1);

namespace App\Config;

class SubscriptionFeatures
{
    public const LEVEL_BASIC    = 'basic';
    public const LEVEL_PREMIUM  = 'premium';
    public const LEVEL_BUSINESS = 'business';

    public const SUBSCRIPTION_LEVELS = [
        self::LEVEL_BASIC,
        self::LEVEL_PREMIUM,
        self::LEVEL_BUSINESS,
    ];

    public const FEATURES_DESCRIPTIONS = [
        // Basic (gratuit ou niveau de base)
        'post_job_offer'     => 'Permet de publier des offres d\'emploi (limité pour Basic)',
        'basic_applications' => 'Accès aux candidatures de base',
        'limited_messaging'  => 'Messagerie limitée',
        'standard_profile'   => 'Profil entreprise standard',

        // Premium
        'unlimited_job_offers' => 'Permet de publier un nombre illimité d\'offres d\'emploi',
        'highlighted_offers'   => 'Offres d\'emploi mises en avant',
        'full_cv_access'       => 'Accès complet aux CV des candidats',
        'unlimited_messaging'  => 'Messagerie illimitée',
        'basic_statistics'     => 'Statistiques de base',
        'enhanced_profile'     => 'Profil entreprise amélioré',

        // Business
        'advanced_candidate_search' => 'Recherche avancée de candidats',
        'automatic_recommendations' => 'Recommandations automatiques',
        'detailed_statistics'       => 'Statistiques détaillées',
        'verified_badge'            => 'Badge "Entreprise vérifiée"',
        'priority_support'          => 'Support prioritaire',
    ];

    public const FEATURES_BY_LEVEL = [
        self::LEVEL_BASIC => [
            'post_job_offer',
            'basic_applications',
            'limited_messaging',
            'standard_profile',
        ],
        self::LEVEL_PREMIUM => [
            'post_job_offer',
            'unlimited_job_offers',
            'highlighted_offers',
            'full_cv_access',
            'unlimited_messaging',
            'basic_statistics',
            'enhanced_profile',
        ],
        self::LEVEL_BUSINESS => [
            'post_job_offer',
            'unlimited_job_offers',
            'advanced_candidate_search',
            'automatic_recommendations',
            'detailed_statistics',
            'verified_badge',
            'priority_support',
        ],
    ];

    public static function getAvailableFeatures(string $subscriptionLevel): array
    {
        $subscriptionLevel = strtolower($subscriptionLevel);

        if (!\in_array($subscriptionLevel, self::SUBSCRIPTION_LEVELS, true)) {
            return [];
        }

        $availableFeatures = [];

        if (\in_array($subscriptionLevel, [self::LEVEL_BASIC, self::LEVEL_PREMIUM, self::LEVEL_BUSINESS], true)) {
            $availableFeatures = array_merge($availableFeatures, self::FEATURES_BY_LEVEL[self::LEVEL_BASIC]);
        }

        if (\in_array($subscriptionLevel, [self::LEVEL_PREMIUM, self::LEVEL_BUSINESS], true)) {
            $availableFeatures = array_merge($availableFeatures, self::FEATURES_BY_LEVEL[self::LEVEL_PREMIUM]);
        }

        if ($subscriptionLevel === self::LEVEL_BUSINESS) {
            $availableFeatures = array_merge($availableFeatures, self::FEATURES_BY_LEVEL[self::LEVEL_BUSINESS]);
        }

        return $availableFeatures;
    }

    public static function getFeatureDescription(string $feature): string
    {
        return self::FEATURES_DESCRIPTIONS[strtolower($feature)] ?? 'Description non disponible';
    }

    public static function isValidSubscriptionLevel(string $level): bool
    {
        return \in_array(strtolower($level), self::SUBSCRIPTION_LEVELS, true);
    }

    public static function isFeatureAvailableForLevel(string $feature, string $level): bool
    {
        $availableFeatures = self::getAvailableFeatures($level);

        return \in_array(strtolower($feature), array_map('strtolower', $availableFeatures), true);
    }
}
