<?php

namespace App\Twig;

use App\Repository\FriendshipRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private Security $security,
        private FriendshipRepository $friendshipRepository,
        private CacheInterface $cache
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_likes_count', [$this, 'getLikesCount']),
            new TwigFunction('pending_friend_requests_count', [$this, 'getPendingFriendRequestsCount']),
        ];
    }

    /**
     * Récupère le nombre de likes
     */
    public function getLikesCount(int $likesCount): int
    {
        return $likesCount;
    }

    public function getPendingFriendRequestsCount(): int
    {
        $user = $this->security->getUser();

        if (!$user) {
            return 0;
        }

        // Utiliser le cache pour éviter les requêtes répétées
        $cacheKey = 'pending_friend_requests_' . $user->getId();
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($user) {
            $item->expiresAfter(300); // Cache pour 5 minutes
            return $this->friendshipRepository->countPendingRequestsReceived($user);
        });
    }
}
