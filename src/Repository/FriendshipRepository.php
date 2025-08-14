<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Friendship;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Friendship>
 *
 * @method null|Friendship find($id, $lockMode = null, $lockVersion = null)
 * @method null|Friendship findOneBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null
 * )
 * @method Friendship[]    findAll()
 * @method Friendship[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

    /**
     * Trouve une amitié entre deux utilisateurs, quel que soit le statut.
     */
    public function findBetweenUsers(User $user1, User $user2): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where(
                '(f.requester = :user1 AND f.addressee = :user2) OR ' .
                '(f.requester = :user2 AND f.addressee = :user1)'
            )
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve une amitié acceptée entre deux utilisateurs.
     */
    public function findAcceptedBetweenUsers(User $user1, User $user2): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where(
                '(f.requester = :user1 AND f.addressee = :user2) OR ' .
                '(f.requester = :user2 AND f.addressee = :user1)'
            )
            ->andWhere('f.status = :status')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve une demande d'amitié en attente entre deux utilisateurs (peu importe la direction).
     */
    public function findPendingRequestBetween(User $user1, User $user2): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where(
                '(f.requester = :user1 AND f.addressee = :user2) OR ' .
                '(f.requester = :user2 AND f.addressee = :user1)'
            )
            ->andWhere('f.status = :status')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setParameter('status', Friendship::STATUS_PENDING)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les amis d'un utilisateur.
     *
     * @return Friendship[]
     */
    public function findFriends(User $user): array
    {
        $friendships = $this->createQueryBuilder('f')
            ->leftJoin('f.requester', 'r')
            ->leftJoin('f.addressee', 'a')
            ->addSelect('r', 'a')
            ->where('(f.requester = :user OR f.addressee = :user)')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->getQuery()
            ->getResult();

        $friends = [];
        foreach ($friendships as $friendship) {
            $friend = $friendship->getRequester();
            if ($friend === $user) {
                $friend = $friendship->getAddressee();
            }
            $friends[] = $friend;
        }

        return $friends;
    }

    /**
     * Vérifie si deux utilisateurs sont amis (version optimisée).
     */
    public function areFriends(User $user1, User $user2): bool
    {
        $count = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where(
                '(f.requester = :user1 AND f.addressee = :user2) OR ' .
                '(f.requester = :user2 AND f.addressee = :user1)'
            )
            ->andWhere('f.status = :status')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Trouve les demandes d'amitié reçues en attente.
     *
     * @return Friendship[]
     */
    public function findByPendingRequestsReceived(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.requester', 'r')
            ->addSelect('r')
            ->where('f.addressee = :user')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_PENDING)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les demandes d'amitié reçues en attente.
     */
    public function countPendingRequestsReceived(User $user): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.addressee = :user')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les demandes d'amitié envoyées en attente.
     *
     * @return Friendship[]
     */
    public function findByPendingRequestsSent(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.addressee', 'a')
            ->addSelect('a')
            ->where('f.requester = :user')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_PENDING)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les amitiés d'un utilisateur.
     *
     * @return Friendship[]
     */
    public function findAllFriendshipsForUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.requester', 'r')
            ->leftJoin('f.addressee', 'a')
            ->addSelect('r', 'a')
            ->where('f.requester = :user OR f.addressee = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les amitiés acceptées d'un utilisateur.
     *
     * @return Friendship[]
     */
    public function findAcceptedFriendships(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.requester', 'r')
            ->leftJoin('f.addressee', 'a')
            ->addSelect('r', 'a')
            ->where('(f.requester = :user OR f.addressee = :user)')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
