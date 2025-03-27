<?php

namespace App\Repository;

use App\Entity\Friendship;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Friendship>
 *
 * @method Friendship|null find($id, $lockMode = null, $lockVersion = null)
 * @method Friendship|null findOneBy(array $criteria, array $orderBy = null)
 * @method Friendship[]    findAll()
 * @method Friendship[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

    /**
     * Trouve une amitié entre deux utilisateurs, quel que soit le statut
     */
    public function findBetweenUsers(User $user1, User $user2): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where('(f.requester = :user1 AND f.addressee = :user2) OR (f.requester = :user2 AND f.addressee = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve une amitié acceptée entre deux utilisateurs
     */
    public function findAcceptedBetweenUsers(User $user1, User $user2): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where('(f.requester = :user1 AND f.addressee = :user2) OR (f.requester = :user2 AND f.addressee = :user1)')
            ->andWhere('f.status = :status')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve une demande d'amitié en attente entre deux utilisateurs
     * @param bool $directional Si true, cherche uniquement les demandes de user1 vers user2
     */
    public function findPendingRequestBetweenUsers(User $user1, User $user2, bool $directional = false): ?Friendship
    {
        $qb = $this->createQueryBuilder('f');
        
        if ($directional) {
            $qb->where('f.requester = :user1 AND f.addressee = :user2');
        } else {
            $qb->where('(f.requester = :user1 AND f.addressee = :user2) OR (f.requester = :user2 AND f.addressee = :user1)');
        }
        
        $qb->andWhere('f.status = :status')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setParameter('status', Friendship::STATUS_PENDING);
        
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve toutes les demandes d'amitié en attente reçues par un utilisateur
     */
    public function findPendingRequestsReceived(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.addressee = :user')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_PENDING)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les demandes d'amitié en attente envoyées par un utilisateur
     */
    public function findPendingRequestsSent(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.requester = :user')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_PENDING)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les amis d'un utilisateur
     */
    public function findFriends(User $user): array
    {
        $friendships = $this->createQueryBuilder('f')
            ->where('(f.requester = :user OR f.addressee = :user)')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->getQuery()
            ->getResult();
        
        $friends = [];
        foreach ($friendships as $friendship) {
            if ($friendship->getRequester() === $user) {
                $friends[] = $friendship->getAddressee();
            } else {
                $friends[] = $friendship->getRequester();
            }
        }
        
        return $friends;
    }

    /**
     * Vérifie si deux utilisateurs sont amis
     */
    public function areFriends(User $user1, User $user2): bool
    {
        $friendship = $this->findAcceptedBetweenUsers($user1, $user2);
        return $friendship !== null;
    }
} 