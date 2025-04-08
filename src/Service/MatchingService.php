<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Applicant;
use App\Entity\JobOffer;
use App\Repository\UserRepository;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;

class MatchingService
{
    private $entityManager;
    private $userRepository;
    private $jobOfferRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        JobOfferRepository $jobOfferRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->jobOfferRepository = $jobOfferRepository;
    }

    /**
     * Calcule un score de compatibilité entre un candidat et une offre d'emploi
     *
     * @param Applicant $applicant
     * @param JobOffer $jobOffer
     * @return array Score détaillé avec les raisons
     */
    public function calculateCompatibilityScore(Applicant $applicant, JobOffer $jobOffer): array
    {
        $score = 0;
        $maxScore = 0;
        $reasons = [];

        // Compétences techniques (poids: 40%)
        $technicalScore = $this->calculateTechnicalSkillsScore($applicant, $jobOffer);
        $score += $technicalScore['score'] * 0.4;
        $maxScore += $technicalScore['maxScore'] * 0.4;
        $reasons[] = [
            'category' => 'Compétences techniques',
            'score' => $technicalScore['score'],
            'maxScore' => $technicalScore['maxScore'],
            'matches' => $technicalScore['matches']
        ];

        // Expérience professionnelle (poids: 30%)
        $experienceScore = $this->calculateExperienceScore($applicant, $jobOffer);
        $score += $experienceScore['score'] * 0.3;
        $maxScore += $experienceScore['maxScore'] * 0.3;
        $reasons[] = [
            'category' => 'Expérience professionnelle',
            'score' => $experienceScore['score'],
            'maxScore' => $experienceScore['maxScore'],
            'details' => $experienceScore['details']
        ];

        // Soft skills (poids: 20%)
        $softSkillsScore = $this->calculateSoftSkillsScore($applicant, $jobOffer);
        $score += $softSkillsScore['score'] * 0.2;
        $maxScore += $softSkillsScore['maxScore'] * 0.2;
        $reasons[] = [
            'category' => 'Soft skills',
            'score' => $softSkillsScore['score'],
            'maxScore' => $softSkillsScore['maxScore'],
            'matches' => $softSkillsScore['matches']
        ];

        // Localisation/Remote (poids: 10%)
        $locationScore = $this->calculateLocationScore($applicant, $jobOffer);
        $score += $locationScore['score'] * 0.1;
        $maxScore += $locationScore['maxScore'] * 0.1;
        $reasons[] = [
            'category' => 'Localisation',
            'score' => $locationScore['score'],
            'maxScore' => $locationScore['maxScore'],
            'details' => $locationScore['details']
        ];

        // Normalisation du score final (0-100%)
        $normalizedScore = ($maxScore > 0) ? round(($score / $maxScore) * 100) : 0;

        return [
            'score' => $normalizedScore,
            'reasons' => $reasons,
            'applicant' => $applicant->getId(),
            'jobOffer' => $jobOffer->getId()
        ];
    }

    /**
     * Calcule le score basé sur les compétences techniques
     */
    private function calculateTechnicalSkillsScore(Applicant $applicant, JobOffer $jobOffer): array
    {
        $requiredSkills = $jobOffer->getRequiredSkills();
        $candidateSkills = $applicant->getTechnicalSkills() ?? [];

        if (empty($requiredSkills)) {
            return ['score' => 0, 'maxScore' => 0, 'matches' => []];
        }

        $matches = [];
        $matchCount = 0;

        foreach ($requiredSkills as $skill) {
            $found = false;
            foreach ($candidateSkills as $candidateSkill) {
                // Recherche fuzzy pour tenir compte des variations d'orthographe
                if ($this->isSimilarSkill($skill, $candidateSkill)) {
                    $found = true;
                    $matches[] = $skill;
                    break;
                }
            }
            if ($found) {
                $matchCount++;
            }
        }

        $maxScore = count($requiredSkills);
        $score = $matchCount;

        return [
            'score' => $score,
            'maxScore' => $maxScore,
            'matches' => $matches
        ];
    }

    /**
     * Compare deux compétences en tenant compte des variations d'orthographe
     */
    private function isSimilarSkill(string $skill1, string $skill2): bool
    {
        // Simplification pour éviter les différences mineures (casse, espaces)
        $skill1 = strtolower(trim($skill1));
        $skill2 = strtolower(trim($skill2));

        // Correspondance exacte
        if ($skill1 === $skill2) {
            return true;
        }

        // Correspondance partielle pour les acronymes et abréviations
        if (
            (strlen($skill1) <= 5 && strpos($skill2, $skill1) === 0) ||
            (strlen($skill2) <= 5 && strpos($skill1, $skill2) === 0)
        ) {
            return true;
        }

        // Distance de Levenshtein pour les fautes de frappe
        $distance = levenshtein($skill1, $skill2);
        $maxLength = max(strlen($skill1), strlen($skill2));

        // Tolérance proportionnelle à la longueur
        $threshold = min(2, ceil($maxLength * 0.3));

        return $distance <= $threshold;
    }

    /**
     * Calcule le score basé sur l'expérience professionnelle
     */
    private function calculateExperienceScore(Applicant $applicant, JobOffer $jobOffer): array
    {
        $workExperience = $applicant->getWorkExperience() ?? [];
        $educationHistory = $applicant->getEducationHistory() ?? [];

        // Si pas d'expérience ni d'éducation, score minimal
        if (empty($workExperience) && empty($educationHistory)) {
            return [
                'score' => 0,
                'maxScore' => 5,
                'details' => ['Aucune expérience professionnelle ni formation renseignée']
            ];
        }

        // Évaluation de la pertinence des expériences
        $score = 0;
        $details = [];

        // Nombre total d'années d'expérience
        $totalYearsExperience = 0;
        $relevantExperienceCount = 0;
        $relevantYearsExperience = 0;
        $mostRecentExperiences = [];

        // Trier les expériences par date (de la plus récente à la plus ancienne)
        usort($workExperience, function ($a, $b) {
            $endDateA = $a['endDate'] ?? 'present';
            $endDateB = $b['endDate'] ?? 'present';

            // Si une des dates est "present", elle est plus récente
            if ($endDateA === 'present' && $endDateB !== 'present') {
                return -1;
            }
            if ($endDateA !== 'present' && $endDateB === 'present') {
                return 1;
            }

            // Sinon, comparer les dates de fin
            try {
                $dateA = $endDateA === 'present' ? new \DateTime() : new \DateTime($endDateA);
                $dateB = $endDateB === 'present' ? new \DateTime() : new \DateTime($endDateB);
                return $dateB <=> $dateA; // Ordre décroissant
            } catch (\Exception $e) {
                return 0;
            }
        });

        // Limiter à 5 expériences maximum pour l'analyse
        $workExperience = array_slice($workExperience, 0, 5);

        foreach ($workExperience as $experience) {
            // Calcul de la durée de l'expérience
            $duration = isset($experience['startDate'], $experience['endDate'])
                ? $this->calculateExperienceDuration($experience['startDate'], $experience['endDate'])
                : 0;

            $totalYearsExperience += $duration;

            // Vérification de la pertinence par rapport au poste
            $isRelevant = $this->isRelevantExperience($experience, $jobOffer);

            if ($isRelevant) {
                $relevantExperienceCount++;
                $relevantYearsExperience += $duration;

                // Ajouter plus de détails sur l'expérience pertinente
                $expDetails = [];
                if (!empty($experience['title'])) {
                    $expDetails[] = $experience['title'];
                }
                if (!empty($experience['company'])) {
                    $expDetails[] = 'chez ' . $experience['company'];
                }
                if ($duration > 0) {
                    $expDetails[] = sprintf('(%.1f ans)', $duration);
                }

                $details[] = 'Expérience pertinente: ' . implode(' ', $expDetails);

                // Garder trace des expériences pertinentes les plus récentes
                $mostRecentExperiences[] = $experience;
            }
        }

        // Évaluation des formations pertinentes
        $relevantEducationCount = 0;
        $educationScore = 0;

        foreach ($educationHistory as $education) {
            $isRelevant = $this->isRelevantEducation($education, $jobOffer);

            if ($isRelevant) {
                $relevantEducationCount++;

                // Ajouter plus de détails sur la formation pertinente
                $eduDetails = [];
                if (!empty($education['degree'])) {
                    $eduDetails[] = $education['degree'];
                }
                if (!empty($education['institution'])) {
                    $eduDetails[] = 'à ' . $education['institution'];
                }

                $details[] = 'Formation pertinente: ' . implode(' ', $eduDetails);
            }
        }

        // Calculer le score basé sur plusieurs facteurs

        // 1. Score basé sur les années d'expérience totale (max 1.5 points)
        $yearsScore = min(1.5, $totalYearsExperience / 3);

        // 2. Score basé sur les années d'expérience pertinente (max 2 points)
        $relevantYearsScore = min(2, $relevantYearsExperience / 2);

        // 3. Score basé sur le nombre d'expériences pertinentes (max 1 point)
        $relevantCountScore = min(1, $relevantExperienceCount / 2);

        // 4. Bonus pour formation pertinente (max 0.5 point)
        $educationBonus = min(0.5, $relevantEducationCount * 0.25);

        // 5. Bonus pour expérience récente dans le domaine (max 0.5 point)
        $recentExperienceBonus = 0;
        if (!empty($mostRecentExperiences)) {
            $mostRecent = $mostRecentExperiences[0];
            $endDate = $mostRecent['endDate'] ?? '';

            // Si l'expérience est en cours ou s'est terminée il y a moins de 2 ans
            if ($endDate === 'present') {
                $recentExperienceBonus = 0.5;
            } else {
                try {
                    $end = new \DateTime($endDate);
                    $now = new \DateTime();
                    $yearsSinceEnd = $now->diff($end)->y;

                    if ($yearsSinceEnd <= 2) {
                        $recentExperienceBonus = 0.5;
                    } elseif ($yearsSinceEnd <= 5) {
                        $recentExperienceBonus = 0.25;
                    }
                } catch (\Exception $e) {
                    // En cas d'erreur de format de date, pas de bonus
                }
            }
        }

        // Score total (max 5 points)
        $score = $yearsScore + $relevantYearsScore + $relevantCountScore + $recentExperienceBonus + $educationBonus;
        $score = min(5, $score); // Plafonnement à 5

        // Ajouter un résumé au début de la liste de détails
        if ($relevantExperienceCount > 0 || $relevantEducationCount > 0) {
            $summaryParts = [];

            if ($relevantExperienceCount > 0) {
                $summaryParts[] = sprintf(
                    '%d expérience(s) pertinente(s) totalisant %.1f an(s)',
                    $relevantExperienceCount,
                    $relevantYearsExperience
                );
            }

            if ($relevantEducationCount > 0) {
                $summaryParts[] = sprintf(
                    '%d formation(s) pertinente(s)',
                    $relevantEducationCount
                );
            }

            array_unshift($details, implode(' et ', $summaryParts));
        } else {
            array_unshift($details, sprintf(
                'Expérience générale de %.1f an(s) sans correspondance directe avec le poste',
                $totalYearsExperience
            ));
        }

        return [
            'score' => $score,
            'maxScore' => 5,
            'details' => $details
        ];
    }

    /**
     * Vérifie si une expérience est pertinente pour l'offre d'emploi
     */
    private function isRelevantExperience(array $experience, JobOffer $jobOffer): bool
    {
        $jobTitle = $jobOffer->getTitle();
        $jobDescription = $jobOffer->getDescription();
        $requiredSkills = $jobOffer->getRequiredSkills();

        $experienceTitle = $experience['title'] ?? '';
        $experienceDescription = $experience['description'] ?? '';
        $experienceCompany = $experience['company'] ?? '';
        $experienceLocation = $experience['location'] ?? '';

        // Concaténer tous les champs d'expérience pour une analyse plus complète
        $experienceFullText = $experienceTitle . ' ' . $experienceDescription . ' ' . $experienceCompany . ' ' . $experienceLocation;

        // 1. Vérification par mots-clés entre le titre de l'expérience et le titre du poste
        if ($this->hasCommonKeywords($experienceTitle, $jobTitle, 2)) {
            return true;
        }

        // 2. Vérification par mots-clés entre la description de l'expérience et la description du poste
        if ($this->hasCommonKeywords($experienceDescription, $jobDescription, 3)) {
            return true;
        }

        // 3. Vérification des compétences requises dans tous les champs d'expérience
        foreach ($requiredSkills as $skill) {
            if (stripos($experienceFullText, $skill) !== false) {
                return true;
            }
        }

        // 4. Vérification des secteurs d'activité (ex: F1, automobile, sport)
        $jobSectors = $this->extractSectors($jobTitle . ' ' . $jobDescription);
        $experienceSectors = $this->extractSectors($experienceFullText);

        if (!empty(array_intersect($jobSectors, $experienceSectors))) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si une formation est pertinente pour l'offre d'emploi
     */
    private function isRelevantEducation(array $education, JobOffer $jobOffer): bool
    {
        $jobTitle = $jobOffer->getTitle();
        $jobDescription = $jobOffer->getDescription();
        $requiredSkills = $jobOffer->getRequiredSkills();

        $educationDegree = $education['degree'] ?? '';
        $educationInstitution = $education['institution'] ?? '';
        $educationDescription = $education['description'] ?? '';
        $educationLocation = $education['location'] ?? '';

        // Concaténer tous les champs d'éducation pour une analyse plus complète
        $educationFullText = $educationDegree . ' ' . $educationInstitution . ' ' . $educationDescription . ' ' . $educationLocation;

        // 1. Vérification par mots-clés entre le diplôme et le titre du poste
        if ($this->hasCommonKeywords($educationDegree, $jobTitle, 1)) {
            return true;
        }

        // 2. Vérification par mots-clés entre la description de la formation et la description du poste
        if ($this->hasCommonKeywords($educationDescription, $jobDescription, 2)) {
            return true;
        }

        // 3. Vérification des compétences requises dans tous les champs de formation
        foreach ($requiredSkills as $skill) {
            if (stripos($educationFullText, $skill) !== false) {
                return true;
            }
        }

        // 4. Vérification des secteurs d'activité (ex: F1, automobile, sport)
        $jobSectors = $this->extractSectors($jobTitle . ' ' . $jobDescription);
        $educationSectors = $this->extractSectors($educationFullText);

        if (!empty(array_intersect($jobSectors, $educationSectors))) {
            return true;
        }

        return false;
    }

    /**
     * Extrait les secteurs d'activité potentiels d'un texte
     */
    private function extractSectors(string $text): array
    {
        $sectors = [
            'f1', 'formule 1', 'sport automobile', 'motorsport', 'grand prix', 'course automobile',
            'automobile', 'auto', 'voiture', 'racing', 'rallye', 'circuit',
            'sport', 'sportif', 'competition', 'équipe sportive', 'team',
            'ingénierie', 'mécanique', 'technique', 'technologie', 'aérodynamique',
            'logistique', 'composite', 'industrie'
        ];

        $foundSectors = [];
        foreach ($sectors as $sector) {
            if (stripos($text, $sector) !== false) {
                $foundSectors[] = $sector;
            }
        }

        return $foundSectors;
    }

    /**
     * Vérifie si deux textes ont des mots-clés en commun
     * @param string $text1 Premier texte à comparer
     * @param string $text2 Second texte à comparer
     * @param int $minCommonCount Nombre minimum de mots-clés communs requis
     * @return bool
     */
    private function hasCommonKeywords(string $text1, string $text2, int $minCommonCount = 1): bool
    {
        $keywords1 = $this->extractKeywords($text1);
        $keywords2 = $this->extractKeywords($text2);

        $common = array_intersect($keywords1, $keywords2);

        return count($common) >= $minCommonCount;
    }

    /**
     * Calcule la durée d'une expérience en années
     */
    private function calculateExperienceDuration(string $startDate, string $endDate): float
    {
        try {
            $start = new \DateTime($startDate);
            $end = $endDate === 'present' ? new \DateTime() : new \DateTime($endDate);

            $interval = $end->diff($start);
            return $interval->y + ($interval->m / 12);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Extrait les mots-clés significatifs d'un texte
     */
    private function extractKeywords(string $text): array
    {
        // Liste des mots vides en français
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'et', 'ou', 'de', 'du', 'en', 'à', 'au', 'aux', 'par', 'pour'];

        // Nettoyage et tokenisation
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Filtrage des mots courts et des mots vides
        $keywords = [];
        foreach ($words as $word) {
            if (strlen($word) > 3 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    /**
     * Calcule le score basé sur les soft skills
     */
    private function calculateSoftSkillsScore(Applicant $applicant, JobOffer $jobOffer): array
    {
        // Dans un cas réel, on extrairait les soft skills de la description de l'offre
        // Pour cette démo, on utilise une liste de soft skills communs dans le sport automobile
        $jobSoftSkillsList = $this->extractSoftSkillsFromJobDescription($jobOffer->getDescription());
        $candidateSoftSkills = $applicant->getSoftSkills() ?? [];

        if (empty($jobSoftSkillsList) || empty($candidateSoftSkills)) {
            return ['score' => 0, 'maxScore' => 0, 'matches' => []];
        }

        $matches = [];
        foreach ($candidateSoftSkills as $skill) {
            foreach ($jobSoftSkillsList as $jobSkill) {
                if ($this->isSimilarSkill($skill, $jobSkill)) {
                    $matches[] = $skill;
                    break;
                }
            }
        }

        $matchCount = count($matches);
        $maxScore = min(5, count($jobSoftSkillsList));
        $score = min($matchCount, $maxScore);

        return [
            'score' => $score,
            'maxScore' => $maxScore,
            'matches' => $matches
        ];
    }

    /**
     * Extrait les soft skills potentiels d'une description de poste
     */
    private function extractSoftSkillsFromJobDescription(string $description): array
    {
        $commonSoftSkills = [
            // Communication
            'communication', 'écoute active', 'expression orale', 'présentation', 'négociation',
            'rédaction', 'vulgarisation', 'diplomatie', 'persuasion', 'médiation',

            // Travail en équipe
            'travail d\'équipe', 'équipe', 'collaboration', 'coopération', 'esprit d\'équipe',
            'coordination', 'cohésion', 'entraide', 'synergie',

            // Leadership
            'leadership', 'management', 'gestion d\'équipe', 'encadrement', 'motivation d\'équipe',
            'délégation', 'prise de décision', 'coaching', 'mentorat', 'influence',

            // Adaptation et résilience
            'adaptabilité', 'flexibilité', 'résilience', 'gestion du stress', 'gestion de crise',
            'résistance à la pression', 'agilité', 'polyvalence', 'réactivité',

            // Organisation
            'organisation', 'planification', 'gestion du temps', 'priorisation', 'ponctualité',
            'méthode', 'rigueur', 'précision', 'autonomie', 'efficacité',

            // Créativité et résolution de problèmes
            'créativité', 'innovation', 'résolution de problèmes', 'pensée critique', 'analyse',
            'esprit critique', 'prise d\'initiative', 'curiosité', 'proactivité',

            // Relationnel
            'empathie', 'intelligence émotionnelle', 'relationnel', 'sociabilité', 'sens du service',
            'orientation client', 'respect', 'éthique', 'bienveillance'
        ];

        $foundSkills = [];
        foreach ($commonSoftSkills as $skill) {
            if (stripos($description, $skill) !== false) {
                $foundSkills[] = $skill;
            }
        }

        // Ajouter quelques soft skills par défaut si rien n'est trouvé
        if (empty($foundSkills)) {
            return ['communication', 'travail d\'équipe', 'adaptabilité'];
        }

        return $foundSkills;
    }

    /**
     * Calcule le score basé sur la localisation/préférence de travail à distance
     */
    private function calculateLocationScore(Applicant $applicant, JobOffer $jobOffer): array
    {
        // Dans un système réel, l'utilisateur aurait des préférences de localisation
        // Pour cette démo, on suppose qu'il n'y a pas de préférence stricte

        $jobIsRemote = $jobOffer->getIsRemote();
        $jobLocation = $jobOffer->getLocation();

        // Extrait la préférence de l'utilisateur (à récupérer d'un champ préférences)
        $user = $applicant; // Cast to User object
        $userPreferredLocation = $user->getCity() ?? '';
        $userPreferredRemote = true; // Par défaut on suppose que le remote est accepté

        $details = [];
        $score = 0;

        // Si le poste est en remote et que le candidat accepte le remote
        if ($jobIsRemote && $userPreferredRemote) {
            $score = 5;
            $details[] = 'Compatibilité parfaite avec le travail à distance';
        }
        // Si le poste est dans la même ville que celle préférée par le candidat
        elseif (!empty($userPreferredLocation) && stripos($jobLocation, $userPreferredLocation) !== false) {
            $score = 5;
            $details[] = 'Localisation idéale: ' . $jobLocation;
        }
        // Score par défaut si aucune localisation n'est spécifiée
        else {
            $score = 3;
            $details[] = 'Compatibilité de localisation moyenne';
        }

        return [
            'score' => $score,
            'maxScore' => 5,
            'details' => $details
        ];
    }

    /**
     * Applique le cast de User vers Applicant si nécessaire
     */
    private function ensureApplicant($user): Applicant
    {
        // Si c'est déjà un Applicant, on le retourne directement
        if ($user instanceof Applicant) {
            return $user;
        }

        // Si c'est un User, on vérifie qu'il a le rôle ROLE_POSTULANT
        if (!($user instanceof User) || !in_array('ROLE_POSTULANT', $user->getRoles())) {
            throw new \LogicException('L\'utilisateur doit avoir le rôle ROLE_POSTULANT');
        }

        // On récupère l'entité Applicant correspondante via l'EntityManager
        $applicant = $this->entityManager->getRepository(Applicant::class)->find($user->getId());

        if (!$applicant) {
            throw new \LogicException('Impossible de trouver l\'entité Applicant correspondante');
        }

        return $applicant;
    }

    /**
     * Trouve les meilleures offres d'emploi pour un candidat
     *
     * @param User|Applicant $user
     * @param int $limit Nombre maximum d'offres à retourner
     * @return array
     */
    public function findBestJobOffersForCandidate($user, int $limit = 5): array
    {
        // On s'assure d'avoir un objet Applicant
        $applicant = $this->ensureApplicant($user);

        // Récupérer toutes les offres d'emploi actives
        $activeJobOffers = $this->jobOfferRepository->findBy(['isActive' => true]);

        // Calculer le score de compatibilité pour chaque offre
        $scoredOffers = [];
        foreach ($activeJobOffers as $jobOffer) {
            $compatibilityScore = $this->calculateCompatibilityScore($applicant, $jobOffer);
            $scoredOffers[] = [
                'jobOffer' => $jobOffer,
                'score' => $compatibilityScore['score'],
                'reasons' => $compatibilityScore['reasons']
            ];
        }

        // Trier par score de compatibilité décroissant
        usort($scoredOffers, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Limiter le nombre de résultats
        return array_slice($scoredOffers, 0, $limit);
    }

    /**
     * Trouve les meilleurs candidats pour une offre d'emploi
     *
     * @param JobOffer $jobOffer
     * @param int $limit Nombre maximum de candidats à retourner
     * @return array
     */
    public function findBestCandidatesForJobOffer(JobOffer $jobOffer, int $limit = 10): array
    {
        // Récupérer tous les candidats via le repository spécifique des Applicant
        $allApplicants = $this->entityManager->getRepository(Applicant::class)->findAll();

        $scoredCandidates = [];

        foreach ($allApplicants as $applicant) {
            $compatibilityScore = $this->calculateCompatibilityScore($applicant, $jobOffer);
            $scoredCandidates[] = [
                'applicant' => $applicant,
                'score' => $compatibilityScore['score'],
                'reasons' => $compatibilityScore['reasons']
            ];
        }

        // Trier par score de compatibilité décroissant
        usort($scoredCandidates, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Limiter le nombre de résultats
        return array_slice($scoredCandidates, 0, $limit);
    }

    /**
     * Récupère un applicant depuis son ID, avec gestion d'erreur appropriée
     *
     * @param int $applicantId
     * @return Applicant
     * @throws \LogicException Si l'applicant n'existe pas
     */
    public function getApplicantById(int $applicantId): Applicant
    {
        $applicant = $this->entityManager->getRepository(Applicant::class)->find($applicantId);

        if (!$applicant) {
            throw new \LogicException('Candidat non trouvé');
        }

        return $applicant;
    }
}
