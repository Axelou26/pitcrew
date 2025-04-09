<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\PostLike;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;

trait PostReactionsTrait
{
    private function initializeReactionCounts(): array
    {
        return [
            PostLike::REACTION_LIKE => 0,
            PostLike::REACTION_CONGRATS => 0,
            PostLike::REACTION_INTERESTING => 0,
            PostLike::REACTION_SUPPORT => 0,
            PostLike::REACTION_ENCOURAGING => 0
        ];
    }

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
            $this->updateReactionCounts();
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
            $this->updateReactionCounts();
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

    public function getReactionCounts(): ?array
    {
        return $this->reactionCounts;
    }

    public function updateReactionCounts(): void
    {
        $counts = $this->initializeReactionCounts();

        foreach ($this->likes as $like) {
            $type = $like->getReactionType();
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }

        $this->reactionCounts = $counts;
    }

    public function getReactionCount(string $type): int
    {
        return $this->reactionCounts[$type] ?? 0;
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

    public function getUserReactionType(User $user): ?string
    {
        $reaction = $this->getUserReaction($user);
        return $reaction ? $reaction->getReactionType() : null;
    }
} 