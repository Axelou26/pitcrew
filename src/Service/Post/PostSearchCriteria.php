<?php

declare(strict_types=1);

namespace App\Service\Post;

use App\Entity\User;

class PostSearchCriteria
{
    public const TYPE_SEARCH   = 'search';
    public const TYPE_FEED     = 'feed';
    public const TYPE_HASHTAGS = 'hashtags';
    public const TYPE_MENTIONS = 'mentions';

    private string $type;
    private ?string $query                = null;
    private ?User $user                   = null;
    private ?\DateTimeInterface $fromDate = null;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function forSearch(string $query): self
    {
        $criteria        = new self(self::TYPE_SEARCH);
        $criteria->query = $query;

        return $criteria;
    }

    public static function forFeed(User $user): self
    {
        $criteria       = new self(self::TYPE_FEED);
        $criteria->user = $user;

        return $criteria;
    }

    public static function forHashtags(\DateTimeInterface $fromDate): self
    {
        $criteria           = new self(self::TYPE_HASHTAGS);
        $criteria->fromDate = $fromDate;

        return $criteria;
    }

    public static function forMentions(User $user): self
    {
        $criteria       = new self(self::TYPE_MENTIONS);
        $criteria->user = $user;

        return $criteria;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getFromDate(): ?\DateTimeInterface
    {
        return $this->fromDate;
    }
}
