<?php

namespace App\Entity\Trait;

use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait PostReactionCountsTrait
{
    #[ORM\Column(type: Types::JSON)]
    private ?array $reactionCounts = [];

    private function initializeReactionCounts(): array
    {
        return [
            'like' => 0,
            'love' => 0,
            'haha' => 0,
            'wow' => 0,
            'sad' => 0,
            'angry' => 0
        ];
    }

    public function getReactionCounts(): ?array
    {
        return $this->reactionCounts;
    }

    public function updateReactionCounts(): void
    {
        $counts = $this->initializeReactionCounts();
        foreach ($this->getLikes() as $like) {
            $type = $like->getType();
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

    public function getUserReaction(User $user): ?string
    {
        foreach ($this->getLikes() as $like) {
            if ($like->getUser() === $user) {
                return $like->getType();
            }
        }
        return null;
    }

    public function getUserReactionType(User $user): ?string
    {
        return $this->getUserReaction($user);
    }
} 