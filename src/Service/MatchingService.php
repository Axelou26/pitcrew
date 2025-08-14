<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Applicant;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Repository\ApplicantRepository;
use App\Repository\JobOfferRepository;
use App\Service\ScoreCalculator\ExperienceScoreCalculator;
use App\Service\ScoreCalculator\LocationScoreCalculator;
use App\Service\ScoreCalculator\ScoreCalculatorInterface;
use App\Service\ScoreCalculator\SoftSkillsCalculator;
use App\Service\ScoreCalculator\TechnicalScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

class MatchingService
{
    private EntityManagerInterface $entityManager;
    private JobOfferRepository $jobOfferRepository;
    private ApplicantRepository $applicantRepository;
    private ScoreCalculatorInterface $experienceCalculator;
    private ScoreCalculatorInterface $locationCalculator;
    private ScoreCalculatorInterface $technicalCalculator;
    private ScoreCalculatorInterface $softSkillsCalculator;

    public function __construct(
        EntityManagerInterface $entityManager,
        JobOfferRepository $jobOfferRepository,
        ApplicantRepository $applicantRepository,
        ExperienceScoreCalculator $experienceCalculator,
        LocationScoreCalculator $locationCalculator,
        TechnicalScoreCalculator $technicalCalculator,
        SoftSkillsCalculator $softSkillsCalculator
    ) {
        $this->entityManager        = $entityManager;
        $this->jobOfferRepository   = $jobOfferRepository;
        $this->applicantRepository  = $applicantRepository;
        $this->experienceCalculator = $experienceCalculator;
        $this->locationCalculator   = $locationCalculator;
        $this->technicalCalculator  = $technicalCalculator;
        $this->softSkillsCalculator = $softSkillsCalculator;
    }

    /**
     * Calcule un score de compatibilité entre un candidat et une offre d'emploi.
     *
     * @param Applicant $applicant
     * @param JobOffer $jobOffer
     *
     * @throws \RuntimeException Si les données requises sont manquantes
     *
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
     */
    public function calculateCompatibilityScore(Applicant $applicant, JobOffer $jobOffer): array
    {
        if (!$applicant->getId() || !$jobOffer->getId()) {
            throw new RuntimeException('L\'applicant et l\'offre d\'emploi doivent avoir des IDs valides');
        }

        $score    = 0;
        $maxScore = 0;
        $reasons  = [];

        // Calculer les scores pour chaque critère
        $scores = [
            $this->technicalCalculator->calculate($applicant, $jobOffer),
            $this->experienceCalculator->calculate($applicant, $jobOffer),
            $this->softSkillsCalculator->calculate($applicant, $jobOffer),
            $this->locationCalculator->calculate($applicant, $jobOffer),
        ];

        // Agréger les scores
        foreach ($scores as $scoreData) {
            $score += $scoreData['score'];
            $maxScore += $scoreData['maxScore'];
            $reasons[] = [
                'category' => $scoreData['category'],
                'score'    => $scoreData['score'],
                'maxScore' => $scoreData['maxScore'],
                'matches'  => $scoreData['matches'] ?? [],
                'details'  => $scoreData['details'] ?? [],
            ];
        }

        return [
            'score'     => $this->normalizeScore($score, $maxScore),
            'reasons'   => $reasons,
            'applicant' => $applicant->getId(),
            'jobOffer'  => $jobOffer->getId(),
        ];
    }

    /**
     * Trouve les meilleures offres d'emploi pour un candidat.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findBestJobOffersForCandidate(User $user, int $limit = 10): array
    {
        if ($limit < 1) {
            throw new RuntimeException('La limite doit être supérieure à 0');
        }

        $applicant       = $this->ensureApplicant($user);
        $activeJobOffers = $this->jobOfferRepository->findBy(['isActive' => true]);

        if (empty($activeJobOffers)) {
            return [];
        }

        $scoredOffers = [];
        foreach ($activeJobOffers as $jobOffer) {
            try {
                $compatibilityScore = $this->calculateCompatibilityScore($applicant, $jobOffer);
                $scoredOffers[]     = [
                    'jobOffer' => $jobOffer,
                    'score'    => $compatibilityScore['score'],
                    'reasons'  => $compatibilityScore['reasons'],
                ];
            } catch (\RuntimeException $e) {
                continue;
            }
        }

        return $this->sortByScore($scoredOffers);
    }

    /**
     * Trouve les meilleurs candidats pour une offre d'emploi.
     *
     * @return array<int, array{applicant: Applicant, score: int}>
     */
    public function findBestCandidatesForJobOffer(JobOffer $jobOffer, int $limit = 10): array
    {
        $candidates       = $this->applicantRepository->findMatchingCandidates($jobOffer, $limit * 2);
        $scoredCandidates = [];

        foreach ($candidates as $candidate) {
            $score              = $this->calculateMatchScore($candidate, $jobOffer);
            $scoredCandidates[] = [
                'applicant' => $candidate,
                'score'     => $score,
            ];
        }

        // Trier par score décroissant
        usort($scoredCandidates, function (array $first, array $second) {
            return $second['score'] <=> $first['score'];
        });

        return \array_slice($scoredCandidates, 0, $limit);
    }

    /**
     * Trouve les meilleurs candidats pour une offre d'emploi.
     *
     * @return array<int, Applicant>
     */
    public function findMatchingCandidates(JobOffer $jobOffer, int $limit = 10): array
    {
        $candidates = $this->applicantRepository->findMatchingCandidates($jobOffer, $limit);

        // Trier les candidats par score de correspondance
        usort($candidates, function (Applicant $first, Applicant $second) use ($jobOffer) {
            $scoreFirst  = $this->calculateMatchScore($first, $jobOffer);
            $scoreSecond = $this->calculateMatchScore($second, $jobOffer);

            return $scoreSecond <=> $scoreFirst;
        });

        return \array_slice($candidates, 0, $limit);
    }

    /**
     * @throws \RuntimeException
     */
    public function getApplicantById(int $applicantId): Applicant
    {
        $applicant = $this->entityManager->getRepository(Applicant::class)->find($applicantId);
        if (!$applicant instanceof Applicant) {
            throw new RuntimeException(\sprintf('Applicant with ID %d not found', $applicantId));
        }

        return $applicant;
    }

    public function calculateMatchScore(Applicant $applicant, JobOffer $jobOffer): int
    {
        $score = 0;

        // Score basé sur les compétences techniques
        $technicalSkills = $applicant->getTechnicalSkills();
        $requiredSkills  = $jobOffer->getRequiredSkills();

        foreach ($requiredSkills as $requiredSkill) {
            if (\in_array($requiredSkill, $technicalSkills, true)) {
                $score += 10;
            }
        }

        // Score basé sur l'expérience
        $experience = $applicant->getWorkExperience();
        if (!empty($experience)) {
            $score += 5;
        }

        return $score;
    }

    /**
     * S'assure qu'un utilisateur est un candidat.
     */
    private function ensureApplicant(User $user): Applicant
    {
        if (!$user instanceof User) {
            throw new RuntimeException('Invalid user provided');
        }
        if (!$user instanceof Applicant) {
            throw new RuntimeException('User is not an applicant');
        }

        return $user;
    }

    /**
     * Trie les éléments par score.
     *
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function sortByScore(array $items): array
    {
        usort($items, fn ($first, $second) => $second['score'] <=> $first['score']);

        return \array_slice($items, 0, 5); // Keep original limit logic
    }

    /**
     * Normalise un score entre 0 et 100.
     */
    private function normalizeScore(float $score, float $maxScore): int
    {
        if ($maxScore <= 0) {
            return 0;
        }

        return (int) round(($score / $maxScore) * 100);
    }
}
