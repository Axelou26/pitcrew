<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RecruiterSubscription;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecruiterSubscription>
 *
 * @method null|RecruiterSubscription find($id, $lockMode = null, $lockVersion = null)
 * @method null|RecruiterSubscription findOneBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null
 * )
 * @method RecruiterSubscription[]    findAll()
 * @method RecruiterSubscription[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class RecruiterSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecruiterSubscription::class);
    }

    /**
     * Trouve l'abonnement actif d'un recruteur.
     */
    public function findActiveSubscription(User $recruiter): ?RecruiterSubscription
    {
        return $this->createQueryBuilder('rs')
            ->andWhere('rs.recruiter = :recruiter')
            ->andWhere('rs.isActive = :active')
            ->andWhere('rs.endDate > :now')
            ->setParameter('recruiter', $recruiter)
            ->setParameter('active', true)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('rs.endDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les abonnements qui expirent bientôt.
     *
     * @return RecruiterSubscription[]
     */
    public function findExpiringSubscriptions(): array
    {
        $now            = new DateTimeImmutable();
        $sevenDaysLater = (new DateTimeImmutable())->modify('+7 days');

        return $this->createQueryBuilder('rs')
            ->andWhere('rs.isActive = :active')
            ->andWhere('rs.endDate BETWEEN :now AND :sevenDaysLater')
            ->andWhere('rs.cancelled = :cancelled')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->setParameter('sevenDaysLater', $sevenDaysLater)
            ->setParameter('cancelled', false)
            ->orderBy('rs.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les abonnements expirés.
     *
     * @return RecruiterSubscription[]
     */
    public function findExpiredSubscriptions(): array
    {
        return $this->createQueryBuilder('rs')
            ->andWhere('rs.isActive = :active')
            ->andWhere('rs.endDate < :now')
            ->setParameter('active', true)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('rs.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un recruteur a un abonnement actif d'un certain type.
     */
    public function hasActiveSubscriptionType(User $recruiter, string $subscriptionName): bool
    {
        $result = $this->createQueryBuilder('rs')
            ->select('COUNT(rs.id)')
            ->join('rs.subscription', 's')
            ->andWhere('rs.recruiter = :recruiter')
            ->andWhere('rs.isActive = :active')
            ->andWhere('rs.endDate > :now')
            ->andWhere('s.name = :name')
            ->setParameter('recruiter', $recruiter)
            ->setParameter('active', true)
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('name', $subscriptionName)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Trouve les abonnements d'un recruteur.
     *
     * @return RecruiterSubscription[]
     */
    public function findByUser(User $recruiter): array
    {
        return $this->createQueryBuilder('rs')
            ->andWhere('rs.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('rs.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
