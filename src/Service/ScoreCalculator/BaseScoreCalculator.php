<?php

declare(strict_types=1);

namespace App\Service\ScoreCalculator;

abstract class BaseScoreCalculator
{
    protected function normalizeScore(float $score, float $maxScore): int
    {
        if ($maxScore <= 0) {
            return 0;
        }
        return (int)round(($score / $maxScore) * 100);
    }

    protected function calculateWeightedScore(float $score, float $maxScore, float $weight): float
    {
        return $score * $weight;
    }

    protected function calculateMaxWeightedScore(float $maxScore, float $weight): float
    {
        return $maxScore * $weight;
    }
} 