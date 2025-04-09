<?php

declare(strict_types=1);

namespace App\Service\ScoreCalculator;

use App\Entity\Applicant;
use App\Entity\JobOffer;

class LocationScoreCalculator extends BaseScoreCalculator
{
    private const WEIGHT = 0.1;
    private const MAX_SCORE = 5;

    public function calculate(Applicant $applicant, JobOffer $jobOffer): array
    {
        $jobIsRemote = $jobOffer->getIsRemote();
        $jobLocation = $jobOffer->getLocation();
        $preferredLocation = $applicant->getCity() ?? '';

        $score = self::MAX_SCORE;
        $details = [];

        if ($jobIsRemote) {
            $details[] = 'Compatibilité parfaite avec le travail à distance';
            return $this->createResult($score, $details);
        }

        if (!empty($preferredLocation) && $this->isLocationMatch($jobLocation, $preferredLocation)) {
            $details[] = sprintf('Localisation idéale: %s', $jobLocation);
            return $this->createResult($score, $details);
        }

        $score = 3;
        $details[] = 'Compatibilité de localisation moyenne';
        
        return $this->createResult($score, $details);
    }

    private function isLocationMatch(string $jobLocation, string $preferredLocation): bool
    {
        return stripos($jobLocation, $preferredLocation) !== false;
    }

    private function createResult(float $score, array $details): array
    {
        return [
            'score' => $this->calculateWeightedScore($score, self::MAX_SCORE, self::WEIGHT),
            'maxScore' => $this->calculateMaxWeightedScore(self::MAX_SCORE, self::WEIGHT),
            'details' => $details,
            'category' => 'Localisation'
        ];
    }
} 