<?php

namespace App\Service;

use App\Entity\Post;

class PostCounterManager
{
    /**
     * Met à jour tous les compteurs d'un post
     */
    public function updateAllCounters(Post $post): void
    {
        $this->updateLikesCounter($post);
        $this->updateCommentsCounter($post);
        $this->updateSharesCounter($post);
    }

    /**
     * Met à jour le compteur de likes
     */
    public function updateLikesCounter(Post $post): void
    {
        $post->setLikesCounter($post->getLikes()->count());
    }

    /**
     * Met à jour le compteur de commentaires
     */
    public function updateCommentsCounter(Post $post): void
    {
        $post->setCommentsCounter($post->getComments()->count());
    }

    /**
     * Met à jour le compteur de partages
     */
    public function updateSharesCounter(Post $post): void
    {
        $post->setSharesCounter($post->getShares()->count());
    }
}
