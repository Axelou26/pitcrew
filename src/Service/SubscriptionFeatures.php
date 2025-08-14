<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

class SubscriptionFeatures
{
    public const LEVEL_BASIC    = 'basic';
    public const LEVEL_PREMIUM  = 'premium';
    public const LEVEL_BUSINESS = 'business';

    private const FEATURES = [
        self::LEVEL_BASIC => [
            'Publication limitée d\'offres (3 max)',
            'Accès basique aux candidatures',
            'Messagerie limitée',
            'Profil entreprise standard',
            'Pas d\'accès complet aux CV',
            'Pas de statistiques',
        ],
        self::LEVEL_PREMIUM => [
            'Publication illimitée d\'offres d\'emploi',
            'Mise en avant des offres pendant 3 jours',
            'Accès aux CV complets des candidats',
            'Messagerie illimitée',
            'Statistiques de base sur les offres',
            'Profil entreprise amélioré',
        ],
        self::LEVEL_BUSINESS => [
            'Tout ce qui est inclus dans Premium',
            'Recherche avancée de candidats',
            'Recommandations automatiques',
            'Statistiques détaillées',
            'Badge "Entreprise vérifiée"',
            'Support prioritaire',
        ],
    ];

    public function isValidSubscriptionLevel(string $level): bool
    {
        return in_array($level, [self::LEVEL_BASIC, self::LEVEL_PREMIUM, self::LEVEL_BUSINESS], true);
    }

    /**
     * Récupère les fonctionnalités disponibles pour un niveau d'abonnement.
     *
     * @return array<int, string>
     */
    public function getAvailableFeatures(string $subscriptionLevel): array
    {
        if (!$this->isValidSubscriptionLevel($subscriptionLevel)) {
            throw new InvalidArgumentException('Niveau d\'abonnement invalide');
        }

        return self::FEATURES[$subscriptionLevel];
    }

    public function isFeatureAvailableForLevel(string $feature, string $level): bool
    {
        if (!$this->isValidSubscriptionLevel($level)) {
            return false;
        }

        return in_array($feature, self::FEATURES[$level], true);
    }
}
