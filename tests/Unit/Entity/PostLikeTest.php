<?php

namespace App\Tests\Unit\Entity;

use App\Entity\PostLike;
use App\Entity\User;
use App\Entity\Post;
use PHPUnit\Framework\TestCase;

class PostLikeTest extends TestCase
{
    private PostLike $postLike;
    private User $user;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postLike = new PostLike();
        $this->user = new User();
        $this->post = new Post();
        
        $this->user->setEmail('user@example.com');
    }

    public function testConstructor(): void
    {
        // Test des valeurs par défaut
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->postLike->getCreatedAt());
        $this->assertEquals(PostLike::REACTION_LIKE, $this->postLike->getReactionType());
    }

    public function testUserAssociation(): void
    {
        $this->postLike->setUser($this->user);
        $this->assertSame($this->user, $this->postLike->getUser());

        // Test avec une valeur null
        $this->postLike->setUser(null);
        $this->assertNull($this->postLike->getUser());
    }

    public function testPostAssociation(): void
    {
        $this->postLike->setPost($this->post);
        $this->assertSame($this->post, $this->postLike->getPost());

        // Test avec une valeur null
        $this->postLike->setPost(null);
        $this->assertNull($this->postLike->getPost());
    }

    public function testCreatedAt(): void
    {
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->postLike->setCreatedAt($date);
        $this->assertEquals($date, $this->postLike->getCreatedAt());
    }

    public function testReactionType(): void
    {
        // Test de tous les types de réactions valides
        $validReactions = [
            PostLike::REACTION_LIKE,
            PostLike::REACTION_CONGRATS,
            PostLike::REACTION_INTERESTING,
            PostLike::REACTION_SUPPORT,
            PostLike::REACTION_ENCOURAGING
        ];

        foreach ($validReactions as $reaction) {
            $this->postLike->setReactionType($reaction);
            $this->assertEquals($reaction, $this->postLike->getReactionType());
        }

        // Test avec un type de réaction invalide
        $this->expectException(\InvalidArgumentException::class);
        $this->postLike->setReactionType('invalid_reaction');
    }

    public function testReactionEmoji(): void
    {
        // Test des emojis pour chaque type de réaction
        $reactionEmojis = [
            PostLike::REACTION_LIKE => '👍',
            PostLike::REACTION_CONGRATS => '👏',
            PostLike::REACTION_INTERESTING => '💡',
            PostLike::REACTION_SUPPORT => '❤️',
            PostLike::REACTION_ENCOURAGING => '💪'
        ];

        foreach ($reactionEmojis as $type => $emoji) {
            $this->postLike->setReactionType($type);
            $this->assertEquals($emoji, $this->postLike->getReactionEmoji());
        }
    }

    public function testReactionName(): void
    {
        // Test des noms pour chaque type de réaction
        $reactionNames = [
            PostLike::REACTION_LIKE => 'J\'aime',
            PostLike::REACTION_CONGRATS => 'Félicitations',
            PostLike::REACTION_INTERESTING => 'Intéressant',
            PostLike::REACTION_SUPPORT => 'Soutien',
            PostLike::REACTION_ENCOURAGING => 'Encourageant'
        ];

        foreach ($reactionNames as $type => $name) {
            $this->postLike->setReactionType($type);
            $this->assertEquals($name, $this->postLike->getReactionName());
        }
    }

    public function testFluentInterface(): void
    {
        // Test de l'interface fluide
        $returnedPostLike = $this->postLike
            ->setUser($this->user)
            ->setPost($this->post)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setReactionType(PostLike::REACTION_LIKE);

        $this->assertSame($this->postLike, $returnedPostLike);
    }
} 