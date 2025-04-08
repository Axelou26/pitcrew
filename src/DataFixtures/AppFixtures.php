<?php

namespace App\DataFixtures;

use App\Entity\Applicant;
use App\Entity\Friendship;
use App\Entity\JobOffer;
use App\Entity\Post;
use App\Entity\Recruiter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function getDependencies()
    {
        return [
            SubscriptionFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Création des recruteurs
        $recruiters = [];

        $recruiterData = [
            [
                'email' => 'recruteur1@exemple.com',
                'firstName' => 'Thomas',
                'lastName' => 'Dubois',
                'companyName' => 'SpeedTech Racing',
                'companyDescription' => 'SpeedTech Racing est une écurie de Formule 1 en pleine expansion, reconnu...',
                'city' => 'Paris',
                'bio' => 'Directeur des ressources humaines chez SpeedTech Racing avec 15 ans d\'expérience dans l...',
                'jobTitle' => 'Directeur des Ressources Humaines'
            ],
            [
                'email' => 'recruteur2@exemple.com',
                'firstName' => 'Sophie',
                'lastName' => 'Martin',
                'companyName' => 'Apex Performance',
                'companyDescription' => 'Apex Performance est un fournisseur de premier plan pour les équipes de F...',
                'city' => 'Lyon',
                'bio' => 'Responsable recrutement chez Apex Performance. Spécialisée dans la recherche de talents...',
                'jobTitle' => 'Responsable Recrutement'
            ],
            [
                'email' => 'recruteur3@exemple.com',
                'firstName' => 'Nicolas',
                'lastName' => 'Leroy',
                'companyName' => 'Turbo Dynamics',
                'companyDescription' => 'Turbo Dynamics est leader dans la conception de moteurs et systèmes de pr...',
                'city' => 'Monaco',
                'bio' => 'Fondateur et PDG de Turbo Dynamics. Ancien ingénieur moteur en F1 avec plus de 20 ans d\...',
                'jobTitle' => 'PDG et Responsable Recrutement'
            ],
            [
                'email' => 'recruteur4@exemple.com',
                'firstName' => 'Camille',
                'lastName' => 'Petit',
                'companyName' => 'FastTrack Engineering',
                'companyDescription' => 'FastTrack Engineering fournit des services de conception et d\'ingénierie...',
                'city' => 'Londres',
                'bio' => 'Directrice des opérations chez FastTrack Engineering. Diplômée en ingénierie mécaniq...',
                'jobTitle' => 'Directrice des Opérations'
            ],
            [
                'email' => 'recruteur5@exemple.com',
                'firstName' => 'Alexandre',
                'lastName' => 'Bernard',
                'companyName' => 'Elite Motorsport',
                'companyDescription' => 'Elite Motorsport est une écurie indépendante avec une riche histoire dan...',
                'city' => 'Maranello',
                'bio' => 'Responsable technique et recrutement chez Elite Motorsport. Expérience internationale da...',
                'jobTitle' => 'Directeur Technique'
            ]
        ];

        foreach ($recruiterData as $index => $data) {
            $recruiter = new Recruiter();
            $recruiter->setEmail($data['email']);
            $recruiter->setFirstName($data['firstName']);
            $recruiter->setLastName($data['lastName']);
            $recruiter->setPassword($this->passwordHasher->hashPassword($recruiter, 'password'));
            $recruiter->setCompanyName($data['companyName']);
            $recruiter->setCompanyDescription($data['companyDescription']);
            $recruiter->setCity($data['city']);
            $recruiter->setBio($data['bio']);
            $recruiter->setJobTitle($data['jobTitle']);

            $manager->persist($recruiter);
            $recruiters[] = $recruiter;
        }

        // Création des candidats
        $applicants = [];

        $applicantData = [
            [
                'email' => 'candidat1@exemple.com',
                'firstName' => 'Julien',
                'lastName' => 'Robert',
                'jobTitle' => 'Mécanicien F1 Senior',
                'description' => 'Mécanicien passionné avec 8 ans d\'expérience dans les stands F1. Spécialiste...',
                'technicalSkills' 
                    = > ['Mécanique de précision', 'Systèmes hydrauliques', 'Aérodynamique', 'Maintenance préventive', 'Diagnostic rapide'],
                'softSkills' 
                    = > ['Travail d\'équipe', 'Résistance au stress', 'Communication', 'Précision', 'Résolution de problèmes'],
                'city' => 'Marseille',
                'bio' => 'Mécanicien passionné de sports automobiles depuis l\'enfance. J\'ai commencé ma carri�...',
                'experience' => "2015-2020 : Mécanicien principal, Team AlphaTauri F1\n2012-2015 : Mécanicien jun...",
                'education' => "2008-2010 : BTS Maintenance des véhicules, option véhicules de compétition\n2006-..."
            ],
            [
                'email' => 'candidat2@exemple.com',
                'firstName' => 'Emma',
                'lastName' => 'Moreau',
                'jobTitle' => 'Ingénieure Aérodynamique',
                'description' => 'Ingénieure aérodynamique spécialisée dans la simulation CFD et l\'optimisatio...',
                'technicalSkills' 
                    = > ['CFD', 'Simulation numérique', 'Conception aérodynamique', 'MATLAB', 'SolidWorks', 'Analyse de données'],
                'softSkills' 
                    = > ['Esprit analytique', 'Innovation', 'Travail en équipe', 'Présentation', 'Capacité d\'adaptation'],
                'city' => 'Bordeaux',
                'bio' => 'Ingénieure passionnée par l\'aérodynamique et la performance. Docteur en mécanique de...',
                'experience' => "2018-2023 : Ingénieure aérodynamique senior, Alpine F1 Team\n2015-2018 : Ingéni...",
                'education' => "2010-2013 : Doctorat en mécanique des fluides, École Polytechnique\n2008-2010 : Ma..."
            ],
            [
                'email' => 'candidat3@exemple.com',
                'firstName' => 'Lucas',
                'lastName' => 'Richard',
                'jobTitle' => 'Technicien Composite',
                'description' => 'Technicien spécialisé dans la fabrication et la réparation de pièces en maté...',
                'technicalSkills' 
                    = > ['Fabrication composite', 'Carbone préimprégné', 'Moulage sous vide', 'Réparation structurelle', 'Tests non destructifs'],
                'softSkills' 
                    = > ['Minutie', 'Attention aux détails', 'Gestion du temps', 'Auto-formation', 'Résistance à la pression'],
                'city' => 'Silverstone',
                'bio' => 'Technicien composite avec 6 ans d\'expérience dans le développement et la fabrication d...',
                'experience' => "2017-2023 : Technicien composite senior, McLaren Racing\n2014-2017 : Technicien co...",
                'education' => "2010-2012 : BTS Mise en œuvre des matériaux composites\n2008-2010 : Baccalauréat ..."
            ],
            [
                'email' => 'candidat4@exemple.com',
                'firstName' => 'Chloé',
                'lastName' => 'Lambert',
                'jobTitle' => 'Ingénieure Données',
                'description' => 'Ingénieure spécialisée dans l\'analyse de données télémétriques et la stra...',
                'technicalSkills' 
                    = > ['Télémétrie', 'Python', 'MATLAB', 'Machine Learning', 'Visualisation de données', 'SQL'],
                'softSkills' 
                    = > ['Analyse critique', 'Communication technique', 'Travail sous pression', 'Multitâche', 'Adaptabilité'],
                'city' => 'Milan',
                'bio' => 'Ingénieure en données avec formation en statistiques avancées et IA. Passionnée par l...',
                'experience' => "2019-2023 : Ingénieure données, Haas F1 Team\n2016-2019 : Analyste performance, ...",
                'education' => "2012-2014 : Master en ingénierie des données, Politecnico di Milano\n2009-2012 : L..."
            ],
            [
                'email' => 'candidat5@exemple.com',
                'firstName' => 'Maxime',
                'lastName' => 'Girard',
                'jobTitle' => 'Chef Mécanicien',
                'description' => 'Chef mécanicien expérimenté, responsable de la coordination d\'équipes techni...',
                'technicalSkills' 
                    = > ['Coordination d\'équipe', 'Gestion technique', 'Diagnostic avancé', 'Mécanique de précision', 'Systèmes électroniques embarqués'],
                'softSkills' 
                    = > ['Leadership', 'Prise de décision', 'Gestion de crise', 'Communication', 'Organisation'],
                'city' => 'Barcelone',
                'bio' => 'Chef mécanicien avec 12 ans d\'expérience en F1 et endurance. J\'ai dirigé des équipe...',
                'experience' => "2018-2023 : Chef mécanicien, Aston Martin F1\n2015-2018 : Mécanicien senior, Toy...",
                'education' => "2008-2010 : Ingénierie en mécanique automobile, ESTACA\n2005-2008 : BTS Maintenanc..."
            ]
        ];

        foreach ($applicantData as $index => $data) {
            $applicant = new Applicant();
            $applicant->setEmail($data['email']);
            $applicant->setFirstName($data['firstName']);
            $applicant->setLastName($data['lastName']);
            $applicant->setPassword($this->passwordHasher->hashPassword($applicant, 'password'));
            $applicant->setJobTitle($data['jobTitle']);
            $applicant->setDescription($data['description']);
            $applicant->setTechnicalSkills($data['technicalSkills']);
            $applicant->setSoftSkills($data['softSkills']);
            $applicant->setCity($data['city']);
            $applicant->setBio($data['bio']);
            $applicant->setExperience($data['experience']);
            $applicant->setEducation($data['education']);

            $manager->persist($applicant);
            $applicants[] = $applicant;
        }

        // Création des offres d'emploi
        $jobTitles = [
            'Mécanicien F1 - Équipe de stand',
            'Ingénieur aérodynamique F1',
            'Spécialiste suspensions F1',
            'Ingénieur télémétrie',
            'Chef mécanicien F1',
            'Technicien matériaux composites',
            'Ingénieur performance',
            'Responsable logistique course',
            'Ingénieur moteur',
            'Concepteur pièces F1'
        ];

        $locations 
            =  ['Monaco', 'Silverstone, UK', 'Maranello, Italie', 'Milton Keynes, UK', 'Enstone, UK', 'Viry-Châtillon, France', 'Hinwil, Suisse', 'Brackley, UK'];
        $contractTypes = ['CDI', 'CDD', 'Freelance', 'Stage', 'Alternance'];

        foreach ($recruiters as $index => $recruiter) {
            for ($j = 1; $j <= 2; $j++) {
                $jobOffer = new JobOffer();
                $title = $jobTitles[array_rand($jobTitles)];
                $jobOffer->setTitle($title . " - Position {$j}");
                $jobOffer
                    ->setDescription("Nous recherchons un professionnel expérimenté pour rejoindre notre équipe de Formule 1.
                
Responsabilités:
- Travailler sur les voitures pendant et entre les courses
- Collaborer avec les ingénieurs pour optimiser les performances
- Assurer la maintenance et les réparations rapides pendant les courses
- Participer aux tests et au développement

Profil recherché:
- Expérience en " . strtolower($title) . " de haut niveau
- Connaissance des règlements F1
- Capacité à travailler sous pression
- Disponibilité pour voyager à l'international
- Anglais courant obligatoire
- Capacité à s'intégrer dans une équipe soudée

Nous offrons:
- Un environnement de travail stimulant au sein d'une équipe passionnée
- Des opportunités de développement professionnel
- La possibilité de voyager sur les circuits du monde entier
- Un package salarial compétitif
- Des avantages sociaux attractifs");

                $jobOffer->setCompany($recruiter->getCompanyName());
                $jobOffer->setContractType($contractTypes[array_rand($contractTypes)]);
                $jobOffer->setLocation($locations[array_rand($locations)]);
                $jobOffer->setSalary(rand(35000, 90000));
                $jobOffer
                    ->setRequiredSkills(['Mécanique F1', 'Connaissance des règlements', 'Travail d\'équipe', 'Anglais courant', 'Résistance au stress', 'Disponibilité pour voyager']);
                $jobOffer->setExpiresAt(new \DateTime('+30 days'));
                $jobOffer->setRecruiter($recruiter);
                $jobOffer->setIsActive(true);
                $jobOffer->setIsRemote(rand(0, 1) === 1);
                $jobOffer->setContactEmail($recruiter->getEmail());

                $manager->persist($jobOffer);
            }
        }

        // Création des publications
        $postTitles = [
            'Les dernières innovations en F1',
            'Comment devenir mécanicien de F1',
            'Mon expérience dans les stands',
            'Les défis techniques des nouvelles réglementations',
            'Conseils pour postuler en F1',
            'L\'importance de la formation continue en motorsport',
            'L\'évolution des matériaux composites en F1',
            'Comment j\'ai décroché mon premier job en F1',
            'Les compétences clés recherchées par les équipes',
            'La vie d\'un ingénieur sur la route'
        ];

        $postContents = [
            'Les équipes de F1 investissent massivement dans les nouvelles technologies pour gagner ces précieux ...',

            'Devenir mécanicien de F1 demande beaucoup de travail et de détermination. J\'ai commencé ma carriè...',

            'Travailler dans les stands pendant une course est une expérience incroyable. L\'adrénaline est à so...',

            'Les nouvelles réglementations pour la saison prochaine vont considérablement changer notre approche ...',

            'Vous rêvez de travailler en F1? Voici mes conseils pour maximiser vos chances. D\'abord, spécialisez...',

            'Dans le monde de la F1, l\'apprentissage ne s\'arrête jamais. Les technologies évoluent si rapidemen...',

            'L\'utilisation des matériaux composites en F1 a révolutionné la conception des voitures de course. ...',

            'Décrocher mon premier emploi en F1 a été un parcours semé d\'embûches mais incroyablement gratifi...',

            'Les équipes de F1 recherchent aujourd\'hui bien plus que des compétences techniques. La capacité à...',

            'La vie d\'un ingénieur F1 sur la route est intense et exigeante. Entre les voyages constants, les long...'
        ];

        // Tous les utilisateurs publient
        $allUsers = array_merge($recruiters, $applicants);
        foreach ($allUsers as $user) {
            for ($p = 0; $p < 2; $p++) {
                $post = new Post();
                $randomIndex = array_rand($postTitles);
                $post->setTitle($postTitles[$randomIndex]);
                $post
                    ->setContent($postContents[$randomIndex] . "\n\nQu'en pensez-vous? Partagez vos expériences dans ce domaine! #F1 #Motorsport #CarrièreF1");
                $post->setAuthor($user);

                $manager->persist($post);
            }
        }

        // Création des demandes d'amitié
        // Chaque recruteur demande au moins 2 candidats en ami
        foreach ($recruiters as $recruiter) {
            $randomApplicants = $applicants;
            shuffle($randomApplicants);
            $selectedApplicants = array_slice($randomApplicants, 0, 2);

            foreach ($selectedApplicants as $applicant) {
                $friendship = new Friendship();
                $friendship->setRequester($recruiter);
                $friendship->setAddressee($applicant);
                $friendship->setStatus(Friendship::STATUS_ACCEPTED);
                $friendship->setUpdatedAt(new \DateTimeImmutable());

                $manager->persist($friendship);
            }
        }

        // Chaque candidat demande au moins 1 recruteur en ami
        foreach ($applicants as $applicant) {
            $randomRecruiters = $recruiters;
            shuffle($randomRecruiters);
            $selectedRecruiter = $randomRecruiters[0];

            // On vérifie que cette amitié n'existe pas déjà dans l'autre sens
            $exists = false;
            foreach ($applicant->getReceivedFriendRequests() as $request) {
                if ($request->getRequester() === $selectedRecruiter) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $friendship = new Friendship();
                $friendship->setRequester($applicant);
                $friendship->setAddressee($selectedRecruiter);

                // Certaines demandes sont en attente, d'autres acceptées
                if (rand(0, 1) === 1) {
                    $friendship->setStatus(Friendship::STATUS_ACCEPTED);
                    $friendship->setUpdatedAt(new \DateTimeImmutable());
                }

                $manager->persist($friendship);
            }
        }

        $manager->flush();
    }
}
