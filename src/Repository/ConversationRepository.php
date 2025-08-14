<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 *
 * @method null|Conversation find($id, $lockMode = null, $lockVersion = null)
 * @method null|Conversation findOneBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null
 * )
 * @method Conversation[]    findAll()
 * @method Conversation[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Find a conversation between two users.
     */
    public function findBetweenUsers(User $user1, User $user2): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->where(
                '(c.participant1 = :user1 AND c.participant2 = :user2) OR ' .
                '(c.participant1 = :user2 AND c.participant2 = :user1)'
            )
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les conversations d'un utilisateur.
     *
     * @return Conversation[]
     */
    public function findConversationsForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.participant1 = :user OR c.participant2 = :user')
            ->setParameter('user', $user)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une conversation entre deux utilisateurs.
     */
    public function findConversationBetweenUsers(User $user1, User $user2): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->where(
                '(c.participant1 = :user1 AND c.participant2 = :user2) OR ' .
                '(c.participant1 = :user2 AND c.participant2 = :user1)'
            )
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
