<?php

namespace App\DataFixtures;

use App\Entity\Subscription;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SubscriptionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Créer l'abonnement Basic
        $basic = new Subscription();
        $basic->setName('Basic');
        $basic->setPrice(0);
        $basic->setDuration(30); // 30 jours
        $basic->setFeatures([
            'Publication limitée d\'offres (3 max)',
            'Accès basique aux candidatures',
            'Messagerie limitée',
            'Profil entreprise standard',
            'Pas d\'accès complet aux CV',
            'Pas de statistiques'
        ]);
        $basic->setMaxJobOffers(3);
        $basic->setIsActive(true);
        $manager->persist($basic);
        $this->addReference('subscription-basic', $basic);

        // Créer l'abonnement Premium
        $premium = new Subscription();
        $premium->setName('Premium');
        $premium->setPrice(49);
        $premium->setDuration(30); // 30 jours
        $premium->setFeatures([
            'Publication illimitée d\'offres d\'emploi',
            'Mise en avant des offres pendant 3 jours',
            'Accès aux CV complets des candidats',
            'Messagerie illimitée',
            'Statistiques de base sur les offres',
            'Profil entreprise amélioré'
        ]);
        $premium->setMaxJobOffers(null); // illimité
        $premium->setIsActive(true);
        $manager->persist($premium);
        $this->addReference('subscription-premium', $premium);

        // Créer l'abonnement Business
        $business = new Subscription();
        $business->setName('Business');
        $business->setPrice(99);
        $business->setDuration(30); // 30 jours
        $business->setFeatures([
            'Tout ce qui est inclus dans Premium',
            'Recherche avancée de candidats',
            'Recommandations automatiques',
            'Statistiques détaillées',
            'Badge "Entreprise vérifiée"',
            'Support prioritaire'
        ]);
        $business->setMaxJobOffers(null); // illimité
        $business->setIsActive(true);
        $manager->persist($business);
        $this->addReference('subscription-business', $business);

        $manager->flush();
    }
}
