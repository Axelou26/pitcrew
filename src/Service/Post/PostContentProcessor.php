<?php

declare(strict_types=1);

namespace App\Service\Post;

use InvalidArgumentException;

class PostContentProcessor
{
    /**
     * @return array<string>
     */
    public function extractHashtags(string $content): array
    {
        preg_match_all('/#([a-zA-ZÀ-ÿ0-9_-]+)/', $content, $matches);
        return $matches[1];
    }

    /**
     * @return array<string>
     */
    public function extractMentions(string $content): array
    {
        preg_match_all('/@([a-zA-ZÀ-ÿ]+(?:\s+[a-zA-ZÀ-ÿ]+)*)/', $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    public function containsInappropriateContent(string $content): bool
    {
        $inappropriateWords = ['spam', 'offensive', 'inappropriate'];

        $content = strtolower($content);
        foreach ($inappropriateWords as $word) {
            if (str_contains($content, $word)) {
                return true;
            }
        }

        return false;
    }

    public function generateSummary(string $content, int $maxLength = 150): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        $summary = substr($content, 0, $maxLength);
        $lastSpace = strrpos($summary, ' ');

        if ($lastSpace !== false) {
            $summary = substr($summary, 0, $lastSpace);
        }

        return $summary . '...';
    }

    public function enrichContent(string $content): string
    {
        $content = preg_replace(
            '/#([a-zA-ZÀ-ÿ0-9_-]+)/',
            '<a href="/hashtag/$1" class="hashtag">#$1</a>',
            $content
        );

        $content = preg_replace(
            '/@([a-zA-ZÀ-ÿ]+(?:\s+[a-zA-ZÀ-ÿ]+)*)/',
            '<a href="/profile/$1" class="mention">@$1</a>',
            $content
        );

        return $content;
    }

    public function validate(?string $content): void
    {
        if (!$content || trim($content) === '') {
            throw new InvalidArgumentException('Le contenu du post ne peut pas être vide');
        }
    }

    /**
     * Traite le contenu d'un post pour en extraire les hashtags et les mentions
     * @return array{content: string, hashtags: array, mentions: array}
     */
    public function process(string $content): array
    {
        // Valider le contenu
        $this->validate($content);

        // Extraire les hashtags et les mentions
        $hashtags = $this->extractHashtags($content);
        $mentions = $this->extractMentions($content);

        // Enrichir le contenu (optionnel, à adapter selon les besoins)
        $processedContent = $content;

        return [
            'content' => $processedContent,
            'hashtags' => $hashtags,
            'mentions' => $mentions
        ];
    }
}
