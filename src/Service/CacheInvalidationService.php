<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use App\Entity\User;
use App\Entity\Friendship;

class CacheInvalidationService
{
    public function __construct(
        private CacheInterface $cache
    ) {
    }

    /**
     * Invalide le cache des demandes d'amitié pour un utilisateur
     */
    public function invalidatePendingFriendRequestsCache(User $user): void
    {
        $cacheKey = 'pending_friend_requests_' . $user->getId();
        $this->cache->delete($cacheKey);
    }

    /**
     * Invalide le cache des statistiques utilisateur
     */
    public function invalidateUserStatsCache(User $user): void
    {
        $cacheKey = 'user_stats_' . $user->getId();
        $this->cache->delete($cacheKey);
    }

    /**
     * Invalide le cache de la page d'accueil
     */
    public function invalidateHomepageCache(?User $user = null): void
    {
        $userId = $user ? $user->getId() : 'anonymous';
        $cacheKey = 'homepage_data_' . $userId;
        $this->cache->delete($cacheKey);
    }

    /**
     * Invalide tous les caches liés à une amitié
     */
    public function invalidateFriendshipCaches(Friendship $friendship): void
    {
        // Invalider le cache pour le demandeur
        if ($friendship->getRequester()) {
            $this->invalidatePendingFriendRequestsCache($friendship->getRequester());
            $this->invalidateUserStatsCache($friendship->getRequester());
            $this->invalidateHomepageCache($friendship->getRequester());
        }

        // Invalider le cache pour le destinataire
        if ($friendship->getAddressee()) {
            $this->invalidatePendingFriendRequestsCache($friendship->getAddressee());
            $this->invalidateUserStatsCache($friendship->getAddressee());
            $this->invalidateHomepageCache($friendship->getAddressee());
        }
    }
} 