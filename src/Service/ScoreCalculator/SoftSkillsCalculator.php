<?php

declare(strict_types=1);

namespace App\Service\ScoreCalculator;

use App\Entity\Applicant;
use App\Entity\JobOffer;

class SoftSkillsCalculator extends BaseScoreCalculator implements ScoreCalculatorInterface
{
    private const WEIGHT              = 0.2;
    private const DEFAULT_SOFT_SKILLS = [
        'communication', 'travail d\'équipe', 'adaptabilité',
        'résolution de problèmes', 'organisation',
    ];

    /**
     * Calcule le score de soft skills pour un candidat.
     *
     * @return array<string, mixed>
     */
    public function calculate(Applicant $applicant, JobOffer $jobOffer): array
    {
        $candidateSkills = $applicant->getSoftSkills() ?? [];
        $jobSkills       = $this->extractSoftSkillsFromJobDescription($jobOffer->getDescription() ?? '');

        if (empty($candidateSkills) || empty($jobSkills)) {
            return $this->createEmptyResult();
        }

        $matchingSkills = $this->findMatchingSoftSkills($candidateSkills, $jobSkills);
        $score          = \count($matchingSkills) * 10;
        $maxScore       = \count($jobSkills) * 10;

        return [
            'category' => 'Soft Skills',
            'score'    => min($score, $maxScore),
            'maxScore' => $maxScore,
            'details'  => $matchingSkills,
            'matches'  => $matchingSkills,
        ];
    }

    /**
     * Trouve les soft skills correspondantes.
     *
     * @param array<int, string> $candidateSkills
     * @param array<int, string> $jobSkills
     *
     * @return array<int, string>
     */
    private function findMatchingSoftSkills(array $candidateSkills, array $jobSkills): array
    {
        $matchingSkills = [];

        foreach ($candidateSkills as $candidateSkill) {
            if ($this->hasMatchingSkill($candidateSkill, $jobSkills)) {
                $matchingSkills[] = $candidateSkill;
            }
        }

        return $matchingSkills;
    }

    /**
     * Vérifie si un candidat a une compétence spécifique.
     *
     * @param array<int, string> $jobSkills
     */
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

    /**
     * Extrait les soft skills d'une description d'emploi.
     *
     * @return array<int, string>
     */
    private function extractSoftSkillsFromJobDescription(string $description): array
    {
        $description = strtolower($description);
        $foundSkills = array_filter(
            $this->getCommonSoftSkills(),
            fn ($skill) => stripos($description, $skill) !== false
        );

        return empty($foundSkills) ? self::DEFAULT_SOFT_SKILLS : array_values($foundSkills);
    }

    /**
     * Retourne la liste des soft skills communes.
     *
     * @return array<int, string>
     */
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
            'autonomie', 'efficacité', 'rigueur', 'méthode',
        ];
    }

    /**
     * Crée un résultat vide.
     *
     * @return array<string, mixed>
     */
    private function createEmptyResult(): array
    {
        return [
            'score'    => 0,
            'maxScore' => 0,
            'matches'  => [],
            'category' => 'Soft skills',
        ];
    }
}
