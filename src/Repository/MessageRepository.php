<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Trouve les messages non lus groupés par conversation.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findUnreadMessagesGroupedByConversation(User $user): array
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->join('m.conversation', 'c')
            ->where('c.participant1 = :user OR c.participant2 = :user')
            ->andWhere('m.author != :user')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC');

        $messages = $queryBuilder->getQuery()->getResult();

        // Group messages by conversation
        $conversations = [];
        foreach ($messages as $message) {
            // Skip read messages in PHP instead of in the query
            if ($message->isRead()) {
                continue;
            }

            $conversationId = $message->getConversation()->getId();
            if (!isset($conversations[$conversationId])) {
                $conversations[$conversationId] = $message->getConversation();
            }
        }

        return array_values($conversations);
    }

    /**
     * Trouve les messages non lus dans une conversation.
     *
     * @return Message[]
     */
    public function findUnreadMessagesInConversation(Conversation $conversation, User $user): array
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->andWhere('m.author != :user')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery();

        $messages = $queryBuilder->getResult();

        // Filter out read messages in PHP code
        return array_filter($messages, function (Message $message) {
            return !$message->isRead();
        });
    }
}
