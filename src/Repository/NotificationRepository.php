<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    private const CACHE_KEY_UNREAD_COUNT = 'notification_unread_count_%d';
    private const CACHE_TTL = 30; // 30 secondes

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return Notification[] Returns an array of unread notifications for a user
     */
    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
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
     * Count unread notifications for a user
     */
    public function countUnreadByUser(User $user): int
    {
        $cacheKey = sprintf(self::CACHE_KEY_UNREAD_COUNT, $user->getId());

        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->enableResultCache(self::CACHE_TTL, $cacheKey)
            ->getSingleScalarResult();
    }

    public function invalidateUserCache(User $user): void
    {
        $cacheKey = sprintf(self::CACHE_KEY_UNREAD_COUNT, $user->getId());
        $this->getEntityManager()->getConfiguration()
            ->getResultCache()
            ->delete($cacheKey);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = :notRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', true)
            ->setParameter('notRead', false)
            ->getQuery()
            ->execute();

        // Invalider le cache après la mise à jour
        $this->invalidateUserCache($user);
    }
}
