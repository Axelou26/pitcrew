<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\PostLike;

class PostReactionManager
{
    /**
     * Met à jour les compteurs de réactions pour un post
     */
    public function updateReactionCounts(Post $post): void
    {
        $counts = [
            PostLike::REACTION_LIKE => 0,
            PostLike::REACTION_CONGRATS => 0,
            PostLike::REACTION_INTERESTING => 0,
            PostLike::REACTION_SUPPORT => 0,
            PostLike::REACTION_ENCOURAGING => 0
        ];

        foreach ($post->getLikes() as $like) {
            $type = $like->getReactionType();
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }

        $post->setReactionCounts($counts);
    }

    /**
     * Récupère le type de réaction d'un utilisateur pour un post
     */
    public function getUserReaction(Post $post, User $user): ?string
    {
        foreach ($post->getLikes() as $like) {
            if ($like->getUser() === $user) {
                return $like->getReactionType();
            }
        }
        return null;
    }

    /**
     * Retourne le nombre de réactions d'un type spécifique
     */
    public function getReactionCount(Post $post, string $type): int
    {
        $counts = $post->getReactionCounts();
        if ($counts === null) {
            return 0;
        }

        // Vérifier d'abord la clé exacte
        if (isset($counts[$type])) {
            return $counts[$type];
        }

        // Vérifier si c'est 'like' mais stocké comme 'likes'
        if ($type === 'like' && isset($counts['likes'])) {
            return $counts['likes'];
        }

        // Vérifier si c'est 'likes' mais stocké comme 'like'
        if ($type === 'likes' && isset($counts['like'])) {
            return $counts['like'];
        }

        return 0;
    }
} 