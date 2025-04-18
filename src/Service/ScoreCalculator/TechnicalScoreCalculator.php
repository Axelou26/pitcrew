<?php

namespace App\Service\ScoreCalculator;

use App\Entity\Applicant;
use App\Entity\JobOffer;

class TechnicalScoreCalculator extends BaseScoreCalculator implements ScoreCalculatorInterface
{
    private const WEIGHT = 0.4;
    private const MAX_SCORE = 5;

    public function calculate(Applicant $applicant, JobOffer $jobOffer): array
    {
        $score = 0;
        $maxScore = 0;
        $matches = [];

        $requiredSkills = $jobOffer->getRequiredSkills() ?? [];
        $applicantSkills = $applicant->getTechnicalSkills() ?? [];

        foreach ($requiredSkills as $skill) {
            $maxScore++;
            if (in_array($skill, $applicantSkills, true)) {
                $score++;
                $matches[] = $skill;
            }
        }

        $maxScore = $maxScore ?: 1; // Éviter la division par zéro

        return [
            'category' => 'Compétences techniques',
            'score' => $this->calculateWeightedScore($score, $maxScore, self::WEIGHT),
            'maxScore' => $this->calculateMaxWeightedScore($maxScore, self::WEIGHT),
            'matches' => $matches,
            'details' => [
                sprintf('%d/%d compétences requises maîtrisées', $score, $maxScore)
            ]
        ];
    }
}
