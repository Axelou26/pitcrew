<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\JobOffer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

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
                'title'       => 'Mécanicien F1',
                'description' => 'Nous recherchons un mécanicien expérimenté pour rejoindre notre équipe de F1. ' .
                    'Vous serez responsable de la maintenance et de la préparation des voitures.',
                'requiredSkills' => [
                    'Mécanique de compétition',
                    'Hydraulique',
                    'Électronique embarquée',
                    'Composite',
                ],
                'softSkills' => [
                    'Travail d\'équipe',
                    'Gestion du stress',
                    'Rigueur',
                    'Communication',
                ],
                'location'           => 'Viry-Châtillon',
                'isRemote'           => false,
                'salary'             => 40000,
                'contractType'       => 'CDI',
                'experienceLevel'    => 'Confirmé',
                'company'            => 'Alpine F1 Team',
                'requiredExperience' => 5,
            ],
            [
                'title'       => 'Ingénieur Performance',
                'description' => 'Rejoignez notre équipe en tant qu\'ingénieur performance pour optimiser ' .
                    'les performances de nos voitures de course.',
                'requiredSkills' => [
                    'Analyse de données',
                    'Simulation',
                    'MATLAB',
                    'CFD',
                ],
                'softSkills' => [
                    'Innovation',
                    'Esprit analytique',
                    'Autonomie',
                    'Communication technique',
                ],
                'location'           => 'Le Mans',
                'isRemote'           => false,
                'salary'             => 55000,
                'contractType'       => 'CDI',
                'experienceLevel'    => 'Senior',
                'company'            => 'Toyota Gazoo Racing',
                'requiredExperience' => 8,
            ],
            [
                'title'       => 'Chef d\'équipe Stand',
                'description' => 'Nous cherchons un chef d\'équipe expérimenté pour gérer notre équipe de stand ' .
                    'pendant les courses.',
                'requiredSkills' => [
                    'Management d\'équipe',
                    'Gestion de stress',
                    'Stratégie de course',
                    'Réglementation FIA',
                ],
                'softSkills' => [
                    'Leadership',
                    'Prise de décision',
                    'Communication',
                    'Gestion de crise',
                ],
                'location'           => 'Magny-Cours',
                'isRemote'           => false,
                'salary'             => 48000,
                'contractType'       => 'CDI',
                'experienceLevel'    => 'Expert',
                'company'            => 'ORECA',
                'requiredExperience' => 10,
            ],
            [
                'title'          => 'Technicien Composite',
                'description'    => 'Expert en fabrication et réparation de pièces en matériaux composites.',
                'requiredSkills' => [
                    'Fibre de carbone',
                    'Moulage',
                    'Réparation composite',
                    'Contrôle qualité',
                ],
                'softSkills' => [
                    'Minutie',
                    'Attention aux détails',
                    'Organisation',
                    'Travail d\'équipe',
                ],
                'location'           => 'Stuttgart',
                'isRemote'           => false,
                'salary'             => 35000,
                'contractType'       => 'CDI',
                'experienceLevel'    => 'Intermédiaire',
                'company'            => 'Porsche Motorsport',
                'requiredExperience' => 3,
            ],
            [
                'title'          => 'Ingénieur Télémétrie',
                'description'    => 'Analyse en temps réel des données de course et optimisation des performances.',
                'requiredSkills' => [
                    'Télémétrie',
                    'Python',
                    'Analyse de données',
                    'Communication radio',
                ],
                'softSkills' => [
                    'Réactivité',
                    'Concentration',
                    'Communication',
                    'Travail sous pression',
                ],
                'location'           => 'Prague',
                'isRemote'           => true,
                'salary'             => 58000,
                'contractType'       => 'CDI',
                'experienceLevel'    => 'Senior',
                'company'            => 'Praga Racing',
                'requiredExperience' => 7,
            ],
            [
                'title'          => 'Ingénieur Développement Moteur',
                'description'    => 'Conception et développement de nouveaux moteurs de course.',
                'requiredSkills' => [
                    'Conception moteur',
                    'Thermodynamique',
                    'CAO',
                    'Simulation moteur',
                ],
                'softSkills' => [
                    'Innovation',
                    'Rigueur',
                    'Travail d\'équipe',
                    'Résolution de problèmes',
                ],
                'location'           => 'Maranello',
                'isRemote'           => false,
                'salary'             => 65000,
                'contractType'       => 'CDI',
                'experienceLevel'    => 'Senior',
                'company'            => 'Ferrari GT Racing',
                'requiredExperience' => 8,
            ],
            [
                'title'       => 'Responsable Marketing Sportif',
                'description' => 'Développement et mise en œuvre de la stratégie
                                         marketing dans le sport automobile.',
                'requiredSkills' => [
                    'Marketing digital',
                    'Communication',
                    'Gestion de projet',
                    'Sponsoring',
                ],
                'softSkills' => [
                    'Créativité',
                    'Leadership',
                    'Négociation',
                    'Vision stratégique',
                ],
                'location'           => 'Paris',
                'isRemote'           => true,
                'salary'             => 52000,
                'contractType'       => 'CDI',
                'experienceLevel'    => 'Confirmé',
                'company'            => 'Alpine Sport Automobile Team',
                'requiredExperience' => 6,
            ],
            [
                'title'          => 'Ingénieur R&D Pneumatiques',
                'description'    => 'Développement et test de nouveaux composés pour les pneumatiques de course.',
                'requiredSkills' => [
                    'Chimie des matériaux',
                    'Test sur piste',
                    'Analyse de données',
                    'Simulation',
                ],
                'softSkills' => [
                    'Innovation',
                    'Méthodologie',
                    'Communication technique',
                    'Esprit d\'équipe',
                ],
                'location'           => 'Clermont-Ferrand',
                'isRemote'           => false,
                'salary'             => 48000,
                'contractType'       => 'CDI',
                'experienceLevel'    => 'Confirmé',
                'company'            => 'Michelin Motorsport',
                'requiredExperience' => 5,
            ],
            [
                'title'          => 'Ingénieur Systèmes Électroniques',
                'description'    => 'Conception et développement des systèmes électroniques embarqués.',
                'requiredSkills' => [
                    'Électronique embarquée',
                    'FPGA',
                    'C++',
                    'Systèmes temps réel',
                ],
                'softSkills' => [
                    'Précision',
                    'Innovation',
                    'Résolution de problèmes',
                    'Travail en équipe',
                ],
                'location'           => 'Woking',
                'isRemote'           => false,
                'salary'             => 62000,
                'contractType'       => 'CDI',
                'experienceLevel'    => 'Senior',
                'company'            => 'McLaren Automotive',
                'requiredExperience' => 7,
            ],
            [
                'title'          => 'Aérodynamicien',
                'description'    => 'Optimisation aérodynamique des véhicules de course.',
                'requiredSkills' => [
                    'CFD',
                    'Soufflerie',
                    'CAO',
                    'Post-traitement',
                ],
                'softSkills' => [
                    'Analyse',
                    'Innovation',
                    'Communication',
                    'Rigueur',
                ],
                'location'           => 'Gaydon',
                'isRemote'           => false,
                'salary'             => 58000,
                'contractType'       => 'CDI',
                'experienceLevel'    => 'Senior',
                'company'            => 'Aston Martin Racing',
                'requiredExperience' => 6,
            ],
        ];
    }
}
