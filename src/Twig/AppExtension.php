<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_reaction_count', [$this, 'getReactionCount']),
        ];
    }

    /**
     * Récupère le compte de réactions quelle que soit la clé (like ou likes)
     */
    public function getReactionCount(array $reactionCounts, string $type): int
    {
        // Vérifier d'abord la clé correcte (sans 's')
        if (isset($reactionCounts[$type])) {
            return $reactionCounts[$type];
        }
        
        // Ensuite, essayer avec un 's' à la fin
        if ($type === 'like' && isset($reactionCounts['likes'])) {
            return $reactionCounts['likes'];
        }
        
        // Par défaut retourner 0
        return 0;
    }
} 