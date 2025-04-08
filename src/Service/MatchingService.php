<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Applicant;
use App\Entity\JobOffer;
use App\Repository\UserRepository;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use DateTime;

class MatchingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly JobOfferRepository $jobOfferRepository
    ) {}

    /**
     * Calcule un score de compatibilité entre un candidat et une offre d'emploi
     *
     * @param Applicant $applicant
     * @param JobOffer $jobOffer
     * @return array{
     *     score: int,
     *     reasons: array<array{
     *         category: string,
     *         score: int|float,
     *         maxScore: int|float,
     *         matches?: array<string>,
     *         details?: array<string>
     *     }>,
     *     applicant: int,
     *     jobOffer: int
     * }
     * @throws RuntimeException Si les données requises sont manquantes
     */
    public function calculateCompatibilityScore(Applicant $applicant, JobOffer $jobOffer): array
    {
        if (!$applicant->getId() || !$jobOffer->getId()) {
            throw new RuntimeException('L\'applicant et l\'offre d\'emploi doivent avoir des IDs valides');
        }

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
        $experienceScore = $this->calculateExperienceScore($applicant->getWorkExperience(), $jobOffer);
        $score += $experienceScore * 0.3;
        $maxScore += 100 * 0.3;
        $reasons[] = [
            'category' => 'Expérience professionnelle',
            'score' => $experienceScore,
            'maxScore' => 100,
            'details' => []
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
        $normalizedScore = $this->normalizeScore($score, $maxScore);

        return [
            'score' => $normalizedScore,
            'reasons' => $reasons,
            'applicant' => $applicant->getId(),
            'jobOffer' => $jobOffer->getId()
        ];
    }

    /**
     * Calcule le score basé sur les compétences techniques
     * 
     * @return array{score: int, maxScore: int, matches: array<string>}
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
            if (empty($skill)) continue;
            
            $found = false;
            foreach ($candidateSkills as $candidateSkill) {
                if (empty($candidateSkill)) continue;
                
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

        $maxScore = count(array_filter($requiredSkills));
        $score = $matchCount;

        return [
            'score' => $score,
            'maxScore' => $maxScore,
            'matches' => array_unique($matches)
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
    private function calculateExperienceScore(array $experiences, JobOffer $jobOffer): float
    {
        $score = 0;
        $relevantExperiences = $this->findRelevantExperiences($experiences, $jobOffer);
        $experienceScore = $this->calculateBaseExperienceScore($relevantExperiences);
        $bonusScore = $this->calculateExperienceBonusScore($relevantExperiences, $jobOffer);
        
        return min(100, $experienceScore + $bonusScore);
    }

    /**
     * @param array<string, mixed> $experiences
     */
    private function findRelevantExperiences(array $experiences, JobOffer $jobOffer): array
    {
        $relevantExperiences = [];
        $jobKeywords = $this->extractKeywords($jobOffer->getTitle() . ' ' . $jobOffer->getDescription());
        
        foreach ($experiences as $experience) {
            if ($this->isExperienceRelevant($experience, $jobKeywords)) {
                $relevantExperiences[] = $experience;
            }
        }
        
        return $relevantExperiences;
    }

    /**
     * @param array<string, mixed> $relevantExperiences
     */
    private function calculateBaseExperienceScore(array $relevantExperiences): float
    {
        $score = 0;
        $totalYears = 0;
        
        foreach ($relevantExperiences as $experience) {
            $years = $this->calculateExperienceYears($experience);
            $totalYears += $years;
            $score += $years * 10;
        }
        
        return min(70, $score);
    }

    /**
     * @param array<string, mixed> $experience
     * @param array<string> $jobKeywords
     */
    private function isExperienceRelevant(array $experience, array $jobKeywords): bool
    {
        $experienceKeywords = $this->extractKeywords(
            $experience['title'] . ' ' . 
            $experience['description'] . ' ' . 
            $experience['company']
        );
        
        return count(array_intersect($jobKeywords, $experienceKeywords)) > 0;
    }

    /**
     * @param array<string, mixed> $experiences
     * @return array<string, mixed>
     */
    private function filterRecentExperiences(array $experiences): array
    {
        $now = new \DateTime();
        return array_filter($experiences, function($experience) use ($now) {
            $endDate = $experience['endDate'] ?? $now;
            $interval = $endDate->diff($now);
            return $interval->y <= 5;
        });
    }

    /**
     * @param array<string, mixed> $experience
     */
    private function calculateSingleExperienceBonus(array $experience, JobOffer $jobOffer): float
    {
        $bonus = 0;
        $experienceKeywords = $this->extractKeywords(
            $experience['title'] . ' ' . $experience['description']
        );
        $jobKeywords = $this->extractKeywords(
            $jobOffer->getTitle() . ' ' . $jobOffer->getDescription()
        );
        
        $matchingKeywords = count(array_intersect($experienceKeywords, $jobKeywords));
        $bonus += $matchingKeywords * 2;
        
        if ($this->isSimilarSkill($experience['title'], $jobOffer->getTitle())) {
            $bonus += 10;
        }
        
        return $bonus;
    }

    /**
     * @param array<string, mixed> $experience
     * @throws RuntimeException Si les dates sont invalides
     */
    private function calculateExperienceYears(array $experience): float
    {
        try {
            $startDate = $experience['startDate'] instanceof DateTime ? 
                $experience['startDate'] : 
                new DateTime($experience['startDate'] ?? 'now');
                
            $endDate = $experience['endDate'] instanceof DateTime ? 
                $experience['endDate'] : 
                new DateTime($experience['endDate'] ?? 'now');

            if ($startDate > $endDate) {
                throw new RuntimeException('La date de début ne peut pas être postérieure à la date de fin');
            }

            $interval = $startDate->diff($endDate);
            return $interval->y + ($interval->m / 12);
        } catch (\Exception $e) {
            throw new RuntimeException('Erreur lors du calcul de la durée d\'expérience: ' . $e->getMessage());
        }
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
        } elseif (!empty($userPreferredLocation) && stripos($jobLocation, $userPreferredLocation) !== false) {
            $score = 5;
            $details[] = 'Localisation idéale: ' . $jobLocation;
        } else {
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
     * @throws RuntimeException
     */
    private function ensureApplicant($user): Applicant
    {
        if (!$user instanceof User) {
            throw new RuntimeException('Invalid user provided');
        }
        $applicant = $user->getApplicant();
        if (!$applicant instanceof Applicant) {
            throw new RuntimeException('User is not an applicant');
        }
        return $applicant;
    }

    /**
     * @return array<array{jobOffer: JobOffer, score: int, reasons: array}>
     * @throws RuntimeException Si l'utilisateur n'est pas un candidat valide
     */
    public function findBestJobOffersForCandidate($user, int $limit = 5): array
    {
        if ($limit < 1) {
            throw new RuntimeException('La limite doit être supérieure à 0');
        }

        $applicant = $this->ensureApplicant($user);
        $activeJobOffers = $this->jobOfferRepository->findBy(['isActive' => true]);

        if (empty($activeJobOffers)) {
            return [];
        }

        $scoredOffers = [];
        foreach ($activeJobOffers as $jobOffer) {
            try {
                $compatibilityScore = $this->calculateCompatibilityScore($applicant, $jobOffer);
                $scoredOffers[] = [
                    'jobOffer' => $jobOffer,
                    'score' => $compatibilityScore['score'],
                    'reasons' => $compatibilityScore['reasons']
                ];
            } catch (RuntimeException $e) {
                // Log l'erreur mais continue avec les autres offres
                continue;
            }
        }

        usort($scoredOffers, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scoredOffers, 0, $limit);
    }

    /**
     * @return array<array{applicant: Applicant, score: int}>
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
     * @throws RuntimeException
     */
    public function getApplicantById(int $applicantId): Applicant
    {
        $applicant = $this->entityManager->getRepository(Applicant::class)->find($applicantId);
        if (!$applicant instanceof Applicant) {
            throw new RuntimeException(sprintf('Applicant with ID %d not found', $applicantId));
        }
        return $applicant;
    }

    /**
     * Calcule le score bonus basé sur l'expérience professionnelle récente
     * 
     * @param array<string, mixed> $relevantExperiences
     */
    private function calculateExperienceBonusScore(array $relevantExperiences, JobOffer $jobOffer): float
    {
        $score = 0;
        $recentExperiences = $this->filterRecentExperiences($relevantExperiences);
        
        foreach ($recentExperiences as $experience) {
            $score += $this->calculateSingleExperienceBonus($experience, $jobOffer);
        }
        
        return min(30, $score);
    }

    /**
     * Extrait les mots-clés d'un texte
     * 
     * @return array<string>
     */
    private function extractKeywords(string $text): array
    {
        // Convertir en minuscules et supprimer la ponctuation
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        // Diviser en mots
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Filtrer les mots courts et les mots vides
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'et', 'ou', 'de', 'du', 'en', 'dans'];
        $words = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });

        return array_values(array_unique($words));
    }

    /**
     * Vérifie si une expérience est récente (moins de 5 ans)
     */
    private function isRecentExperience(array $experience): bool
    {
        $now = new DateTime();
        $endDate = $experience['endDate'] ?? $now;
        $interval = $endDate->diff($now);
        return $interval->y <= 5;
    }

    /**
     * Normalise un score entre 0 et 100
     */
    private function normalizeScore(float $score, float $maxScore): int
    {
        if ($maxScore <= 0) {
            return 0;
        }
        return (int)round(($score / $maxScore) * 100);
    }

    /**
     * @return array<string>
     */
    private function getDefaultSoftSkills(): array
    {
        return [
            'communication',
            'travail d\'équipe',
            'adaptabilité',
            'résolution de problèmes',
            'organisation'
        ];
    }
}
