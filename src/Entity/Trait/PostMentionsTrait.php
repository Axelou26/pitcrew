<?php

namespace App\Entity\Trait;

use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait PostMentionsTrait
{
    #[ORM\Column(type: Types::JSON)]
    private ?array $mentions = [];

    public function getMentions(): ?array
    {
        return $this->mentions;
    }

    public function setMentions(?array $mentions): static
    {
        $this->mentions = $mentions;
        return $this;
    }

    public function addMention(User $user): static
    {
        $userId = $user->getId();
        if (!in_array($userId, $this->mentions)) {
            $this->mentions[] = $userId;
        }
        return $this;
    }

    public function extractMentions(): array
    {
        $mentions = [];
        if (preg_match_all('/@(\w+)/', $this->getContent(), $matches)) {
            foreach ($matches[1] as $username) {
                $mentions[] = $username;
            }
        }
        return array_unique($mentions);
    }
} 