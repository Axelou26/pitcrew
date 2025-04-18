<?php

declare(strict_types=1);

namespace App\Service\ScoreCalculator;

use App\Entity\Applicant;
use App\Entity\JobOffer;
use DateTime;
use RuntimeException;
use InvalidArgumentException;
use Exception;

class ExperienceScoreCalculator extends BaseScoreCalculator implements ScoreCalculatorInterface
{
    private const WEIGHT = 0.3;

    public function calculate(Applicant $applicant, JobOffer $jobOffer): array
    {
        $score = 0;
        $maxScore = 3; // Score maximum pour l'expérience
        $details = [];

        $workExperience = $applicant->getWorkExperience() ?? [];
        $requiredExperience = $jobOffer->getRequiredExperience() ?? 0;

        // Calculer le nombre total d'années d'expérience
        $totalYears = 0;
        foreach ($workExperience as $experience) {
            $startYear = (int) $experience['startDate'];
            $endYear = $experience['endDate'] === 'present' ? date('Y') : (int) $experience['endDate'];
            $totalYears += $endYear - $startYear;
        }

        // Attribution du score basé sur l'expérience
        if ($totalYears >= $requiredExperience) {
            $score = $maxScore;
            $details[] = sprintf('Expérience suffisante (%d années vs %d requises)', $totalYears, $requiredExperience);
            return [
                'category' => 'Expérience professionnelle',
                'score' => $score,
                'maxScore' => $maxScore,
                'details' => $details
            ];
        }

        $score = ($totalYears / $requiredExperience) * $maxScore;
        $details[] = sprintf('Expérience partielle (%d années vs %d requises)', $totalYears, $requiredExperience);

        return [
            'category' => 'Expérience professionnelle',
            'score' => $score,
            'maxScore' => $maxScore,
            'details' => $details
        ];
    }

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

    private function calculateBaseExperienceScore(array $relevantExperiences): float
    {
        $score = 0;
        foreach ($relevantExperiences as $experience) {
            $years = $this->calculateExperienceYears($experience);
            $score += $years * 10;
        }

        return min(70, $score);
    }

    private function calculateExperienceBonusScore(array $relevantExperiences, JobOffer $jobOffer): float
    {
        $score = 0;
        $recentExperiences = $this->filterRecentExperiences($relevantExperiences);

        foreach ($recentExperiences as $experience) {
            $score += $this->calculateSingleExperienceBonus($experience, $jobOffer);
        }

        return min(30, $score);
    }

    private function isExperienceRelevant(array $experience, array $jobKeywords): bool
    {
        $experienceKeywords = $this->extractKeywords(
            $experience['title'] . ' ' .
            $experience['description'] . ' ' .
            $experience['company']
        );

        return count(array_intersect($jobKeywords, $experienceKeywords)) > 0;
    }

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

        if ($this->isSimilarTitle($experience['title'], $jobOffer->getTitle())) {
            $bonus += 10;
        }

        return $bonus;
    }

    private function filterRecentExperiences(array $experiences): array
    {
        $now = new DateTime();
        return array_filter(
            $experiences,
            function ($experience) use ($now) {
                $endDate = $experience['endDate'] ?? $now;
                $interval = $endDate->diff($now);
                return $interval->y <= 5;
            }
        );
    }

    private function calculateExperienceYears(array $experience): float
    {
        try {
            $startDate = $experience['startDate'] instanceof DateTime
                ? $experience['startDate']
                : new DateTime($experience['startDate'] ?? 'now');

            $endDate = $experience['endDate'] instanceof DateTime
                ? $experience['endDate']
                : new DateTime($experience['endDate'] ?? 'now');

            if ($startDate > $endDate) {
                throw new RuntimeException('La date de début ne peut pas être postérieure à la date de fin');
            }

            $interval = $startDate->diff($endDate);
            return $interval->y + ($interval->m / 12);
        } catch (\Exception $e) {
            throw new RuntimeException('Erreur lors du calcul de la durée d\'expérience: ' . $e->getMessage());
        }
    }

    private function isSimilarTitle(string $title1, string $title2): bool
    {
        return strtolower(trim($title1)) === strtolower(trim($title2));
    }

    private function extractKeywords(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'et', 'ou', 'de', 'du', 'en', 'dans'];
        $words = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });

        return array_values(array_unique($words));
    }
}
