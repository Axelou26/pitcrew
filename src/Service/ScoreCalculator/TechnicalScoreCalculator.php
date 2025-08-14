<?php

declare(strict_types=1);

namespace App\Service\ScoreCalculator;

use App\Entity\Applicant;
use App\Entity\JobOffer;

class TechnicalScoreCalculator extends BaseScoreCalculator implements ScoreCalculatorInterface
{
    private const WEIGHT    = 0.4;
    private const MAX_SCORE = 5;

    public function calculate(Applicant $applicant, JobOffer $jobOffer): array
    {
        $requiredSkills  = $jobOffer->getRequiredSkills() ?? [];
        $applicantSkills = $applicant->getTechnicalSkills() ?? [];

        if (empty($requiredSkills)) {
            return [
                'category' => 'Compétences techniques',
                'score'    => 0,
                'maxScore' => 0,
                'matches'  => [],
                'details'  => ['Aucune compétence technique requise'],
            ];
        }

        $score   = 0;
        $matches = [];

        foreach ($requiredSkills as $skill) {
            if (\in_array($skill, $applicantSkills, true)) {
                $score++;
                $matches[] = $skill;
            }
        }

        return [
            'category' => 'Compétences techniques',
            'score'    => $score,
            'maxScore' => \count($requiredSkills),
            'matches'  => $matches,
            'details'  => [
                \sprintf('%d/%d compétences techniques maîtrisées', $score, \count($requiredSkills)),
            ],
        ];
    }
}
