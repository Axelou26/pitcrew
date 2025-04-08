<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return array Returns an array of conversations with unread messages for a user
     */
    public function findUnreadMessagesGroupedByConversation(User $user): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.conversation', 'c')
            ->where('m.recipient = :user')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('m.createdAt', 'DESC');

        $unreadMessages = $qb->getQuery()->getResult();

        // Group messages by conversation
        $conversations = [];
        foreach ($unreadMessages as $message) {
            $conversationId = $message->getConversation()->getId();
            if (!isset($conversations[$conversationId])) {
                $conversations[$conversationId] = $message->getConversation();
            }
        }

        return array_values($conversations);
    }

    /**
     * Trouve les messages non lus dans une conversation pour un utilisateur spécifique
     */
    public function findUnreadMessagesInConversation(Conversation $conversation, User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->andWhere('m.recipient = :user')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getResult();
    }
}
