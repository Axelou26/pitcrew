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
                ->setSoftSkills($data['softSkills'])
                ->setLocation($data['location'])
                ->setIsRemote($data['isRemote'])
                ->setSalary($data['salary'])
                ->setContractType($data['contractType'])
                ->setExperienceLevel($data['experienceLevel'])
                ->setCompany($data['company'])
                ->setIsPublished(true)
                ->setRecruiter($this->getReference(RecruiterFixtures::RECRUITER_REFERENCE_PREFIX . ($index % 5)))
                ->setIsActive(true)
                ->setRequiredExperience($data['requiredExperience']);

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
                'description' => 'Nous recherchons un mécanicien expérimenté pour rejoindre notre équipe de F1. ' .
                    'Vous serez responsable de la maintenance et de la préparation des voitures.',
                'requiredSkills' => [
                    'Mécanique de compétition',
                    'Hydraulique',
                    'Électronique embarquée',
                    'Composite'
                ],
                'softSkills' => [
                    'Travail d\'équipe',
                    'Gestion du stress',
                    'Rigueur',
                    'Communication'
                ],
                'location' => 'Viry-Châtillon',
                'isRemote' => false,
                'salary' => 40000,
                'contractType' => 'CDI',
                'experienceLevel' => 'Confirmé',
                'company' => 'Alpine F1 Team',
                'requiredExperience' => 5
            ],
            [
                'title' => 'Ingénieur Performance',
                'description' => 'Rejoignez notre équipe en tant qu\'ingénieur performance pour optimiser ' .
                    'les performances de nos voitures de course.',
                'requiredSkills' => [
                    'Analyse de données',
                    'Simulation',
                    'MATLAB',
                    'CFD'
                ],
                'softSkills' => [
                    'Innovation',
                    'Esprit analytique',
                    'Autonomie',
                    'Communication technique'
                ],
                'location' => 'Le Mans',
                'isRemote' => false,
                'salary' => 55000,
                'contractType' => 'CDI',
                'experienceLevel' => 'Senior',
                'company' => 'Toyota Gazoo Racing',
                'requiredExperience' => 8
            ],
            [
                'title' => 'Chef d\'équipe Stand',
                'description' => 'Nous cherchons un chef d\'équipe expérimenté pour gérer notre équipe de stand ' .
                    'pendant les courses.',
                'requiredSkills' => [
                    'Management d\'équipe',
                    'Gestion de stress',
                    'Stratégie de course',
                    'Réglementation FIA'
                ],
                'softSkills' => [
                    'Leadership',
                    'Prise de décision',
                    'Communication',
                    'Gestion de crise'
                ],
                'location' => 'Magny-Cours',
                'isRemote' => false,
                'salary' => 48000,
                'contractType' => 'CDI',
                'experienceLevel' => 'Expert',
                'company' => 'ORECA',
                'requiredExperience' => 10
            ],
            [
                'title' => 'Technicien Composite',
                'description' => 'Expert en fabrication et réparation de pièces en matériaux composites.',
                'requiredSkills' => [
                    'Fibre de carbone',
                    'Moulage',
                    'Réparation composite',
                    'Contrôle qualité'
                ],
                'softSkills' => [
                    'Minutie',
                    'Attention aux détails',
                    'Organisation',
                    'Travail d\'équipe'
                ],
                'location' => 'Stuttgart',
                'isRemote' => false,
                'salary' => 35000,
                'contractType' => 'CDI',
                'experienceLevel' => 'Intermédiaire',
                'company' => 'Porsche Motorsport',
                'requiredExperience' => 3
            ],
            [
                'title' => 'Ingénieur Télémétrie',
                'description' => 'Analyse en temps réel des données de course et optimisation des performances.',
                'requiredSkills' => [
                    'Télémétrie',
                    'Python',
                    'Analyse de données',
                    'Communication radio'
                ],
                'softSkills' => [
                    'Réactivité',
                    'Concentration',
                    'Communication',
                    'Travail sous pression'
                ],
                'location' => 'Prague',
                'isRemote' => true,
                'salary' => 58000,
                'contractType' => 'CDI',
                'experienceLevel' => 'Senior',
                'company' => 'Praga Racing',
                'requiredExperience' => 7
            ]
        ];
    }
}
