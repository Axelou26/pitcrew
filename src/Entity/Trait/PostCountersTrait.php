<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait PostCountersTrait
{
    #[ORM\Column(options: ["default" => 0])]
    private int $likesCounter = 0;

    #[ORM\Column(options: ["default" => 0])]
    private int $commentsCounter = 0;

    #[ORM\Column(options: ["default" => 0])]
    private int $sharesCounter = 0;

    public function getLikesCounter(): int
    {
        return $this->likesCounter;
    }

    public function setLikesCounter(int $likesCounter): static
    {
        $this->likesCounter = $likesCounter;
        return $this;
    }

    public function getCommentsCounter(): int
    {
        return $this->commentsCounter;
    }

    public function setCommentsCounter(int $commentsCounter): static
    {
        $this->commentsCounter = $commentsCounter;
        return $this;
    }

    public function getSharesCounter(): int
    {
        return $this->sharesCounter;
    }

    public function setSharesCounter(int $sharesCounter): static
    {
        $this->sharesCounter = $sharesCounter;
        return $this;
    }

    public function updateAllCounters(): void
    {
        $this->updateLikesCounter();
        $this->updateCommentsCounter();
        $this->updateSharesCounter();
        $this->updateReactionCounts();
    }
} 