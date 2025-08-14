<?php

declare(strict_types=1);

namespace App\Service;

class PostContentManager
{
    private $postContentProcessor;

    public function __construct($postContentProcessor)
    {
        $this->postContentProcessor = $postContentProcessor;
    }

    /**
     * Extrait les hashtags d'un contenu.
     *
     * @return array<int, string>
     */
    public function extractHashtags(string $content): array
    {
        $result = $this->postContentProcessor->process($content);

        return $result[1] ?? [];
    }

    /**
     * Extrait les mentions d'un contenu.
     *
     * @return array<int, string>
     */
    public function extractMentions(string $content): array
    {
        $result = $this->postContentProcessor->process($content);

        return $result[1] ?? [];
    }
}
