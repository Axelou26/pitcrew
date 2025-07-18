<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;

class PostReactionManager
{
    /**
     * Met à jour les compteurs de likes pour un post
     */
    public function updateReactionCounts(Post $post): void
    {
        $post->updateLikesCounter();
    }

    /**
     * Récupère le like d'un utilisateur pour un post
     */
    public function getUserReaction(Post $post, User $user): ?bool
    {
        return $post->isLikedByUser($user);
    }

    /**
     * Retourne le nombre de likes
     */
    public function getReactionCount(Post $post): int
    {
        return $post->getLikesCount();
    }
}
