<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\User;
use App\Repository\FriendshipRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private FriendshipRepository $friendshipRepository;

    public function __construct(FriendshipRepository $friendshipRepository)
    {
        $this->friendshipRepository = $friendshipRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pending_requests_count', [$this, 'getPendingRequestsCount']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', [$this, 'jsonDecode']),
        ];
    }

    public function getPendingRequestsCount(UserInterface $user): int
    {
        if (!$user instanceof User) {
            return 0;
        }

        return $this->friendshipRepository->countPendingRequestsReceived($user);
    }

    public function jsonDecode(string $json, bool $assoc = true): mixed
    {
        return json_decode($json, $assoc);
    }
}
