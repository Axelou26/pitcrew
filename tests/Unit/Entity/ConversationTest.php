<?php

declare(strict_types = 1);

namespace App\Tests\Unit\Entity;

use App\Entity\Conversation;
use App\Entity\JobApplication;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class ConversationTest extends TestCase
{
    private Conversation $conversation;
    private User $participant1;
    private User $participant2;
    private Message $message;
    private JobApplication $jobApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conversation   = new Conversation();
        $this->participant1   = new User();
        $this->participant2   = new User();
        $this->message        = new Message();
        $this->jobApplication = new JobApplication();

        $this->participant1->setEmail('participant1@example.com');
        $this->participant2->setEmail('participant2@example.com');
    }

    public function testParticipantAssociations(): void
    {
        // Test de l'association avec le premier participant
        $this->conversation->setParticipant1($this->participant1);
        $this->assertSame($this->participant1, $this->conversation->getParticipant1());

        // Test de l'association avec le deuxième participant
        $this->conversation->setParticipant2($this->participant2);
        $this->assertSame($this->participant2, $this->conversation->getParticipant2());
    }

    public function testJobApplicationAssociation(): void
    {
        $this->conversation->setJobApplication($this->jobApplication);
        $this->assertSame($this->jobApplication, $this->conversation->getJobApplication());

        // Test avec une valeur null
        $this->conversation->setJobApplication(null);
        $this->assertNull($this->conversation->getJobApplication());
    }

    public function testDates(): void
    {
        // Test des dates par défaut
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->conversation->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->conversation->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->conversation->getLastMessageAt());

        // Test de modification des dates
        $newDate = new \DateTimeImmutable('2024-01-01 12:00:00');

        $this->conversation->setCreatedAt($newDate);
        $this->assertSame($newDate, $this->conversation->getCreatedAt());

        $this->conversation->setUpdatedAt($newDate);
        $this->assertSame($newDate, $this->conversation->getUpdatedAt());

        $this->conversation->setLastMessageAt($newDate);
        $this->assertSame($newDate, $this->conversation->getLastMessageAt());
    }

    public function testMessagesCollection(): void
    {
        // Test de la collection de messages
        $this->assertInstanceOf(ArrayCollection::class, $this->conversation->getMessages());
        $this->assertCount(0, $this->conversation->getMessages());

        // Test d'ajout d'un message
        $this->conversation->addMessage($this->message);
        $this->assertCount(1, $this->conversation->getMessages());
        $this->assertTrue($this->conversation->getMessages()->contains($this->message));
        $this->assertSame($this->conversation, $this->message->getConversation());

        // Test de suppression d'un message
        $this->conversation->removeMessage($this->message);
        $this->assertCount(0, $this->conversation->getMessages());
        $this->assertFalse($this->conversation->getMessages()->contains($this->message));
        $this->assertNull($this->message->getConversation());
    }

    public function testGetOtherParticipant(): void
    {
        $this->conversation->setParticipant1($this->participant1);
        $this->conversation->setParticipant2($this->participant2);

        // Test pour le participant1
        $otherParticipant = $this->conversation->getOtherParticipant($this->participant1);
        $this->assertSame($this->participant2, $otherParticipant);

        // Test pour le participant2
        $otherParticipant = $this->conversation->getOtherParticipant($this->participant2);
        $this->assertSame($this->participant1, $otherParticipant);

        // Test pour un utilisateur non participant
        $nonParticipant   = new User();
        $otherParticipant = $this->conversation->getOtherParticipant($nonParticipant);
        $this->assertNull($otherParticipant);
    }

    public function testHasUnreadMessages(): void
    {
        $this->conversation->setParticipant1($this->participant1);
        $this->conversation->setParticipant2($this->participant2);

        $message = new Message();
        $message->setSender($this->participant1);
        $message->setRecipient($this->participant2);
        $message->setIsRead(false);

        $this->conversation->addMessage($message);

        // Test pour le destinataire avec un message non lu
        $this->assertTrue($this->conversation->hasUnreadMessages($this->participant2));

        // Test pour l'expéditeur
        $this->assertFalse($this->conversation->hasUnreadMessages($this->participant1));

        // Test après avoir marqué le message comme lu
        $message->setIsRead(true);
        $this->assertFalse($this->conversation->hasUnreadMessages($this->participant2));
    }

    public function testGetLastMessage(): void
    {
        // Test sans message
        $this->assertNull($this->conversation->getLastMessage());

        // Test avec un message
        $message1 = new Message();
        $message1->setCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));
        $this->conversation->addMessage($message1);
        $this->assertSame($message1, $this->conversation->getLastMessage());

        // Test avec plusieurs messages
        $message2 = new Message();
        $message2->setCreatedAt(new \DateTimeImmutable('2024-01-02 12:00:00'));
        $this->conversation->addMessage($message2);
        $this->assertSame($message2, $this->conversation->getLastMessage());
    }

    public function testConstructor(): void
    {
        $conversation = new Conversation();

        // Vérification des valeurs par défaut
        $this->assertInstanceOf(\DateTimeImmutable::class, $conversation->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $conversation->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $conversation->getLastMessageAt());
        $this->assertInstanceOf(ArrayCollection::class, $conversation->getMessages());
        $this->assertCount(0, $conversation->getMessages());
        $this->assertNull($conversation->getParticipant1());
        $this->assertNull($conversation->getParticipant2());
        $this->assertNull($conversation->getJobApplication());
    }

    public function testFluentInterface(): void
    {
        // Test de l'interface fluide
        $returnedConversation = $this->conversation
            ->setParticipant1($this->participant1)
            ->setParticipant2($this->participant2)
            ->setJobApplication($this->jobApplication)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setLastMessageAt(new \DateTimeImmutable());

        $this->assertSame($this->conversation, $returnedConversation);
    }
}
