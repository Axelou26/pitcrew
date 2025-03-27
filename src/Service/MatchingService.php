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
        
        // Si pas d'expérience, score minimal
        if (empty($workExperience)) {
            return [
                'score' => 0,
                'maxScore' => 5,
                'details' => ['Aucune expérience professionnelle']
            ];
        }
        
        // Évaluation de la pertinence des expériences
        $score = 0;
        $details = [];
        
        // Nombre total d'années d'expérience
        $totalYearsExperience = 0;
        $relevantExperienceCount = 0;
        
        foreach ($workExperience as $experience) {
            // Calcul de la durée de l'expérience si les dates sont disponibles
            $duration = isset($experience['startDate'], $experience['endDate']) 
                ? $this->calculateExperienceDuration($experience['startDate'], $experience['endDate']) 
                : 0;
            
            $totalYearsExperience += $duration;
            
            // Vérification de la pertinence par rapport au poste
            $isRelevant = $this->isRelevantExperience($experience, $jobOffer);
            
            if ($isRelevant) {
                $relevantExperienceCount++;
                $details[] = 'Expérience pertinente: ' . ($experience['title'] ?? 'Non spécifiée');
            }
        }
        
        // Score basé sur les années d'expérience (max 3 points)
        $yearsScore = min(3, $totalYearsExperience / 2);
        
        // Score basé sur la pertinence (max 2 points)
        $relevanceScore = min(2, $relevantExperienceCount);
        
        $score = $yearsScore + $relevanceScore;
        
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
        
        // Vérification du titre
        if ($this->hasCommonKeywords($experienceTitle, $jobTitle)) {
            return true;
        }
        
        // Vérification de la description
        if ($this->hasCommonKeywords($experienceDescription, $jobDescription)) {
            return true;
        }
        
        // Vérification des compétences requises
        foreach ($requiredSkills as $skill) {
            if (
                stripos($experienceTitle, $skill) !== false || 
                stripos($experienceDescription, $skill) !== false
            ) {
                return true;
            }
        }
        
        return false;
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
     * Vérifie si deux textes ont des mots-clés en commun
     */
    private function hasCommonKeywords(string $text1, string $text2): bool
    {
        $keywords1 = $this->extractKeywords($text1);
        $keywords2 = $this->extractKeywords($text2);
        
        $common = array_intersect($keywords1, $keywords2);
        
        return count($common) > 0;
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
            'communication', 'travail d\'équipe', 'équipe', 'leadership', 'gestion du stress',
            'adaptabilité', 'réactivité', 'autonomie', 'rigueur', 'organisation',
            'créativité', 'innovation', 'résolution de problèmes', 'analytique',
            'prise de décision', 'négociation', 'motivation', 'ponctualité'
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
        usort($scoredOffers, function($a, $b) {
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
        usort($scoredCandidates, function($a, $b) {
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