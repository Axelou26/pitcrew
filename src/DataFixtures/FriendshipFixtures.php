<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Friendship;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use DateTimeImmutable;

class FriendshipFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            ApplicantFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Créer des relations d'amitié entre tous les candidats
        for ($i = 0; $i < 2; $i++) {
            for ($j = $i + 1; $j < 2; $j++) {
                $friendship = new Friendship();
                $friendship->setRequester($this->getReference(ApplicantFixtures::APPLICANT_REFERENCE_PREFIX . $i))
                    ->setAddressee($this->getReference(ApplicantFixtures::APPLICANT_REFERENCE_PREFIX . $j))
                    ->setStatus('accepted')
                    ->setCreatedAt(new DateTimeImmutable());

                $manager->persist($friendship);
            }
        }

        $manager->flush();
    }
}
