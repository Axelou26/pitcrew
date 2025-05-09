<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            TestUserFixtures::class,
            SubscriptionFixtures::class,
            RecruiterFixtures::class,
            ApplicantFixtures::class,
            JobOfferFixtures::class,
            PostFixtures::class,
            ApplicationFixtures::class,
            FriendshipFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Cette classe est maintenant utilisée uniquement pour gérer les dépendances
        // entre les différentes fixtures
    }
}
