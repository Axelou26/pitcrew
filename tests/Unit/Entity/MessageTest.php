<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Message;
use App\Entity\User;
use App\Entity\Conversation;
use App\Entity\JobApplication;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private Message $message;
    private User $sender;
    private User $recipient;
    private Conversation $conversation;
    private JobApplication $jobApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->message = new Message();
        $this->sender = new User();
        $this->recipient = new User();
        $this->conversation = new Conversation();
        $this->jobApplication = new JobApplication();

        $this->sender->setEmail('sender@example.com');
        $this->recipient->setEmail('recipient@example.com');
    }

    public function testBasicInformation(): void
    {
        $content = 'Bonjour, je suis intéressé par votre offre.';
        $this->message->setContent($content);
        $this->assertEquals($content, $this->message->getContent());
    }

    public function testUserAssociations(): void
    {
        // Test de l'association avec l'expéditeur
        $this->message->setSender($this->sender);
        $this->assertSame($this->sender, $this->message->getSender());

        // Test de l'association avec le destinataire
        $this->message->setRecipient($this->recipient);
        $this->assertSame($this->recipient, $this->message->getRecipient());
    }

    public function testConversationAssociation(): void
    {
        $this->message->setConversation($this->conversation);
        $this->assertSame($this->conversation, $this->message->getConversation());
    }

    public function testJobApplicationAssociation(): void
    {
        $this->message->setJobApplication($this->jobApplication);
        $this->assertSame($this->jobApplication, $this->message->getJobApplication());

        // Test avec une valeur null
        $this->message->setJobApplication(null);
        $this->assertNull($this->message->getJobApplication());
    }

    public function testReadStatus(): void
    {
        // Test du statut de lecture par défaut
        $this->assertFalse($this->message->isRead());

        // Test de changement du statut de lecture
        $this->message->setIsRead(true);
        $this->assertTrue($this->message->isRead());
    }

    public function testCreatedAt(): void
    {
        // Test de la date de création par défaut
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->message->getCreatedAt());

        // Test de modification de la date de création
        $newDate = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->message->setCreatedAt($newDate);
        $this->assertEquals($newDate, $this->message->getCreatedAt());
    }

    public function testConstructor(): void
    {
        $message = new Message();

        // Vérification des valeurs par défaut
        $this->assertInstanceOf(\DateTimeImmutable::class, $message->getCreatedAt());
        $this->assertFalse($message->isRead());
        $this->assertNull($message->getContent());
        $this->assertNull($message->getSender());
        $this->assertNull($message->getRecipient());
        $this->assertNull($message->getConversation());
        $this->assertNull($message->getJobApplication());
    }

    public function testNullableAssociations(): void
    {
        // Test des associations nullables
        $this->message->setJobApplication(null);
        $this->assertNull($this->message->getJobApplication());
    }

    public function testFluentInterface(): void
    {
        // Test de l'interface fluide
        $returnedMessage = $this->message
            ->setContent('Test message')
            ->setSender($this->sender)
            ->setRecipient($this->recipient)
            ->setConversation($this->conversation)
            ->setIsRead(true)
            ->setJobApplication($this->jobApplication);

        $this->assertSame($this->message, $returnedMessage);
    }
}
