<?php

namespace App\Repository;

use App\Entity\SupportTicket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportTicket>
 */
class SupportTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportTicket::class);
    }

    /**
     * Trouver les tickets en fonction du statut et de la priorité
     */
    public function findByStatusAndPriority(array $criteria): array
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.createdAt', 'ASC');
        
        if (isset($criteria['status'])) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $criteria['status']);
        }
        
        if (isset($criteria['priority'])) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $criteria['priority']);
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Trouver les tickets prioritaires non résolus
     */
    public function findPriorityTickets(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.priority = :priority')
            ->andWhere('t.status NOT IN (:statuses)')
            ->setParameter('priority', 'high')
            ->setParameter('statuses', ['resolved', 'closed'])
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Trouver les tickets non résolus d'un utilisateur
     */
    public function findOpenTicketsForUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.status NOT IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['resolved', 'closed'])
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Compter le nombre de tickets par statut
     */
    public function countByStatus(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();
        
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']] = $row['count'];
        }
        
        return $counts;
    }
} 