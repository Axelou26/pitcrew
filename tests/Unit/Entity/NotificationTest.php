<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Notification;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    private Notification $notification;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notification = new Notification();
        $this->user = new User();
        $this->user->setEmail('user@example.com');
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->notification->getCreatedAt());
        $this->assertFalse($this->notification->isIsRead());
        $this->assertEquals(Notification::TYPE_INFO, $this->notification->getType());
    }

    public function testUserAssociation(): void
    {
        $this->notification->setUser($this->user);
        $this->assertSame($this->user, $this->notification->getUser());
    }

    public function testTitle(): void
    {
        $title = "Nouvelle notification";
        $this->notification->setTitle($title);
        $this->assertEquals($title, $this->notification->getTitle());
    }

    public function testMessage(): void
    {
        $message = "Contenu de la notification";
        $this->notification->setMessage($message);
        $this->assertEquals($message, $this->notification->getMessage());
    }

    public function testIsRead(): void
    {
        $this->notification->setIsRead(true);
        $this->assertTrue($this->notification->isIsRead());

        $this->notification->setIsRead(false);
        $this->assertFalse($this->notification->isIsRead());
    }

    public function testLink(): void
    {
        $link = "/posts/1";
        $this->notification->setLink($link);
        $this->assertEquals($link, $this->notification->getLink());

        // Test avec une valeur null
        $this->notification->setLink(null);
        $this->assertNull($this->notification->getLink());
    }

    public function testType(): void
    {
        $validTypes = [
            Notification::TYPE_INFO,
            Notification::TYPE_MENTION,
            Notification::TYPE_LIKE,
            Notification::TYPE_COMMENT,
            Notification::TYPE_SHARE,
            Notification::TYPE_FRIEND_REQUEST,
            Notification::TYPE_APPLICATION
        ];

        foreach ($validTypes as $type) {
            $this->notification->setType($type);
            $this->assertEquals($type, $this->notification->getType());
        }
    }

    public function testEntityTypeAndId(): void
    {
        $entityType = "post";
        $entityId = 1;

        $this->notification->setEntityType($entityType);
        $this->notification->setEntityId($entityId);

        $this->assertEquals($entityType, $this->notification->getEntityType());
        $this->assertEquals($entityId, $this->notification->getEntityId());

        // Test avec des valeurs null
        $this->notification->setEntityType(null);
        $this->notification->setEntityId(null);

        $this->assertNull($this->notification->getEntityType());
        $this->assertNull($this->notification->getEntityId());
    }

    public function testActorId(): void
    {
        $actorId = 1;
        $this->notification->setActorId($actorId);
        $this->assertEquals($actorId, $this->notification->getActorId());

        // Test avec une valeur null
        $this->notification->setActorId(null);
        $this->assertNull($this->notification->getActorId());
    }

    public function testFluentInterface(): void
    {
        $returnedNotification = $this->notification
            ->setUser($this->user)
            ->setTitle("Titre")
            ->setMessage("Message")
            ->setIsRead(true)
            ->setLink("/test")
            ->setType(Notification::TYPE_INFO)
            ->setEntityType("post")
            ->setEntityId(1)
            ->setActorId(2);

        $this->assertSame($this->notification, $returnedNotification);
    }
}
