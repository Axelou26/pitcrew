<?php

namespace App\Repository;

use App\Entity\RecruiterSubscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecruiterSubscription>
 *
 * @method RecruiterSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method RecruiterSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method RecruiterSubscription[]    findAll()
 * @method RecruiterSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecruiterSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecruiterSubscription::class);
    }

    /**
     * Trouve l'abonnement actif d'un recruteur
     */
    public function findActiveSubscription(User $recruiter): ?RecruiterSubscription
    {
        return $this->createQueryBuilder('rs')
            ->andWhere('rs.recruiter = :recruiter')
            ->andWhere('rs.isActive = :active')
            ->andWhere('rs.endDate > :now')
            ->setParameter('recruiter', $recruiter)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('rs.endDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les abonnements qui expirent bientôt (dans les 7 jours)
     */
    public function findExpiringSubscriptions(): array
    {
        $now = new \DateTime();
        $sevenDaysLater = (new \DateTime())->modify('+7 days');

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
     * Trouve tous les abonnements expirés qui sont encore marqués comme actifs
     */
    public function findExpiredSubscriptions(): array
    {
        return $this->createQueryBuilder('rs')
            ->andWhere('rs.isActive = :active')
            ->andWhere('rs.endDate < :now')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('rs.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un recruteur a un abonnement actif d'un certain type
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
            ->setParameter('now', new \DateTime())
            ->setParameter('name', $subscriptionName)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
