<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\PostShare;
use Doctrine\Common\Collections\Collection;

trait PostSharesTrait
{
    public function getShares(): Collection
    {
        return $this->shares;
    }

    public function addShare(PostShare $share): static
    {
        if (!$this->shares->contains($share)) {
            $this->shares->add($share);
            $share->setPost($this);
            $this->sharesCounter++;
        }
        return $this;
    }

    public function removeShare(PostShare $share): static
    {
        if ($this->shares->removeElement($share)) {
            if ($share->getPost() === $this) {
                $share->setPost(null);
            }
            $this->sharesCounter = max(0, $this->sharesCounter - 1);
        }
        return $this;
    }

    public function getSharesCount(): int
    {
        return $this->sharesCounter;
    }

    public function updateSharesCounter(): void
    {
        $this->sharesCounter = $this->shares->count();
    }
}
