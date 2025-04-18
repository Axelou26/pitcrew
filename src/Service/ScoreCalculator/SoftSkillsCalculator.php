<?php

declare(strict_types=1);

namespace App\Service\ScoreCalculator;

use App\Entity\Applicant;
use App\Entity\JobOffer;

class SoftSkillsCalculator extends BaseScoreCalculator implements ScoreCalculatorInterface
{
    private const WEIGHT = 0.2;
    private const DEFAULT_SOFT_SKILLS = [
        'communication', 'travail d\'équipe', 'adaptabilité',
        'résolution de problèmes', 'organisation'
    ];

    public function calculate(Applicant $applicant, JobOffer $jobOffer): array
    {
        $score = 0;
        $maxScore = 0;
        $matches = [];

        $requiredSkills = $jobOffer->getSoftSkills() ?? [];
        $applicantSkills = $applicant->getSoftSkills() ?? [];

        foreach ($requiredSkills as $skill) {
            $maxScore++;
            if (in_array($skill, $applicantSkills, true)) {
                $score++;
                $matches[] = $skill;
            }
        }

        return [
            'category' => 'Compétences humaines',
            'score' => $score,
            'maxScore' => $maxScore ?: 1,
            'matches' => $matches,
            'details' => [
                sprintf('%d/%d soft skills requis maîtrisés', $score, $maxScore)
            ]
        ];
    }

    private function findMatchingSoftSkills(array $candidateSkills, array $jobSkills): array
    {
        $matches = [];
        foreach ($candidateSkills as $skill) {
            if ($this->hasMatchingSkill($skill, $jobSkills)) {
                $matches[] = $skill;
            }
        }

        $matchCount = count($matches);
        $maxScore = min(5, count($jobSkills));

        return [
            'score' => min($matchCount, $maxScore),
            'maxScore' => $maxScore,
            'matches' => $matches
        ];
    }

    private function hasMatchingSkill(string $candidateSkill, array $jobSkills): bool
    {
        foreach ($jobSkills as $jobSkill) {
            if ($this->isSimilarSkill($candidateSkill, $jobSkill)) {
                return true;
            }
        }
        return false;
    }

    private function isSimilarSkill(string $skill1, string $skill2): bool
    {
        return strtolower(trim($skill1)) === strtolower(trim($skill2));
    }

    private function extractSoftSkillsFromJobDescription(string $description): array
    {
        $description = strtolower($description);
        $foundSkills = array_filter(
            $this->getCommonSoftSkills(),
            fn($skill) => stripos($description, $skill) !== false
        );

        return empty($foundSkills) ? self::DEFAULT_SOFT_SKILLS : array_values($foundSkills);
    }

    private function getCommonSoftSkills(): array
    {
        return [
            // Communication
            'communication', 'écoute active', 'expression orale', 'présentation',
            'négociation', 'rédaction', 'vulgarisation', 'diplomatie',

            // Travail en équipe
            'travail d\'équipe', 'collaboration', 'coopération', 'esprit d\'équipe',
            'coordination', 'cohésion', 'entraide', 'synergie',

            // Leadership
            'leadership', 'management', 'gestion d\'équipe', 'encadrement',
            'délégation', 'prise de décision', 'coaching', 'mentorat',

            // Adaptation
            'adaptabilité', 'flexibilité', 'résilience', 'gestion du stress',
            'polyvalence', 'réactivité',

            // Organisation
            'organisation', 'planification', 'gestion du temps', 'priorisation',
            'autonomie', 'efficacité', 'rigueur', 'méthode'
        ];
    }

    private function createEmptyResult(): array
    {
        return [
            'score' => 0,
            'maxScore' => 0,
            'matches' => [],
            'category' => 'Soft skills'
        ];
    }
}
