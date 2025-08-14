<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 *
 * @method null|Subscription find($id, $lockMode = null, $lockVersion = null)
 * @method null|Subscription findOneBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null
 * )
 * @method Subscription[]    findAll()
 * @method Subscription[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Trouve tous les abonnements actifs.
     *
     * @return Subscription[]
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
     * Trouve l'abonnement Basic (gratuit).
     */
    public function findBasicSubscription(): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('LOWER(s.name) = :name')
            ->andWhere('s.isActive = :active')
            ->setParameter('name', 'basic')
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les abonnements uniques par nom (insensible à la casse)
     *
     * @return Subscription[]
     */
    public function findUniqueSubscriptions(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.price', 'ASC');

        $result = $qb->getQuery()->getResult();

        // Filtrer pour ne garder qu'un abonnement par nom (insensible à la casse)
        $uniqueSubscriptions = [];
        $processedNames = [];

        foreach ($result as $subscription) {
            $normalizedName = strtolower($subscription->getName());
            if (!in_array($normalizedName, $processedNames)) {
                $uniqueSubscriptions[] = $subscription;
                $processedNames[] = $normalizedName;
            }
        }

        return $uniqueSubscriptions;
    }
}
