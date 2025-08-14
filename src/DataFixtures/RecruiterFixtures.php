<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Recruiter;
use App\Entity\RecruiterSubscription;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RecruiterFixtures extends Fixture implements DependentFixtureInterface
{
    public const RECRUITER_REFERENCE_PREFIX = 'recruiter_';
    private const PASSWORD                  = 'password';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function getDependencies(): array
    {
        return [
            SubscriptionFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getRecruiterData() as $index => $data) {
            $recruiter = new Recruiter();
            $recruiter->setEmail($data['email'])
                ->setFirstName($data['firstName'])
                ->setLastName($data['lastName'])
                ->setCompanyName($data['companyName'])
                ->setPassword($this->passwordHasher->hashPassword($recruiter, self::PASSWORD))
                ->setCompanyDescription($data['companyDescription'])
                ->setCity($data['city'])
                ->setSkills([])
                ->setDocuments([]);

            // Créer l'abonnement du recruteur
            $recruiterSubscription = new RecruiterSubscription();
            $recruiterSubscription->setRecruiter($recruiter)
                ->setSubscription($this->getReference($data['subscriptionReference']))
                ->setStartDate(new DateTimeImmutable())
                ->setEndDate(new DateTimeImmutable('+30 days'))
                ->setIsActive(true)
                ->setPaymentStatus('completed')
                ->setRemainingJobOffers(null)
                ->setCancelled(false)
                ->setAutoRenew(true);

            $manager->persist($recruiter);
            $manager->persist($recruiterSubscription);
            $this->addReference(self::RECRUITER_REFERENCE_PREFIX . $index, $recruiter);
        }

        $manager->flush();
    }

    private function getRecruiterData(): array
    {
        return [
            [
                'email'              => 'recruteur1@exemple.fr',
                'firstName'          => 'Jean',
                'lastName'           => 'Dupont',
                'companyName'        => 'Alpine F1 Team',
                'companyDescription' => 'Écurie de Formule 1 basée à Enstone (UK) et Viry-Châtillon, ' .
                    'représentant les couleurs françaises en F1',
                'city'                  => 'Viry-Châtillon',
                'subscriptionReference' => 'subscription-business',
            ],
            [
                'email'                 => 'recruteur2@exemple.fr',
                'firstName'             => 'Marie',
                'lastName'              => 'Laurent',
                'companyName'           => 'Toyota Gazoo Racing',
                'companyDescription'    => 'Division compétition de Toyota, multiple vainqueur des 24h du Mans',
                'city'                  => 'Le Mans',
                'subscriptionReference' => 'subscription-premium',
            ],
            [
                'email'                 => 'recruteur3@exemple.fr',
                'firstName'             => 'Pierre',
                'lastName'              => 'Martin',
                'companyName'           => 'ORECA',
                'companyDescription'    => 'Constructeur et préparateur français de voitures de course',
                'city'                  => 'Magny-Cours',
                'subscriptionReference' => 'subscription-basic',
            ],
            [
                'email'                 => 'recruteur4@exemple.fr',
                'firstName'             => 'Thomas',
                'lastName'              => 'Schmidt',
                'companyName'           => 'Porsche Motorsport',
                'companyDescription'    => 'Division sport automobile de Porsche, active en Endurance et en Formule E',
                'city'                  => 'Stuttgart',
                'subscriptionReference' => 'subscription-premium',
            ],
            [
                'email'                 => 'recruteur5@exemple.fr',
                'firstName'             => 'Eva',
                'lastName'              => 'Novotná',
                'companyName'           => 'Praga Racing',
                'companyDescription'    => 'Constructeur de voitures de course et de karts de compétition',
                'city'                  => 'Prague',
                'subscriptionReference' => 'subscription-business',
            ],
            [
                'email'              => 'recruteur6@exemple.fr',
                'firstName'          => 'Sophie',
                'lastName'           => 'Lefevre',
                'companyName'        => 'Ferrari GT Racing',
                'companyDescription' => 'Division GT de Ferrari, spécialisée dans les
                                                championnats GT et l\'endurance',
                'city'                  => 'Maranello',
                'subscriptionReference' => 'subscription-premium',
            ],
            [
                'email'                 => 'recruteur7@exemple.fr',
                'firstName'             => 'Marc',
                'lastName'              => 'Dubois',
                'companyName'           => 'Michelin Motorsport',
                'companyDescription'    => 'Leader mondial des pneumatiques de compétition',
                'city'                  => 'Clermont-Ferrand',
                'subscriptionReference' => 'subscription-business',
            ],
            [
                'email'                 => 'recruteur8@exemple.fr',
                'firstName'             => 'Laura',
                'lastName'              => 'Garcia',
                'companyName'           => 'McLaren Automotive',
                'companyDescription'    => 'Constructeur de voitures de luxe et de course, pionnier en F1',
                'city'                  => 'Woking',
                'subscriptionReference' => 'subscription-premium',
            ],
            [
                'email'                 => 'recruteur9@exemple.fr',
                'firstName'             => 'Alexandre',
                'lastName'              => 'Moreau',
                'companyName'           => 'Peugeot Sport',
                'companyDescription'    => 'Division sportive de Peugeot, engagée en WEC et rallye-raid',
                'city'                  => 'Sochaux',
                'subscriptionReference' => 'subscription-business',
            ],
            [
                'email'                 => 'recruteur10@exemple.fr',
                'firstName'             => 'Claire',
                'lastName'              => 'Bernard',
                'companyName'           => 'Aston Martin Racing',
                'companyDescription'    => 'Équipe officielle Aston Martin en compétition GT et F1',
                'city'                  => 'Gaydon',
                'subscriptionReference' => 'subscription-premium',
            ],
        ];
    }
}
