<?php

declare(strict_types=1);

namespace App\Service\Post;

class PostContentProcessor
{
    /**
     * @return array<string>
     */
    public function extractHashtags(string $content): array
    {
        preg_match_all('/#(\w+)/', $content, $matches);
        return $matches[1];
    }

    /**
     * @return array<string>
     */
    public function extractMentions(string $content): array
    {
        preg_match_all('/@(\w+)/', $content, $matches);
        return $matches[1];
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
            '/#(\w+)/',
            '<a href="/hashtag/$1" class="hashtag">#$1</a>',
            $content
        );

        $content = preg_replace(
            '/@(\w+)/',
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
}
