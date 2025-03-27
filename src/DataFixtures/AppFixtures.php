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
                'companyDescription' => 'SpeedTech Racing est une écurie de Formule 1 en pleine expansion, reconnue pour ses innovations technologiques et sa culture d\'équipe dynamique. Fondée en 2010, notre équipe a rapidement progressé dans les classements grâce à notre engagement envers l\'excellence et l\'innovation.',
                'city' => 'Paris',
                'bio' => 'Directeur des ressources humaines chez SpeedTech Racing avec 15 ans d\'expérience dans le recrutement pour les sports mécaniques. Passionné par la constitution d\'équipes performantes.',
                'jobTitle' => 'Directeur des Ressources Humaines'
            ],
            [
                'email' => 'recruteur2@exemple.com',
                'firstName' => 'Sophie',
                'lastName' => 'Martin',
                'companyName' => 'Apex Performance',
                'companyDescription' => 'Apex Performance est un fournisseur de premier plan pour les équipes de F1, spécialisé dans les composants aérodynamiques et les systèmes de suspension. Nous travaillons avec les meilleures écuries pour développer des solutions innovantes qui maximisent les performances sur piste.',
                'city' => 'Lyon',
                'bio' => 'Responsable recrutement chez Apex Performance. Spécialisée dans la recherche de talents techniques pour l\'industrie automobile de haute performance. Ancienne ingénieure reconvertie RH.',
                'jobTitle' => 'Responsable Recrutement'
            ],
            [
                'email' => 'recruteur3@exemple.com',
                'firstName' => 'Nicolas',
                'lastName' => 'Leroy',
                'companyName' => 'Turbo Dynamics',
                'companyDescription' => 'Turbo Dynamics est leader dans la conception de moteurs et systèmes de propulsion pour la compétition automobile. Notre équipe d\'ingénieurs et de techniciens développe les groupes propulseurs de nouvelle génération, combinant puissance, efficacité et fiabilité.',
                'city' => 'Monaco',
                'bio' => 'Fondateur et PDG de Turbo Dynamics. Ancien ingénieur moteur en F1 avec plus de 20 ans d\'expérience dans les sports mécaniques. Toujours à la recherche des meilleurs talents pour repousser les limites.',
                'jobTitle' => 'PDG et Responsable Recrutement'
            ],
            [
                'email' => 'recruteur4@exemple.com',
                'firstName' => 'Camille',
                'lastName' => 'Petit',
                'companyName' => 'FastTrack Engineering',
                'companyDescription' => 'FastTrack Engineering fournit des services de conception et d\'ingénierie aux équipes de Formule 1 et autres compétitions de haut niveau. Notre expertise couvre l\'aérodynamique, les matériaux composites et l\'optimisation des performances.',
                'city' => 'Londres',
                'bio' => 'Directrice des opérations chez FastTrack Engineering. Diplômée en ingénierie mécanique de l\'Imperial College. 10 ans d\'expérience dans la gestion d\'équipes techniques en F1.',
                'jobTitle' => 'Directrice des Opérations'
            ],
            [
                'email' => 'recruteur5@exemple.com',
                'firstName' => 'Alexandre',
                'lastName' => 'Bernard',
                'companyName' => 'Elite Motorsport',
                'companyDescription' => 'Elite Motorsport est une écurie indépendante avec une riche histoire dans les compétitions automobiles. Nous nous concentrons sur le développement de jeunes talents et l\'innovation technique pour défier les grandes équipes avec un budget optimisé.',
                'city' => 'Maranello',
                'bio' => 'Responsable technique et recrutement chez Elite Motorsport. Expérience internationale dans la direction d\'équipes de course. Cherche à constituer une équipe d\'élite de passionnés de motorsport.',
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
                'description' => 'Mécanicien passionné avec 8 ans d\'expérience dans les stands F1. Spécialiste des arrêts au stand rapides et de la préparation des voitures pour les courses.',
                'technicalSkills' => ['Mécanique de précision', 'Systèmes hydrauliques', 'Aérodynamique', 'Maintenance préventive', 'Diagnostic rapide'],
                'softSkills' => ['Travail d\'équipe', 'Résistance au stress', 'Communication', 'Précision', 'Résolution de problèmes'],
                'city' => 'Marseille',
                'bio' => 'Mécanicien passionné de sports automobiles depuis l\'enfance. J\'ai commencé ma carrière en F3 avant de rejoindre la F1 il y a 8 ans. J\'ai travaillé avec plusieurs équipes du milieu de grille et souhaite maintenant relever de nouveaux défis techniques.',
                'experience' => "2015-2020 : Mécanicien principal, Team AlphaTauri F1\n2012-2015 : Mécanicien junior, Williams Racing\n2010-2012 : Apprenti mécanicien, Formule 3",
                'education' => "2008-2010 : BTS Maintenance des véhicules, option véhicules de compétition\n2006-2008 : Baccalauréat Professionnel Maintenance Automobile"
            ],
            [
                'email' => 'candidat2@exemple.com',
                'firstName' => 'Emma',
                'lastName' => 'Moreau',
                'jobTitle' => 'Ingénieure Aérodynamique',
                'description' => 'Ingénieure aérodynamique spécialisée dans la simulation CFD et l\'optimisation des performances. Expérience en soufflerie et développement de composants aérodynamiques pour la F1.',
                'technicalSkills' => ['CFD', 'Simulation numérique', 'Conception aérodynamique', 'MATLAB', 'SolidWorks', 'Analyse de données'],
                'softSkills' => ['Esprit analytique', 'Innovation', 'Travail en équipe', 'Présentation', 'Capacité d\'adaptation'],
                'city' => 'Bordeaux',
                'bio' => 'Ingénieure passionnée par l\'aérodynamique et la performance. Docteur en mécanique des fluides avec expérience en soufflerie F1. Je cherche à rejoindre une équipe innovante pour développer les voitures de course de demain.',
                'experience' => "2018-2023 : Ingénieure aérodynamique senior, Alpine F1 Team\n2015-2018 : Ingénieure R&D, Dallara Automobili\n2013-2015 : Assistante de recherche, Institut Aérodynamique",
                'education' => "2010-2013 : Doctorat en mécanique des fluides, École Polytechnique\n2008-2010 : Master en ingénierie aérospatiale, ISAE-SUPAERO\n2005-2008 : Licence en physique appliquée, Université de Bordeaux"
            ],
            [
                'email' => 'candidat3@exemple.com',
                'firstName' => 'Lucas',
                'lastName' => 'Richard',
                'jobTitle' => 'Technicien Composite',
                'description' => 'Technicien spécialisé dans la fabrication et la réparation de pièces en matériaux composites pour la F1. Expert en carbone, kevlar et fibres hybrides pour applications haute performance.',
                'technicalSkills' => ['Fabrication composite', 'Carbone préimprégné', 'Moulage sous vide', 'Réparation structurelle', 'Tests non destructifs'],
                'softSkills' => ['Minutie', 'Attention aux détails', 'Gestion du temps', 'Auto-formation', 'Résistance à la pression'],
                'city' => 'Silverstone',
                'bio' => 'Technicien composite avec 6 ans d\'expérience dans le développement et la fabrication de pièces légères et résistantes pour la compétition automobile. Passionné par l\'innovation dans les matériaux.',
                'experience' => "2017-2023 : Technicien composite senior, McLaren Racing\n2014-2017 : Technicien composite, Sauber F1 Team\n2012-2014 : Assistant technique, GT Motorsport",
                'education' => "2010-2012 : BTS Mise en œuvre des matériaux composites\n2008-2010 : Baccalauréat STI Génie mécanique"
            ],
            [
                'email' => 'candidat4@exemple.com',
                'firstName' => 'Chloé',
                'lastName' => 'Lambert',
                'jobTitle' => 'Ingénieure Données',
                'description' => 'Ingénieure spécialisée dans l\'analyse de données télémétriques et la stratégie de course. Expérience dans le développement d\'algorithmes d\'optimisation de performance et aide à la décision en temps réel.',
                'technicalSkills' => ['Télémétrie', 'Python', 'MATLAB', 'Machine Learning', 'Visualisation de données', 'SQL'],
                'softSkills' => ['Analyse critique', 'Communication technique', 'Travail sous pression', 'Multitâche', 'Adaptabilité'],
                'city' => 'Milan',
                'bio' => 'Ingénieure en données avec formation en statistiques avancées et IA. Passionnée par l\'extraction d\'insights à partir des gigaoctets de données générées par les voitures de course modernes pour optimiser les performances.',
                'experience' => "2019-2023 : Ingénieure données, Haas F1 Team\n2016-2019 : Analyste performance, Ferrari Driver Academy\n2014-2016 : Stagiaire data analyst, Prema Racing",
                'education' => "2012-2014 : Master en ingénierie des données, Politecnico di Milano\n2009-2012 : Licence en mathématiques appliquées, Université de Lyon"
            ],
            [
                'email' => 'candidat5@exemple.com',
                'firstName' => 'Maxime',
                'lastName' => 'Girard',
                'jobTitle' => 'Chef Mécanicien',
                'description' => 'Chef mécanicien expérimenté, responsable de la coordination d\'équipes techniques et de la préparation des voitures. Spécialiste de la résolution de problèmes complexes sous contrainte de temps.',
                'technicalSkills' => ['Coordination d\'équipe', 'Gestion technique', 'Diagnostic avancé', 'Mécanique de précision', 'Systèmes électroniques embarqués'],
                'softSkills' => ['Leadership', 'Prise de décision', 'Gestion de crise', 'Communication', 'Organisation'],
                'city' => 'Barcelone',
                'bio' => 'Chef mécanicien avec 12 ans d\'expérience en F1 et endurance. J\'ai dirigé des équipes techniques dans plusieurs championnats majeurs et contribué à des victoires en Grand Prix. Recherche un nouveau défi dans une équipe ambitieuse.',
                'experience' => "2018-2023 : Chef mécanicien, Aston Martin F1\n2015-2018 : Mécanicien senior, Toyota Gazoo Racing (WEC)\n2011-2015 : Mécanicien, Red Bull Racing",
                'education' => "2008-2010 : Ingénierie en mécanique automobile, ESTACA\n2005-2008 : BTS Maintenance Automobile"
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
        
        $locations = ['Monaco', 'Silverstone, UK', 'Maranello, Italie', 'Milton Keynes, UK', 'Enstone, UK', 'Viry-Châtillon, France', 'Hinwil, Suisse', 'Brackley, UK'];
        $contractTypes = ['CDI', 'CDD', 'Freelance', 'Stage', 'Alternance'];
        
        foreach ($recruiters as $index => $recruiter) {
            for ($j = 1; $j <= 2; $j++) {
                $jobOffer = new JobOffer();
                $title = $jobTitles[array_rand($jobTitles)];
                $jobOffer->setTitle($title . " - Position {$j}");
                $jobOffer->setDescription("Nous recherchons un professionnel expérimenté pour rejoindre notre équipe de Formule 1.
                
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
                $jobOffer->setRequiredSkills(['Mécanique F1', 'Connaissance des règlements', 'Travail d\'équipe', 'Anglais courant', 'Résistance au stress', 'Disponibilité pour voyager']);
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
            'Les équipes de F1 investissent massivement dans les nouvelles technologies pour gagner ces précieux dixièmes de seconde. Cette année, nous avons vu des avancées impressionnantes dans l\'aérodynamique active et les systèmes de refroidissement. Les planchers des voitures sont également devenus un domaine clé de développement suite aux derniers changements de règlement. Les équipes qui maîtrisent l\'effet de sol ont un avantage significatif.',
            
            'Devenir mécanicien de F1 demande beaucoup de travail et de détermination. J\'ai commencé ma carrière dans les championnats nationaux, puis j\'ai progressé vers la F3 et la F2. Un BTS en mécanique automobile spécialisé en compétition est un excellent point de départ, mais rien ne remplace l\'expérience de terrain. Les stages sont essentiels. La maîtrise de l\'anglais est également indispensable dans ce milieu international.',
            
            'Travailler dans les stands pendant une course est une expérience incroyable. L\'adrénaline est à son comble quand la voiture arrive pour un arrêt au stand de 2 secondes. Chaque membre de l\'équipe doit être parfaitement synchronisé et anticiper tout problème potentiel. La pression est énorme, mais la satisfaction après un arrêt parfait est incomparable. C\'est un véritable travail d\'équipe où chaque milliseconde compte.',
            
            'Les nouvelles réglementations pour la saison prochaine vont considérablement changer notre approche du développement aérodynamique. La réduction supplémentaire de l\'appui va forcer les équipes à repenser leurs concepts. La limitation du temps en soufflerie favorise désormais la simulation CFD, mais avec des restrictions de puissance de calcul. C\'est un défi passionnant pour les ingénieurs qui doivent optimiser leurs ressources.',
            
            'Vous rêvez de travailler en F1? Voici mes conseils pour maximiser vos chances. D\'abord, spécialisez-vous dans un domaine précis : mécanique, aérodynamique, matériaux composites ou analyse de données. Ensuite, développez votre réseau - LinkedIn est crucial. Participez à des événements du secteur. Soyez prêt à commencer par des postes juniors ou des stages, même si vous avez de l\'expérience dans d\'autres industries. La persévérance est la clé!',
            
            'Dans le monde de la F1, l\'apprentissage ne s\'arrête jamais. Les technologies évoluent si rapidement que la formation continue est essentielle pour rester compétitif. Les équipes investissent massivement dans le développement des compétences de leur personnel. En tant que professionnel du motorsport, consacrer du temps à se former sur les nouvelles méthodes et technologies est aussi important que le travail quotidien.',
            
            'L\'utilisation des matériaux composites en F1 a révolutionné la conception des voitures de course. Des premières pièces en carbone dans les années 80 aux structures hybrides ultra-sophistiquées d\'aujourd\'hui, l\'évolution a été spectaculaire. Les nouveaux composites permettent une rigidité exceptionnelle pour un poids minimal, tout en absorbant l\'énergie en cas d\'impact. C\'est un domaine fascinant qui continue d\'évoluer.',
            
            'Décrocher mon premier emploi en F1 a été un parcours semé d\'embûches mais incroyablement gratifiant. Après des années d\'études et plusieurs stages dans des catégories inférieures, j\'ai finalement eu ma chance grâce à une candidature spontanée persistante. La clé a été de montrer ma passion et ma détermination lors des entretiens, et de mettre en avant mes projets personnels liés au motorsport.',
            
            'Les équipes de F1 recherchent aujourd\'hui bien plus que des compétences techniques. La capacité à travailler sous pression, l\'esprit d\'équipe, l\'adaptabilité et la communication sont devenus aussi importants que l\'expertise dans votre domaine. La F1 moderne est tellement complexe qu\'une collaboration efficace entre les différents départements est cruciale pour le succès.',
            
            'La vie d\'un ingénieur F1 sur la route est intense et exigeante. Entre les voyages constants, les longues journées de travail et le décalage horaire, c\'est un véritable défi physique et mental. Mais l\'expérience de travailler sur les circuits les plus emblématiques du monde et la camaraderie au sein de l\'équipe compensent largement ces difficultés. C\'est un mode de vie unique qui demande des sacrifices mais offre des récompenses incomparables.'
        ];
        
        // Tous les utilisateurs publient
        $allUsers = array_merge($recruiters, $applicants);
        foreach ($allUsers as $user) {
            for ($p = 0; $p < 2; $p++) {
                $post = new Post();
                $randomIndex = array_rand($postTitles);
                $post->setTitle($postTitles[$randomIndex]);
                $post->setContent($postContents[$randomIndex] . "\n\nQu'en pensez-vous? Partagez vos expériences dans ce domaine! #F1 #Motorsport #CarrièreF1");
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
