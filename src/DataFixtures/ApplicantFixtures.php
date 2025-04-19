<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Applicant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApplicantFixtures extends Fixture
{
    private const PASSWORD = 'password';
    public const APPLICANT_REFERENCE_PREFIX = 'applicant_';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getApplicantData() as $index => $data) {
            $applicant = new Applicant();
            $applicant->setEmail($data['email'])
                ->setFirstName($data['firstName'])
                ->setLastName($data['lastName'])
                ->setPassword($this->passwordHasher->hashPassword($applicant, self::PASSWORD))
                ->setJobTitle($data['jobTitle'])
                ->setDescription($data['description'])
                ->setTechnicalSkills($data['technicalSkills'])
                ->setSoftSkills($data['softSkills'])
                ->setCity($data['city'])
                ->setBio($data['bio'])
                ->setEducationHistory($this->parseEducation($data['education']))
                ->setWorkExperience($this->parseWorkExperience($data['experience']))
                ->setSkills($data['technicalSkills'])
                ->setDocuments([]);

            $manager->persist($applicant);
            $this->addReference(self::APPLICANT_REFERENCE_PREFIX . $index, $applicant);
        }

        $manager->flush();
    }

    private function parseWorkExperience(string $experienceText): array
    {
        $experiences = [];
        $items = explode("\n", $experienceText);
        foreach ($items as $exp) {
            if (preg_match('/(\d{4})-(\d{4})\s*:\s*(.+?)\s*@\s*(.+)/', $exp, $matches)) {
                $experiences[] = [
                    'startDate' => $matches[1],
                    'endDate' => $matches[2],
                    'title' => $matches[3],
                    'company' => $matches[4]
                ];
            }
        }
        return $experiences;
    }

    private function parseEducation(string $educationText): array
    {
        $education = [];
        $items = explode("\n", $educationText);
        foreach ($items as $edu) {
            if (preg_match('/(\d{4})-(\d{4})\s*:\s*(.+?)\s*@\s*(.+?)\s*,\s*(.+)/', $edu, $matches)) {
                $education[] = [
                    'startDate' => $matches[1],
                    'endDate' => $matches[2],
                    'degree' => $matches[3],
                    'institution' => $matches[4],
                    'location' => $matches[5]
                ];
            }
        }
        return $education;
    }

    private function getApplicantData(): array
    {
        return [
            [
                'email' => 'candidat1@exemple.fr',
                'firstName' => 'Marc',
                'lastName' => 'Dubois',
                'jobTitle' => 'Mécanicien de Compétition',
                'description' => 'Mécanicien passionné avec 5 ans d\'expérience en sport automobile',
                'technicalSkills' => [
                    'Mécanique',
                    'Hydraulique',
                    'Électronique',
                    'Composite'
                ],
                'softSkills' => [
                    'Travail d\'équipe',
                    'Résistance au stress',
                    'Rigueur'
                ],
                'city' => 'Le Mans',
                'bio' => 'Passionné de sport automobile depuis mon plus jeune âge',
                'experience' => "2018-2023 : Mécanicien F3 @ Prema Racing\n2016-2018 : Mécanicien F4 @ FFSA Academy",
                'education' => implode("\n", [
                    "2014-2016 : BTS Maintenance des Véhicules @ Lycée Le Mans Sud, Le Mans",
                    "2011-2014 : Bac Pro Maintenance des Véhicules @ Lycée Le Mans Sud, Le Mans"
                ])
            ],
            [
                'email' => 'candidat2@exemple.fr',
                'firstName' => 'Julie',
                'lastName' => 'Martin',
                'jobTitle' => 'Ingénieure Aérodynamique',
                'description' => 'Ingénieure spécialisée en aérodynamique avec expérience en soufflerie',
                'technicalSkills' => [
                    'CFD',
                    'MATLAB',
                    'Python',
                    'CAO'
                ],
                'softSkills' => [
                    'Analyse',
                    'Innovation',
                    'Communication'
                ],
                'city' => 'Viry-Châtillon',
                'bio' => 'Passionnée par l\'innovation en sport automobile',
                'experience' => implode("\n", [
                    "2020-2023 : Ingénieure Aéro @ Dallara",
                    "2019-2020 : Stagiaire Aéro @ Alpine F1"
                ]),
                'education' => implode("\n", [
                    "2017-2019 : Master en Aérodynamique @ ISAE-SUPAERO, Toulouse",
                    "2014-2017 : Diplôme d'Ingénieur @ École Centrale Paris, Paris"
                ])
            ],
            [
                'email' => 'candidat3@exemple.fr',
                'firstName' => 'Sophie',
                'lastName' => 'Leroy',
                'jobTitle' => 'Ingénieure Data',
                'description' => 'Spécialiste en analyse de données de course et télémétrie',
                'technicalSkills' => [
                    'Python',
                    'R',
                    'SQL',
                    'Machine Learning',
                    'Télémétrie'
                ],
                'softSkills' => [
                    'Analyse',
                    'Précision',
                    'Adaptabilité'
                ],
                'city' => 'Monaco',
                'bio' => 'Passionnée par l\'analyse de données dans le sport automobile',
                'experience' => implode("\n", [
                    "2019-2023 : Data Engineer @ Ferrari F1",
                    "2017-2019 : Junior Data Analyst @ Mercedes F1"
                ]),
                'education' => implode("\n", [
                    "2015-2017 : Master en Science des Données @ École Polytechnique, Paris",
                    "2012-2015 : Licence en Mathématiques @ Sorbonne Université, Paris"
                ])
            ],
            [
                'email' => 'candidat4@exemple.fr',
                'firstName' => 'Lucas',
                'lastName' => 'Bernard',
                'jobTitle' => 'Technicien Composite',
                'description' => 'Expert en fabrication et réparation de pièces en matériaux composites',
                'technicalSkills' => [
                    'Composite',
                    'Moulage',
                    'CAO',
                    'Contrôle Qualité'
                ],
                'softSkills' => [
                    'Minutie',
                    'Organisation',
                    'Autonomie'
                ],
                'city' => 'Magny-Cours',
                'bio' => 'Spécialiste des matériaux composites dans le sport automobile',
                'experience' => implode("\n", [
                    "2020-2023 : Technicien Composite @ Alpine F1",
                    "2018-2020 : Technicien Composite @ ART Grand Prix"
                ]),
                'education' => implode("\n", [
                    "2016-2018 : BTS Composites et Plastiques @ ISPA, Alençon",
                    "2013-2016 : Bac Pro Plastiques et Composites @ Lycée Technique, Alençon"
                ])
            ],
            [
                'email' => 'candidat5@exemple.fr',
                'firstName' => 'Emma',
                'lastName' => 'Petit',
                'jobTitle' => 'Ingénieure Performance',
                'description' => 'Spécialiste en optimisation des performances et stratégie de course',
                'technicalSkills' => [
                    'MATLAB',
                    'Simulation',
                    'Analyse de données',
                    'Stratégie'
                ],
                'softSkills' => [
                    'Leadership',
                    'Gestion du stress',
                    'Communication'
                ],
                'city' => 'Silverstone',
                'bio' => 'Passionnée par la stratégie et la performance en course',
                'experience' => implode("\n", [
                    "2021-2023 : Performance Engineer @ Aston Martin F1",
                    "2019-2021 : Junior Engineer @ Williams Racing"
                ]),
                'education' => implode("\n", [
                    "2017-2019 : Master en Ingénierie Automobile @ Cranfield University, UK",
                    "2014-2017 : Diplôme d'Ingénieur @ ESTACA, Paris"
                ])
            ],
            [
                'email' => 'candidat6@exemple.fr',
                'firstName' => 'Thomas',
                'lastName' => 'Moreau',
                'jobTitle' => 'Ingénieur Moteur',
                'description' => 'Expert en développement et optimisation de moteurs de course',
                'technicalSkills' => [
                    'Thermodynamique',
                    'CAO',
                    'Simulation moteur',
                    'Banc d\'essai'
                ],
                'softSkills' => [
                    'Résolution de problèmes',
                    'Travail en équipe',
                    'Innovation'
                ],
                'city' => 'Maranello',
                'bio' => 'Passionné par la technologie des moteurs de course',
                'experience' => implode("\n", [
                    "2020-2023 : Ingénieur Moteur @ Ferrari F1",
                    "2018-2020 : Ingénieur Développement @ Renault Sport"
                ]),
                'education' => implode("\n", [
                    "2016-2018 : Master en Ingénierie Mécanique @ Politecnico di Milano, Italie",
                    "2013-2016 : Diplôme d'Ingénieur @ INSA Lyon, Lyon"
                ])
            ],
            [
                'email' => 'candidat7@exemple.fr',
                'firstName' => 'Léa',
                'lastName' => 'Rousseau',
                'jobTitle' => 'Responsable Marketing Sportif',
                'description' => 'Experte en marketing et communication dans le sport automobile',
                'technicalSkills' => [
                    'Marketing Digital',
                    'Réseaux Sociaux',
                    'Gestion de Projet',
                    'Analyse de données'
                ],
                'softSkills' => ['Communication', 'Créativité', 'Gestion d\'équipe'],
                'city' => 'Paris',
                'bio' => 'Passionnée par la promotion du sport automobile',
                'experience' => implode("\n", [
                    "2021-2023 : Marketing Manager @ WEC",
                    "2019-2021 : Chargée de Communication @ Alpine"
                ]),
                'education' => implode("\n", [
                    "2017-2019 : Master Marketing du Sport @ ESSEC, Paris",
                    "2014-2017 : Bachelor en Communication @ CELSA, Paris"
                ])
            ],
            [
                'email' => 'candidat8@exemple.fr',
                'firstName' => 'Antoine',
                'lastName' => 'Lambert',
                'jobTitle' => 'Ingénieur Pneumatiques',
                'description' => 'Spécialiste en développement et analyse de pneumatiques de course',
                'technicalSkills' => [
                    'Analyse de données',
                    'Simulation',
                    'Tests sur piste',
                    'Matériaux'
                ],
                'softSkills' => [
                    'Analyse',
                    'Communication technique',
                    'Travail sous pression'
                ],
                'city' => 'Clermont-Ferrand',
                'bio' => 'Expert en optimisation des performances pneumatiques',
                'experience' => implode("\n", [
                    "2020-2023 : Ingénieur Pneus @ Michelin Motorsport",
                    "2018-2020 : Ingénieur R&D @ Pirelli"
                ]),
                'education' => implode("\n", [
                    "2016-2018 : Master en Mécanique @ SIGMA Clermont, Clermont-Ferrand",
                    "2013-2016 : Diplôme d'Ingénieur @ UTC, Compiègne"
                ])
            ],
            [
                'email' => 'candidat9@exemple.fr',
                'firstName' => 'Marine',
                'lastName' => 'Durand',
                'jobTitle' => 'Ingénieure Électronique',
                'description' => 'Experte en systèmes électroniques embarqués pour la compétition',
                'technicalSkills' => [
                    'Électronique embarquée',
                    'FPGA',
                    'C++',
                    'Systèmes temps réel'
                ],
                'softSkills' => [
                    'Rigueur',
                    'Innovation',
                    'Résolution de problèmes'
                ],
                'city' => 'Stuttgart',
                'bio' => 'Passionnée par l\'innovation en électronique automobile',
                'experience' => implode("\n", [
                    "2021-2023 : Ingénieure Électronique @ Porsche Motorsport",
                    "2019-2021 : Développeuse Systèmes @ Bosch Motorsport"
                ]),
                'education' => implode("\n", [
                    "2017-2019 : Master en Systèmes Embarqués @ ENSEEIHT, Toulouse",
                    "2014-2017 : Diplôme d'Ingénieur @ Télécom Paris, Paris"
                ])
            ],
            [
                'email' => 'candidat10@exemple.fr',
                'firstName' => 'Paul',
                'lastName' => 'Michel',
                'jobTitle' => 'Chef Mécanicien',
                'description' => 'Chef mécanicien expérimenté en endurance et F1',
                'technicalSkills' => [
                    'Gestion d\'équipe',
                    'Mécanique',
                    'Diagnostic',
                    'Procédures de course'
                ],
                'softSkills' => [
                    'Leadership',
                    'Gestion de stress',
                    'Communication'
                ],
                'city' => 'Le Mans',
                'bio' => 'Plus de 15 ans d\'expérience en sport automobile',
                'experience' => implode("\n", [
                    "2019-2023 : Chef Mécanicien @ Toyota Gazoo Racing",
                    "2015-2019 : Mécanicien Senior @ Porsche LMP1"
                ]),
                'education' => implode("\n", [
                    "2013-2015 : Formation Chef d'Équipe @ GNFA, Paris",
                    "2010-2013 : BTS Maintenance Véhicules @ Lycée Le Mans Sud, Le Mans"
                ])
            ]
        ];
    }
}
