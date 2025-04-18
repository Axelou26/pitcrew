<?php

declare(strict_types=1);

namespace App\Service\ScoreCalculator;

use App\Entity\Applicant;
use App\Entity\JobOffer;

interface ScoreCalculatorInterface
{
    /**
     * Calcule un score de correspondance entre un candidat et une offre d'emploi.
     *
     * @param Applicant $applicant Le candidat à évaluer
     * @param JobOffer $jobOffer L'offre d'emploi à comparer
     * @return array Un tableau contenant :
     *               - category: string - La catégorie du score
     *               - score: float - Le score obtenu
     *               - maxScore: float - Le score maximum possible
     *               - details: array - Les détails du calcul (optionnel)
     *               - matches: array - Les éléments correspondants (optionnel)
     */
    public function calculate(Applicant $applicant, JobOffer $jobOffer): array;
}
