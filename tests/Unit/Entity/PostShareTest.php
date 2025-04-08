<?php

namespace App\Tests\Unit\Entity;

use App\Entity\PostShare;
use App\Entity\User;
use App\Entity\Post;
use PHPUnit\Framework\TestCase;

class PostShareTest extends TestCase
{
    private PostShare $postShare;
    private User $user;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postShare = new PostShare();
        $this->user = new User();
        $this->post = new Post();
        
        $this->user->setEmail('user@example.com');
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->postShare->getCreatedAt());
    }

    public function testUserAssociation(): void
    {
        $this->postShare->setUser($this->user);
        $this->assertSame($this->user, $this->postShare->getUser());

        // Test avec une valeur null
        $this->postShare->setUser(null);
        $this->assertNull($this->postShare->getUser());
    }

    public function testPostAssociation(): void
    {
        $this->postShare->setPost($this->post);
        $this->assertSame($this->post, $this->postShare->getPost());

        // Test avec une valeur null
        $this->postShare->setPost(null);
        $this->assertNull($this->postShare->getPost());
    }

    public function testComment(): void
    {
        // Test avec un commentaire
        $comment = "Ceci est un commentaire de partage";
        $this->postShare->setComment($comment);
        $this->assertEquals($comment, $this->postShare->getComment());

        // Test avec une valeur null
        $this->postShare->setComment(null);
        $this->assertNull($this->postShare->getComment());
    }

    public function testCreatedAt(): void
    {
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->postShare->setCreatedAt($date);
        $this->assertEquals($date, $this->postShare->getCreatedAt());
    }

    public function testFluentInterface(): void
    {
        $returnedPostShare = $this->postShare
            ->setUser($this->user)
            ->setPost($this->post)
            ->setComment('Test comment')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->assertSame($this->postShare, $returnedPostShare);
    }
} 