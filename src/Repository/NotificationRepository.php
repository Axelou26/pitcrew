<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method null|Notification find($id, $lockMode = null, $lockVersion = null)
 * @method null|Notification findOneBy(array<string, mixed> $criteria, array<string, string> $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class NotificationRepository extends ServiceEntityRepository
{
    private const CACHE_KEY_UNREAD_COUNT = 'notification_unread_count_%d';
    private const CACHE_TTL              = 60; // 1 minute au lieu de 30 secondes

    public function __construct(
        ManagerRegistry $registry,
        private CacheInterface $cache
    ) {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return Notification[] Returns an array of unread notifications for a user
     */
    public function findUnreadByUser(User $user, int $limit = 20): array
    {
        $cacheKey = \sprintf('notification_unread_list_%d_%d', $user->getId(), $limit);

        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->enableResultCache(15, $cacheKey) // Cache de 15 secondes
            ->getResult();
    }

    /**
     * @return Notification[] Returns an array of recent notifications for a user
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unread notifications for a user.
     */
    public function countUnreadByUser(User $user): int
    {
        $cacheKey = \sprintf(self::CACHE_KEY_UNREAD_COUNT, $user->getId());

        try {
            // Utiliser une requête DQL directe et optimisée
            $query = $this->getEntityManager()->createQuery(
                'SELECT COUNT(n.id) FROM App\Entity\Notification n
                 WHERE n.user = :user AND n.isRead = :isRead'
            )
                ->setParameter('user', $user)
                ->setParameter('isRead', false);

            // Utiliser le cache de requête avec un TTL court
            $query->enableResultCache(self::CACHE_TTL, $cacheKey);

            // Définir un timeout de requête court
            $query->setHint('doctrine.query.timeout', 2); // 2 secondes max

            return (int) $query->getSingleScalarResult();
        } catch (\Exception $e) {
            // En cas d'erreur, retourner 0 pour éviter de bloquer l'UI
            return 0;
        }
    }

    public function invalidateUserCache(User $user): void
    {
        // Invalider le cache du compteur
        $countCacheKey = \sprintf(self::CACHE_KEY_UNREAD_COUNT, $user->getId());
        $this->getEntityManager()->getConfiguration()
            ->getResultCache()
            ->delete($countCacheKey);

        // Invalider le cache de la liste (pour différentes limites)
        for ($limit = 10; $limit <= 50; $limit += 10) {
            $listCacheKey = \sprintf('notification_unread_list_%d_%d', $user->getId(), $limit);
            $this->getEntityManager()->getConfiguration()
                ->getResultCache()
                ->delete($listCacheKey);
        }
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->where('n.user = :user')
            ->andWhere('n.isRead = :oldIsRead')
            ->setParameter('isRead', true)
            ->setParameter('user', $user)
            ->setParameter('oldIsRead', false)
            ->getQuery()
            ->execute();

        // Invalider le cache
        $this->cache->delete('notifications_unread_' . $user->getId());
        $this->cache->delete('notifications_count_' . $user->getId());
    }

    public function deleteOldNotifications(int $daysOld = 30): void
    {
        $date = new DateTime('-' . $daysOld . ' days');

        $this->createQueryBuilder('n')
            ->delete()
            ->where('n.createdAt < :date')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('date', $date)
            ->setParameter('isRead', true)
            ->getQuery()
            ->execute();

        // Invalider le cache si disponible
        if ($this->cache !== null) {
            // Note: clear() n'est pas disponible sur CacheInterface, on utilise delete() pour les clés spécifiques
        }
    }

    public function clearUserNotificationsCache(int $userId): void
    {
        if ($this->cache !== null) {
            $this->cache->delete('user_notifications_' . $userId);
        }
    }

    public function clearAllNotificationsCache(): void
    {
        if ($this->cache !== null) {
            $this->cache->delete('all_notifications');
        }
    }
}
