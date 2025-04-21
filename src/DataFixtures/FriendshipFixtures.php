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
        // Créer des relations d'amitié acceptées
        $acceptedFriendships = [
            [0, 1], [0, 2], [1, 2], [1, 3], [2, 3], [2, 4], [3, 4],
            [4, 5], [5, 6], [5, 7], [6, 7], [6, 8], [7, 8], [7, 9],
            [0, 4], [1, 5], [2, 6], [3, 7], [4, 8], [5, 9],
            [0, 6], [1, 7], [2, 8], [3, 9]
        ];

        foreach ($acceptedFriendships as $pair) {
            $friendship = new Friendship();
            $friendship->setRequester($this->getReference(ApplicantFixtures::APPLICANT_REFERENCE_PREFIX . $pair[0]))
                ->setAddressee($this->getReference(ApplicantFixtures::APPLICANT_REFERENCE_PREFIX . $pair[1]))
                ->setStatus('accepted')
                ->setCreatedAt(new DateTimeImmutable('-' . rand(1, 30) . ' days'));

            $manager->persist($friendship);
        }

        // Créer des demandes d'amitié en attente
        $pendingFriendships = [
            [0, 8], [1, 9], [2, 7], [3, 6], [4, 7],
            [5, 8], [6, 9], [7, 0], [8, 1], [9, 2]
        ];

        foreach ($pendingFriendships as $pair) {
            $friendship = new Friendship();
            $friendship->setRequester($this->getReference(ApplicantFixtures::APPLICANT_REFERENCE_PREFIX . $pair[0]))
                ->setAddressee($this->getReference(ApplicantFixtures::APPLICANT_REFERENCE_PREFIX . $pair[1]))
                ->setStatus('pending')
                ->setCreatedAt(new DateTimeImmutable('-' . rand(1, 5) . ' days'));

            $manager->persist($friendship);
        }

        // Créer quelques demandes d'amitié refusées
        $rejectedFriendships = [
            [0, 9], [1, 8], [2, 5], [3, 8], [4, 9]
        ];

        foreach ($rejectedFriendships as $pair) {
            $friendship = new Friendship();
            $friendship->setRequester($this->getReference(ApplicantFixtures::APPLICANT_REFERENCE_PREFIX . $pair[0]))
                ->setAddressee($this->getReference(ApplicantFixtures::APPLICANT_REFERENCE_PREFIX . $pair[1]))
                ->setStatus('rejected')
                ->setCreatedAt(new DateTimeImmutable('-' . rand(10, 60) . ' days'));

            $manager->persist($friendship);
        }

        $manager->flush();
    }
}
