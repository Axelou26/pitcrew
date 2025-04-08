<?php

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 *
 * @method Subscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subscription[]    findAll()
 * @method Subscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Trouve tous les abonnements actifs
     */
    public function findActiveSubscriptions(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve l'abonnement Basic (gratuit)
     */
    public function findBasicSubscription(): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.name = :name')
            ->andWhere('s.isActive = :active')
            ->setParameter('name', 'Basic')
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
