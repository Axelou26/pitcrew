<?php

declare(strict_types = 1);

namespace App\Tests\Unit\Entity;

use App\Entity\SupportTicket;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class SupportTicketTest extends TestCase
{
    private SupportTicket $ticket;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticket = new SupportTicket();
        $this->user   = new User();
        $this->user->setEmail('user@example.com');
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->ticket->getCreatedAt());
        $this->assertSame('new', $this->ticket->getStatus());
        $this->assertSame('normal', $this->ticket->getPriority());
        $this->assertIsArray($this->ticket->getReplies());
        $this->assertEmpty($this->ticket->getReplies());
    }

    public function testUserAssociation(): void
    {
        $this->ticket->setUser($this->user);
        $this->assertSame($this->user, $this->ticket->getUser());
    }

    public function testSubject(): void
    {
        $subject = 'Problème technique';
        $this->ticket->setSubject($subject);
        $this->assertSame($subject, $this->ticket->getSubject());
    }

    public function testContent(): void
    {
        $content = 'Description détaillée du problème';
        $this->ticket->setContent($content);
        $this->assertSame($content, $this->ticket->getContent());
    }

    public function testStatus(): void
    {
        $status = 'in_progress';
        $this->ticket->setStatus($status);
        $this->assertSame($status, $this->ticket->getStatus());
    }

    public function testPriority(): void
    {
        $priority = 'high';
        $this->ticket->setPriority($priority);
        $this->assertSame($priority, $this->ticket->getPriority());
    }

    public function testDates(): void
    {
        // Test CreatedAt
        $createdAt = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->ticket->setCreatedAt($createdAt);
        $this->assertSame($createdAt, $this->ticket->getCreatedAt());

        // Test UpdatedAt
        $updatedAt = new \DateTimeImmutable('2024-01-02 12:00:00');
        $this->ticket->setUpdatedAt($updatedAt);
        $this->assertSame($updatedAt, $this->ticket->getUpdatedAt());

        // Test avec une valeur null pour UpdatedAt
        $this->ticket->setUpdatedAt(null);
        $this->assertNull($this->ticket->getUpdatedAt());
    }

    public function testReplies(): void
    {
        $ticket = new SupportTicket();

        // Initialiser avec un tableau vide
        $ticket->setReplies([]);
        $this->assertIsArray($ticket->getReplies());
        $this->assertEmpty($ticket->getReplies());

        // Ajouter une réponse
        $reply = 'Voici une réponse';
        $ticket->addReply($reply);
        $this->assertCount(1, $ticket->getReplies());
        $this->assertSame($reply, $ticket->getReplies()[0]);
    }

    public function testFluentInterface(): void
    {
        $returnedTicket = $this->ticket
            ->setUser($this->user)
            ->setSubject('Problème technique')
            ->setContent('Description du problème')
            ->setStatus('in_progress')
            ->setPriority('high')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setReplies([]);

        $this->assertSame($this->ticket, $returnedTicket);
    }
}
