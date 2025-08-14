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
        preg_match_all('/#(\w+)/', $content, $matches);

        return $matches[1] ?? [];
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

        $summary   = substr($content, 0, $maxLength);
        $lastSpace = strrpos($summary, ' ');

        if ($lastSpace !== false) {
            $summary = substr($summary, 0, $lastSpace);
        }

        return $summary . '...';
    }

    /**
     * Enrichit le contenu avec des liens.
     */
    public function enrichContent(string $content): string
    {
        // Convertir les hashtags en liens
        $content = preg_replace(
            '/#([a-zA-ZÀ-ÿ0-9_-]+)/',
            '<a href="/hashtag/$1" class="hashtag">#$1</a>',
            $content
        ) ?? $content;

        // Convertir les mentions en liens
        $content = preg_replace(
            '/@([a-zA-Z0-9_]+)/',
            '<a href="/profile/$1" class="mention">@$1</a>',
            $content
        ) ?? $content;

        // Convertir les URLs en liens cliquables
        $content = preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" target="_blank" rel="noopener">$1</a>',
            $content
        ) ?? $content;

        return $content;
    }

    public function validate(?string $content): void
    {
        if (!$content || trim($content) === '') {
            throw new InvalidArgumentException('Le contenu du post ne peut pas être vide');
        }
    }

    /**
     * Traite le contenu et extrait les hashtags et mentions.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    public function process(string $content): array
    {
        if (trim($content) === '') {
            return [[], []];
        }

        // Extraire les hashtags
        preg_match_all('/#([a-zA-ZÀ-ÿ0-9_-]+)/', $content, $hashtagMatches);
        $hashtags = array_unique($hashtagMatches[1] ?? []);

        // Extraire les mentions
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $mentionMatches);
        $mentions = array_unique($mentionMatches[1] ?? []);

        return [$hashtags, $mentions];
    }

    public function processContent(?string $content): string
    {
        if ($content === null) {
            return '';
        }

        $processedContent = $content;

        // Process mentions
        $processedContent = $this->processMentions($processedContent);

        // Process hashtags
        $processedContent = $this->processHashtags($processedContent);

        // Process URLs
        $processedContent = $this->processUrls($processedContent);

        return $processedContent;
    }

    private function processMentions(string $content): string
    {
        // Expression régulière pour trouver les mentions (@username)
        $pattern = '/@([a-zA-Z0-9_]+)/';
        preg_match_all($pattern, $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $username) {
                // Préparer le tag et le lien
                $mentionTag = '@' . $username;

                // Créer le lien de mention
                $linkTemplate = '<a href="/profile/%s" class="mention">%s</a>';
                $mentionLink  = sprintf($linkTemplate, $username, $mentionTag);

                // Remplacer dans le contenu
                $content = str_replace($mentionTag, $mentionLink, $content);
            }
        }

        return $content;
    }

    private function processHashtags(string $content): string
    {
        // Expression régulière pour trouver les hashtags (#tag)
        $pattern = '/#([a-zA-Z0-9_]+)/';
        preg_match_all($pattern, $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $hashtag) {
                // Préparer le tag et le lien
                $hashtagTag = '#' . $hashtag;

                // Créer le lien de hashtag
                $linkTemplate = '<a href="/hashtag/%s" class="hashtag">%s</a>';
                $hashtagLink  = sprintf($linkTemplate, $hashtag, $hashtagTag);

                // Remplacer dans le contenu
                $content = str_replace($hashtagTag, $hashtagLink, $content);
            }
        }

        return $content;
    }

    private function processUrls(string $content): string
    {
        // Expression régulière pour trouver les URLs
        $pattern = '/(https?:\/\/[^\s]+)/';
        preg_match_all($pattern, $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                // Créer le lien URL avec attributs de sécurité
                $linkTemplate = '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>';
                $urlLink      = \sprintf($linkTemplate, $url, $url);

                // Remplacer dans le contenu
                $content = str_replace($url, $urlLink, $content);
            }
        }

        return $content;
    }
}
