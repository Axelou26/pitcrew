<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Recruiter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RecruiterFixtures extends Fixture implements DependentFixtureInterface
{
    private const PASSWORD = 'password';
    public const RECRUITER_REFERENCE_PREFIX = 'recruiter_';

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
                ->setPassword($this->passwordHasher->hashPassword($recruiter, self::PASSWORD))
                ->setCompanyName($data['companyName'])
                ->setCompanyDescription($data['companyDescription'])
                ->setCity($data['city'])
                ->setBio($data['bio'])
                ->setJobTitle($data['jobTitle']);

            $manager->persist($recruiter);
            $this->addReference(self::RECRUITER_REFERENCE_PREFIX . $index, $recruiter);
        }

        $manager->flush();
    }

    private function getRecruiterData(): array
    {
        return [
            [
                'email' => 'recruteur1@example.com',
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'companyName' => 'Tech Solutions',
                'companyDescription' => 'Une entreprise innovante dans le domaine de la tech',
                'city' => 'Paris',
                'bio' => 'Passionné par le recrutement tech',
                'jobTitle' => 'Talent Acquisition Manager'
            ],
            [
                'email' => 'recruteur2@example.com',
                'firstName' => 'Marie',
                'lastName' => 'Martin',
                'companyName' => 'Digital Agency',
                'companyDescription' => 'Agence digitale spécialisée dans le web',
                'city' => 'Lyon',
                'bio' => 'Expert en recrutement digital',
                'jobTitle' => 'HR Manager'
            ],
            // Ajoutez d'autres recruteurs selon vos besoins
        ];
    }
} 