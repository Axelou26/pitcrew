<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\PostLike;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;

trait PostReactionsTrait
{
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(PostLike $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setPost($this);
            $this->likesCounter++;
        }

        return $this;
    }

    public function removeLike(PostLike $like): static
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getPost() === $this) {
                $like->setPost(null);
            }
            $this->likesCounter = max(0, $this->likesCounter - 1);
        }

        return $this;
    }

    public function getLikesCount(): int
    {
        return $this->likesCounter;
    }

    public function updateLikesCounter(): void
    {
        $this->likesCounter = $this->likes->count();
    }

    public function isLikedByUser(User $user): bool
    {
        foreach ($this->likes as $like) {
            if ($like->getUser() === $user) {
                return true;
            }
        }

        return false;
    }

    public function getUserReaction(User $user): ?PostLike
    {
        foreach ($this->likes as $like) {
            if ($like->getUser() === $user) {
                return $like;
            }
        }

        return null;
    }
}
