<?php

declare(strict_types = 1);

namespace App\Tests\Unit\Entity;

use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PostLikeTest extends TestCase
{
    private PostLike $postLike;
    private User $user;
    private Post $post;

    protected function setUp(): void
    {
        $this->postLike = new PostLike();
        $this->user     = new User();
        $this->post     = new Post();
    }

    public function testId(): void
    {
        $postLike   = new PostLike();
        $reflection = new \ReflectionClass(PostLike::class);
        $property   = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($postLike, 1);

        $this->assertSame(1, $postLike->getId());
    }

    public function testUser(): void
    {
        $this->postLike->setUser($this->user);
        $this->assertSame($this->user, $this->postLike->getUser());
    }

    public function testPost(): void
    {
        $this->postLike->setPost($this->post);
        $this->assertSame($this->post, $this->postLike->getPost());
    }

    public function testCreatedAt(): void
    {
        $createdAt = new \DateTimeImmutable();
        $this->postLike->setCreatedAt($createdAt);
        $this->assertSame($createdAt, $this->postLike->getCreatedAt());
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->postLike->getCreatedAt());
    }
}
