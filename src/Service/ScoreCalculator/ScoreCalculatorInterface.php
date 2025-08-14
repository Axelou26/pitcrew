<?php

declare(strict_types=1);

namespace App\Service\ScoreCalculator;

use App\Entity\Applicant;
use App\Entity\JobOffer;

interface ScoreCalculatorInterface
{
    /**
     * Calcule le score pour un candidat et une offre d'emploi.
     *
     * @return array<string, mixed>
     */
    public function calculate(Applicant $applicant, JobOffer $jobOffer): array;
}
