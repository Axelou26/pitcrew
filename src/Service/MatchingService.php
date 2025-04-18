<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Applicant;
use App\Entity\JobOffer;
use App\Repository\UserRepository;
use App\Repository\JobOfferRepository;
use App\Service\ScoreCalculator\TechnicalScoreCalculator;
use App\Service\ScoreCalculator\SoftSkillsCalculator;
use App\Service\ScoreCalculator\ExperienceScoreCalculator;
use App\Service\ScoreCalculator\LocationScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

class MatchingService
{
    private TechnicalScoreCalculator $techScore;
    private SoftSkillsCalculator $softScore;
    private ExperienceScoreCalculator $expScore;
    private LocationScoreCalculator $locScore;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly JobOfferRepository $jobOfferRepository
    ) {
        $this->techScore = new TechnicalScoreCalculator();
        $this->softScore = new SoftSkillsCalculator();
        $this->expScore = new ExperienceScoreCalculator();
        $this->locScore = new LocationScoreCalculator();
    }

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

        // Calculer les scores pour chaque critère
        $scores = [
            $this->techScore->calculate($applicant, $jobOffer),
            $this->expScore->calculate($applicant, $jobOffer),
            $this->softScore->calculate($applicant, $jobOffer),
            $this->locScore->calculate($applicant, $jobOffer)
        ];

        // Agréger les scores
        foreach ($scores as $scoreData) {
            $score += $scoreData['score'];
            $maxScore += $scoreData['maxScore'];
            $reasons[] = [
                'category' => $scoreData['category'],
                'score' => $scoreData['score'],
                'maxScore' => $scoreData['maxScore'],
                'matches' => $scoreData['matches'] ?? [],
                'details' => $scoreData['details'] ?? []
            ];
        }

        return [
            'score' => $this->normalizeScore($score, $maxScore),
            'reasons' => $reasons,
            'applicant' => $applicant->getId(),
            'jobOffer' => $jobOffer->getId()
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
        if (!$user instanceof Applicant) {
            throw new RuntimeException('User is not an applicant');
        }
        return $user;
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
                continue;
            }
        }

        return $this->sortByScore($scoredOffers, $limit);
    }

    /**
     * @return array<array{applicant: Applicant, score: int}>
     */
    public function findBestCandidatesForJobOffer(JobOffer $jobOffer, int $limit = 10): array
    {
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

        return $this->sortByScore($scoredCandidates, $limit);
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

    private function sortByScore(array $items, int $limit): array
    {
        usort($items, fn($first, $second) => $second['score'] <=> $first['score']);
        return array_slice($items, 0, $limit);
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
}
