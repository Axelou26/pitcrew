<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;

class PostContentManager
{
    /**
     * Extrait les hashtags du contenu
     */
    public function extractHashtags(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        try {
            preg_match_all('/#([a-zA-Z0-9_]+)/', $content, $matches);
            return array_unique($matches[1] ?? []);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Extrait les mentions (@username) du contenu
     */
    public function extractMentions(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        try {
            preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
            return array_unique($matches[1] ?? []);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
