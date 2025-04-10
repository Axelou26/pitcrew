<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\PostComment;
use Doctrine\Common\Collections\Collection;

trait PostCommentsTrait
{
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(PostComment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPost($this);
            $this->commentsCounter++;
        }
        return $this;
    }

    public function removeComment(PostComment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
            $this->commentsCounter = max(0, $this->commentsCounter - 1);
        }
        return $this;
    }

    public function getCommentsCount(): int
    {
        return $this->commentsCounter;
    }

    public function updateCommentsCounter(): void
    {
        $this->commentsCounter = $this->comments->count();
        // Ajouter les réponses aux commentaires dans le compteur
        foreach ($this->comments as $comment) {
            $this->commentsCounter += $comment->getReplies()->count();
        }
    }
}
