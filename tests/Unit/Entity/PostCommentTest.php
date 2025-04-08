<?php

namespace App\Tests\Unit\Entity;

use App\Entity\PostComment;
use App\Entity\User;
use App\Entity\Post;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class PostCommentTest extends TestCase
{
    private PostComment $comment;
    private User $author;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->comment = new PostComment();
        $this->author = new User();
        $this->post = new Post();
        
        $this->author->setEmail('user@example.com');
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->comment->getCreatedAt());
        $this->assertInstanceOf(Collection::class, $this->comment->getReplies());
        $this->assertCount(0, $this->comment->getReplies());
    }

    public function testContent(): void
    {
        $content = "Ceci est un commentaire de test";
        $this->comment->setContent($content);
        $this->assertEquals($content, $this->comment->getContent());
    }

    public function testAuthorAssociation(): void
    {
        $this->comment->setAuthor($this->author);
        $this->assertSame($this->author, $this->comment->getAuthor());

        // Test avec une valeur null
        $this->comment->setAuthor(null);
        $this->assertNull($this->comment->getAuthor());
    }

    public function testPostAssociation(): void
    {
        $this->comment->setPost($this->post);
        $this->assertSame($this->post, $this->comment->getPost());

        // Test avec une valeur null
        $this->comment->setPost(null);
        $this->assertNull($this->comment->getPost());
    }

    public function testParentAssociation(): void
    {
        $parentComment = new PostComment();
        $this->comment->setParent($parentComment);
        $this->assertSame($parentComment, $this->comment->getParent());

        // Test avec une valeur null
        $this->comment->setParent(null);
        $this->assertNull($this->comment->getParent());
    }

    public function testReplies(): void
    {
        $reply1 = new PostComment();
        $reply2 = new PostComment();

        // Test d'ajout de réponses
        $this->comment->addReply($reply1);
        $this->comment->addReply($reply2);
        
        $this->assertCount(2, $this->comment->getReplies());
        $this->assertTrue($this->comment->getReplies()->contains($reply1));
        $this->assertTrue($this->comment->getReplies()->contains($reply2));
        $this->assertSame($this->comment, $reply1->getParent());
        $this->assertSame($this->comment, $reply2->getParent());

        // Test de suppression d'une réponse
        $this->comment->removeReply($reply1);
        $this->assertCount(1, $this->comment->getReplies());
        $this->assertFalse($this->comment->getReplies()->contains($reply1));
        $this->assertTrue($this->comment->getReplies()->contains($reply2));
        $this->assertNull($reply1->getParent());
    }

    public function testCreatedAt(): void
    {
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->comment->setCreatedAt($date);
        $this->assertEquals($date, $this->comment->getCreatedAt());
    }

    public function testFluentInterface(): void
    {
        $returnedComment = $this->comment
            ->setContent('Test content')
            ->setAuthor($this->author)
            ->setPost($this->post)
            ->setParent(null)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->assertSame($this->comment, $returnedComment);
    }
} 