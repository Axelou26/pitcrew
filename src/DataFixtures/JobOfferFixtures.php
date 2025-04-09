<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\JobOffer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use DateTimeImmutable;

class JobOfferFixtures extends Fixture implements DependentFixtureInterface
{
    public const JOB_OFFER_REFERENCE_PREFIX = 'job_offer_';

    public function getDependencies(): array
    {
        return [
            RecruiterFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getJobOfferData() as $index => $data) {
            $jobOffer = new JobOffer();
            $jobOffer->setTitle($data['title'])
                ->setDescription($data['description'])
                ->setRequiredSkills($data['requiredSkills'])
                ->setLocation($data['location'])
                ->setIsRemote($data['isRemote'])
                ->setSalary($data['salary'])
                ->setContractType($data['contractType'])
                ->setExperienceLevel($data['experienceLevel'])
                ->setCompany($data['company'])
                ->setIsPublished(true)
                ->setRecruiter($this->getReference(RecruiterFixtures::RECRUITER_REFERENCE_PREFIX . ($index % 5)))
                ->setIsActive(true);

            $manager->persist($jobOffer);
            $this->addReference(self::JOB_OFFER_REFERENCE_PREFIX . $index, $jobOffer);
        }

        $manager->flush();
    }

    private function getJobOfferData(): array
    {
        return [
            [
                'title' => 'Mécanicien F1',
                'description' => 'Nous recherchons un mécanicien expérimenté pour rejoindre notre équipe de Formule 1. Vous serez responsable de la maintenance et de la préparation des voitures de course.',
                'requiredSkills' => ['Mécanique de compétition', 'Hydraulique', 'Électronique embarquée', 'Composite'],
                'location' => 'Viry-Châtillon',
                'isRemote' => false,
                'salary' => 40000,
                'contractType' => 'CDI',
                'experienceLevel' => 'Confirmé',
                'company' => 'Alpine F1 Team'
            ],
            [
                'title' => 'Ingénieur Performance',
                'description' => 'Rejoignez notre équipe en tant qu\'ingénieur performance pour analyser et optimiser les performances de nos voitures de course.',
                'requiredSkills' => ['Analyse de données', 'Simulation', 'MATLAB', 'CFD'],
                'location' => 'Le Mans',
                'isRemote' => false,
                'salary' => 55000,
                'contractType' => 'CDI',
                'experienceLevel' => 'Senior',
                'company' => 'Toyota Gazoo Racing'
            ],
            [
                'title' => 'Chef d\'équipe Stand',
                'description' => 'Nous cherchons un chef d\'équipe expérimenté pour gérer notre équipe de stand pendant les courses.',
                'requiredSkills' => ['Management d\'équipe', 'Gestion de stress', 'Stratégie de course', 'Réglementation FIA'],
                'location' => 'Magny-Cours',
                'isRemote' => false,
                'salary' => 48000,
                'contractType' => 'CDI',
                'experienceLevel' => 'Expert',
                'company' => 'ORECA'
            ],
            [
                'title' => 'Technicien Composite',
                'description' => 'Expert en matériaux composites pour la fabrication et la réparation de pièces de carrosserie.',
                'requiredSkills' => ['Fibre de carbone', 'Moulage', 'Réparation composite', 'Contrôle qualité'],
                'location' => 'Stuttgart',
                'isRemote' => false,
                'salary' => 35000,
                'contractType' => 'CDI',
                'experienceLevel' => 'Intermédiaire',
                'company' => 'Porsche Motorsport'
            ],
            [
                'title' => 'Ingénieur Télémétrie',
                'description' => 'Analyse en temps réel des données de course et optimisation des performances.',
                'requiredSkills' => ['Télémétrie', 'Python', 'Analyse de données', 'Communication radio'],
                'location' => 'Prague',
                'isRemote' => true,
                'salary' => 58000,
                'contractType' => 'CDI',
                'experienceLevel' => 'Senior',
                'company' => 'Praga Racing'
            ]
        ];
    }
}
