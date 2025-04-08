<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Friendship;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class FriendshipTest extends TestCase
{
    private Friendship $friendship;
    private User $requester;
    private User $addressee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->friendship = new Friendship();
        $this->requester = new User();
        $this->addressee = new User();
        
        $this->requester->setEmail('requester@example.com');
        $this->addressee->setEmail('addressee@example.com');
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->friendship->getCreatedAt());
        $this->assertEquals(Friendship::STATUS_PENDING, $this->friendship->getStatus());
        $this->assertNull($this->friendship->getUpdatedAt());
    }

    public function testUserAssociations(): void
    {
        // Test de l'association avec le demandeur
        $this->friendship->setRequester($this->requester);
        $this->assertSame($this->requester, $this->friendship->getRequester());

        // Test de l'association avec le destinataire
        $this->friendship->setAddressee($this->addressee);
        $this->assertSame($this->addressee, $this->friendship->getAddressee());
    }

    public function testStatus(): void
    {
        // Test du statut par défaut
        $this->assertEquals(Friendship::STATUS_PENDING, $this->friendship->getStatus());
        $this->assertTrue($this->friendship->isPending());
        $this->assertFalse($this->friendship->isAccepted());
        $this->assertFalse($this->friendship->isDeclined());

        // Test du statut accepté
        $this->friendship->setStatus(Friendship::STATUS_ACCEPTED);
        $this->assertEquals(Friendship::STATUS_ACCEPTED, $this->friendship->getStatus());
        $this->assertFalse($this->friendship->isPending());
        $this->assertTrue($this->friendship->isAccepted());
        $this->assertFalse($this->friendship->isDeclined());

        // Test du statut refusé
        $this->friendship->setStatus(Friendship::STATUS_DECLINED);
        $this->assertEquals(Friendship::STATUS_DECLINED, $this->friendship->getStatus());
        $this->assertFalse($this->friendship->isPending());
        $this->assertFalse($this->friendship->isAccepted());
        $this->assertTrue($this->friendship->isDeclined());
    }

    public function testDates(): void
    {
        // Test de la date de création
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->friendship->getCreatedAt());

        // Test de la date de mise à jour
        $this->assertNull($this->friendship->getUpdatedAt());

        $newDate = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->friendship->setUpdatedAt($newDate);
        $this->assertEquals($newDate, $this->friendship->getUpdatedAt());
    }

    public function testAcceptAndDecline(): void
    {
        // Test de l'acceptation
        $this->friendship->accept();
        $this->assertEquals(Friendship::STATUS_ACCEPTED, $this->friendship->getStatus());
        $this->assertTrue($this->friendship->isAccepted());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->friendship->getUpdatedAt());

        // Test du refus
        $this->friendship->decline();
        $this->assertEquals(Friendship::STATUS_DECLINED, $this->friendship->getStatus());
        $this->assertTrue($this->friendship->isDeclined());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->friendship->getUpdatedAt());
    }

    public function testFluentInterface(): void
    {
        $returnedFriendship = $this->friendship
            ->setRequester($this->requester)
            ->setAddressee($this->addressee)
            ->setStatus(Friendship::STATUS_PENDING)
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->assertSame($this->friendship, $returnedFriendship);
    }
} 