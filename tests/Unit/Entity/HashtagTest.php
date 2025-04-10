<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Hashtag;
use App\Entity\Post;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class HashtagTest extends TestCase
{
    private Hashtag $hashtag;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hashtag = new Hashtag();
        $this->post = new Post();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->hashtag->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->hashtag->getLastUsedAt());
        $this->assertInstanceOf(Collection::class, $this->hashtag->getPosts());
        $this->assertCount(0, $this->hashtag->getPosts());
        $this->assertEquals(0, $this->hashtag->getUsageCount());
    }

    public function testName(): void
    {
        // Test avec un nom simple
        $this->hashtag->setName('test');
        $this->assertEquals('test', $this->hashtag->getName());

        // Test avec un nom contenant un #
        $this->hashtag->setName('#test');
        $this->assertEquals('test', $this->hashtag->getName());

        // Test avec des majuscules
        $this->hashtag->setName('TestTag');
        $this->assertEquals('testtag', $this->hashtag->getName());
    }

    public function testUsageCount(): void
    {
        $this->hashtag->setUsageCount(5);
        $this->assertEquals(5, $this->hashtag->getUsageCount());

        // Test de l'incrémentation
        $this->hashtag->incrementUsageCount();
        $this->assertEquals(6, $this->hashtag->getUsageCount());

        // Vérifier que lastUsedAt est mis à jour lors de l'incrémentation
        $beforeIncrement = $this->hashtag->getLastUsedAt()->getTimestamp();
        sleep(1); // Attendre une seconde pour assurer une différence de temps
        $this->hashtag->incrementUsageCount();
        $this->assertGreaterThan($beforeIncrement, $this->hashtag->getLastUsedAt()->getTimestamp());
    }

    public function testLastUsedAt(): void
    {
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->hashtag->setLastUsedAt($date);
        $this->assertEquals($date, $this->hashtag->getLastUsedAt());
    }

    public function testPosts(): void
    {
        // Test d'ajout de posts
        $this->hashtag->addPost($this->post);
        $this->assertTrue($this->hashtag->getPosts()->contains($this->post));
        $this->assertTrue($this->post->getHashtags()->contains($this->hashtag));

        // Test de suppression de post
        $this->hashtag->removePost($this->post);
        $this->assertFalse($this->hashtag->getPosts()->contains($this->post));
        $this->assertFalse($this->post->getHashtags()->contains($this->hashtag));
    }

    public function testFormattedName(): void
    {
        $this->hashtag->setName('test');
        $this->assertEquals('#test', $this->hashtag->getFormattedName());

        $this->hashtag->setName('#another');
        $this->assertEquals('#another', $this->hashtag->getFormattedName());
    }

    public function testToString(): void
    {
        $this->hashtag->setName('test');
        $this->assertEquals('#test', (string) $this->hashtag);
    }

    public function testFluentInterface(): void
    {
        $returnedHashtag = $this->hashtag
            ->setName('test')
            ->setUsageCount(1)
            ->setLastUsedAt(new \DateTimeImmutable());

        $this->assertSame($this->hashtag, $returnedHashtag);
    }
}
