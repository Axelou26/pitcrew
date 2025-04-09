<?php

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
        $this->user = new User();
        $this->user->setEmail('user@example.com');
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\DateTime::class, $this->ticket->getCreatedAt());
        $this->assertEquals('new', $this->ticket->getStatus());
        $this->assertEquals('normal', $this->ticket->getPriority());
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
        $subject = "Problème technique";
        $this->ticket->setSubject($subject);
        $this->assertEquals($subject, $this->ticket->getSubject());
    }

    public function testContent(): void
    {
        $content = "Description détaillée du problème";
        $this->ticket->setContent($content);
        $this->assertEquals($content, $this->ticket->getContent());
    }

    public function testStatus(): void
    {
        $status = "in_progress";
        $this->ticket->setStatus($status);
        $this->assertEquals($status, $this->ticket->getStatus());
    }

    public function testPriority(): void
    {
        $priority = "high";
        $this->ticket->setPriority($priority);
        $this->assertEquals($priority, $this->ticket->getPriority());
    }

    public function testDates(): void
    {
        // Test CreatedAt
        $createdAt = new \DateTime('2024-01-01 12:00:00');
        $this->ticket->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $this->ticket->getCreatedAt());

        // Test UpdatedAt
        $updatedAt = new \DateTime('2024-01-02 12:00:00');
        $this->ticket->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $this->ticket->getUpdatedAt());

        // Test avec une valeur null pour UpdatedAt
        $this->ticket->setUpdatedAt(null);
        $this->assertNull($this->ticket->getUpdatedAt());
    }

    public function testReplies(): void
    {
        $replies = [
            [
                'user_id' => 1,
                'content' => 'Première réponse',
                'created_at' => '2024-01-01 12:00:00'
            ],
            [
                'user_id' => 2,
                'content' => 'Deuxième réponse',
                'created_at' => '2024-01-02 12:00:00'
            ]
        ];

        $this->ticket->setReplies($replies);
        $this->assertEquals($replies, $this->ticket->getReplies());

        // Test avec une valeur null
        $this->ticket->setReplies(null);
        $this->assertNull($this->ticket->getReplies());
    }

    public function testFluentInterface(): void
    {
        $returnedTicket = $this->ticket
            ->setUser($this->user)
            ->setSubject("Problème technique")
            ->setContent("Description du problème")
            ->setStatus("in_progress")
            ->setPriority("high")
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setReplies([]);

        $this->assertSame($this->ticket, $returnedTicket);
    }
}
