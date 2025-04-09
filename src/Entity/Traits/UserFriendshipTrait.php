<?php

namespace App\Entity\Traits;

trait UserFriendshipTrait
{
    public bool $isFriend = false;
    public bool $hasPendingRequestFrom = false;
    public bool $hasPendingRequestTo = false;
    public ?int $pendingRequestId = null;
}
