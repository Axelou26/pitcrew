<?php

declare(strict_types = 1);

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
        $this->requester  = new User();
        $this->addressee  = new User();

        $this->requester->setEmail('requester@example.com');
        $this->addressee->setEmail('addressee@example.com');
    }

    public function testConstructor(): void
    {
        $friendship = new Friendship();

        // Les dates sont maintenant initialisées dans le constructeur
        $this->assertInstanceOf(\DateTimeInterface::class, $friendship->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $friendship->getUpdatedAt());
        $this->assertSame('pending', $friendship->getStatus());
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
        $friendship = new Friendship();

        $this->assertTrue($friendship->isPending());
        $this->assertFalse($friendship->isAccepted());
        $this->assertFalse($friendship->isRejected());

        $friendship->setStatus('accepted');
        $this->assertFalse($friendship->isPending());
        $this->assertTrue($friendship->isAccepted());
        $this->assertFalse($friendship->isRejected());

        $friendship->setStatus('rejected');
        $this->assertFalse($friendship->isPending());
        $this->assertFalse($friendship->isAccepted());
        $this->assertTrue($friendship->isRejected());
    }

    public function testDates(): void
    {
        $friendship = new Friendship();

        // Les dates sont maintenant initialisées dans le constructeur
        $this->assertInstanceOf(\DateTimeInterface::class, $friendship->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $friendship->getUpdatedAt());

        $newDate = new \DateTimeImmutable();
        $friendship->setUpdatedAt($newDate);
        $this->assertSame($newDate, $friendship->getUpdatedAt());
    }

    public function testAcceptAndDecline(): void
    {
        $friendship = new Friendship();

        $this->assertTrue($friendship->isPending());

        $friendship->accept();
        $this->assertTrue($friendship->isAccepted());
        $this->assertFalse($friendship->isPending());
        $this->assertFalse($friendship->isRejected());

        $friendship->reject();
        $this->assertFalse($friendship->isAccepted());
        $this->assertFalse($friendship->isPending());
        $this->assertTrue($friendship->isRejected());
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
