<?php

declare(strict_types=1);

namespace App\Service\ScoreCalculator;

use App\Entity\Applicant;
use App\Entity\JobOffer;

class LocationScoreCalculator extends BaseScoreCalculator implements ScoreCalculatorInterface
{
    private const WEIGHT    = 0.1;
    private const MAX_SCORE = 5;

    /**
     * Calcule le score de localisation pour un candidat.
     *
     * @return array<string, mixed>
     */
    public function calculate(Applicant $applicant, JobOffer $jobOffer): array
    {
        $applicantLocation = $applicant->getLocation();
        $jobLocation       = $jobOffer->getLocation();

        if (empty($applicantLocation) || empty($jobLocation)) {
            return $this->createResult(0, self::MAX_SCORE, ['Localisation non spécifiée']);
        }

        $distance = $this->calculateDistance($applicantLocation, $jobLocation);

        if ($distance <= 10) {
            return $this->createResult(self::MAX_SCORE, self::MAX_SCORE, ['Distance: ' . $distance . ' km']);
        }
        if ($distance <= 50) {
            return $this->createResult(self::MAX_SCORE * 0.7, self::MAX_SCORE, ['Distance: ' . $distance . ' km']);
        }

        return $this->createResult(self::MAX_SCORE * 0.3, self::MAX_SCORE, ['Distance: ' . $distance . ' km']);
    }

    public function calculateScore(Applicant $applicant, JobOffer $jobOffer): float
    {
        $applicantLocation = $applicant->getLocation();
        $jobLocation       = $jobOffer->getLocation();

        if (!$applicantLocation || !$jobLocation) {
            return 0.0;
        }

        $distance = $this->calculateDistance($applicantLocation, $jobLocation);

        // Score basé sur la distance (plus proche = meilleur score)
        if ($distance <= 10) {
            return 100.0;
        }
        if ($distance <= 25) {
            return 80.0;
        }
        if ($distance <= 50) {
            return 60.0;
        }
        if ($distance <= 100) {
            return 40.0;
        }

        return 20.0;
    }

    private function calculateDistance(string $location1, string $location2): float
    {
        // Simulation simple de calcul de distance
        // En production, utiliser un service comme Google Maps API
        $coordinates1 = $this->getCoordinates($location1);
        $coordinates2 = $this->getCoordinates($location2);

        if (!$coordinates1 || !$coordinates2) {
            return 999.0; // Distance très élevée si pas de coordonnées
        }

        return $this->haversineDistance($coordinates1, $coordinates2);
    }

    private function getCoordinates(string $location): ?array
    {
        // Simulation - en production, utiliser un service de géocodage
        $coordinates = [
            'Paris'     => [48.8566, 2.3522],
            'Lyon'      => [45.7578, 4.8320],
            'Marseille' => [43.2965, 5.3698],
            'Toulouse'  => [43.6047, 1.4442],
            'Nantes'    => [47.2184, -1.5536],
        ];

        return $coordinates[$location] ?? null;
    }

    private function haversineDistance(array $coord1, array $coord2): float
    {
        $lat1 = deg2rad($coord1[0]);
        $lon1 = deg2rad($coord1[1]);
        $lat2 = deg2rad($coord2[0]);
        $lon2 = deg2rad($coord2[1]);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $angle         = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $circumference = 2 * atan2(sqrt($angle), sqrt(1 - $angle));

        return 6371 * $circumference; // Distance en km
    }

    private function isLocationMatch(?string $jobLocation, string $candidateLocation): bool
    {
        if ($jobLocation === null) {
            return false;
        }

        $jobLocation       = strtolower(trim($jobLocation));
        $candidateLocation = strtolower(trim($candidateLocation));

        // Correspondance exacte
        if ($jobLocation === $candidateLocation) {
            return true;
        }

        // Correspondance partielle (ville dans la région)
        if (str_contains($jobLocation, $candidateLocation) || str_contains($candidateLocation, $jobLocation)) {
            return true;
        }

        return false;
    }

    /**
     * Crée le résultat du calcul.
     *
     * @param array<int, string> $details
     *
     * @return array<string, mixed>
     */
    private function createResult(float $score, float $maxScore, array $details): array
    {
        return [
            'score'    => $this->calculateWeightedScore($score, $maxScore, self::WEIGHT),
            'maxScore' => $this->calculateMaxWeightedScore($maxScore, self::WEIGHT),
            'details'  => $details,
            'category' => 'Localisation',
        ];
    }
}
