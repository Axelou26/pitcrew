<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\Hashtag;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait PostTaggingTrait
{
    #[ORM\Column(type: 'json')]
    private array $mentions = [];

    public function getHashtags(): Collection
    {
        return $this->hashtags;
    }

    public function addHashtag(Hashtag $hashtag): static
    {
        if (!$this->hashtags->contains($hashtag)) {
            $this->hashtags->add($hashtag);
            $hashtag->getPosts()->add($this);
        }

        return $this;
    }

    public function removeHashtag(Hashtag $hashtag): static
    {
        if ($this->hashtags->removeElement($hashtag)) {
            $hashtag->getPosts()->removeElement($this);
        }

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function extractHashtags(): array
    {
        $content = $this->getContent();
        if ($content === null) {
            return [];
        }

        preg_match_all('/#(\w+)/', $content, $matches);

        return $matches[1] ?? [];
    }

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
        if (!\in_array($userId, $this->mentions ?? [], true)) {
            $this->mentions   = $this->mentions ?? [];
            $this->mentions[] = $userId;
        }

        return $this;
    }

    public function removeMention(User $user): static
    {
        $userId         = $user->getId();
        $this->mentions = array_values(array_filter($this->mentions ?? [], function ($mentionId) use ($userId) {
            return $mentionId !== $userId;
        }));

        return $this;
    }

    public function hasMention(User $user): bool
    {
        return \in_array($user->getId(), $this->mentions ?? [], true);
    }

    /**
     * @return array<int, string>
     */
    public function extractMentions(): array
    {
        $content = $this->getContent();
        if ($content === null) {
            return [];
        }

        preg_match_all('/@(\w+)/', $content, $matches);

        return $matches[1] ?? [];
    }
}
