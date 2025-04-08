<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\PostLike;
use App\Entity\PostComment;
use App\Entity\PostShare;
use App\Entity\Hashtag;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    private Post $post;
    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->post = new Post();
        $this->author = new User();
        $this->author->setEmail('author@example.com');
    }

    public function testConstructor(): void
    {
        // Test des valeurs par défaut
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->post->getCreatedAt());
        $this->assertInstanceOf(Collection::class, $this->post->getLikes());
        $this->assertInstanceOf(Collection::class, $this->post->getComments());
        $this->assertInstanceOf(Collection::class, $this->post->getShares());
        $this->assertInstanceOf(Collection::class, $this->post->getHashtags());
        
        // Test des compteurs
        $this->assertEquals(0, $this->post->getLikesCount());
        $this->assertEquals(0, $this->post->getCommentsCount());
        $this->assertEquals(0, $this->post->getSharesCount());
        
        // Test des tableaux
        $this->assertIsArray($this->post->getMentions());
        $this->assertEmpty($this->post->getMentions());
    }

    public function testBasicInformation(): void
    {
        $title = "Mon titre de post";
        $content = "Le contenu de mon post";
        $image = "image.jpg";
        $imageName = "Mon image";

        $this->post->setTitle($title)
            ->setContent($content)
            ->setImage($image)
            ->setImageName($imageName);

        $this->assertEquals($title, $this->post->getTitle());
        $this->assertEquals($content, $this->post->getContent());
        $this->assertEquals($image, $this->post->getImage());
        $this->assertEquals($imageName, $this->post->getImageName());
    }

    public function testAuthorAssociation(): void
    {
        $this->post->setAuthor($this->author);
        $this->assertSame($this->author, $this->post->getAuthor());

        // Test avec une valeur null
        $this->post->setAuthor(null);
        $this->assertNull($this->post->getAuthor());
    }

    public function testLikes(): void
    {
        $user = new User();
        $like = new PostLike();
        $like->setUser($user);

        // Test d'ajout d'un like
        $this->post->addLike($like);
        $this->assertTrue($this->post->getLikes()->contains($like));
        $this->assertEquals(1, $this->post->getLikesCount());
        $this->assertTrue($this->post->isLikedByUser($user));

        // Test de suppression d'un like
        $this->post->removeLike($like);
        $this->assertFalse($this->post->getLikes()->contains($like));
        $this->assertEquals(0, $this->post->getLikesCount());
        $this->assertFalse($this->post->isLikedByUser($user));

        // Test de mise à jour du compteur
        $this->post->addLike($like);
        $this->post->updateLikesCounter();
        $this->assertEquals(1, $this->post->getLikesCount());
    }

    public function testComments(): void
    {
        $comment = new PostComment();
        
        // Test d'ajout d'un commentaire
        $this->post->addComment($comment);
        $this->assertTrue($this->post->getComments()->contains($comment));
        $this->assertEquals(1, $this->post->getCommentsCount());

        // Test de suppression d'un commentaire
        $this->post->removeComment($comment);
        $this->assertFalse($this->post->getComments()->contains($comment));
        $this->assertEquals(0, $this->post->getCommentsCount());

        // Test de mise à jour du compteur
        $this->post->addComment($comment);
        $this->post->updateCommentsCounter();
        $this->assertEquals(1, $this->post->getCommentsCount());
    }

    public function testShares(): void
    {
        $share = new PostShare();
        
        // Test d'ajout d'un partage
        $this->post->addShare($share);
        $this->assertTrue($this->post->getShares()->contains($share));
        $this->assertEquals(1, $this->post->getSharesCount());

        // Test de suppression d'un partage
        $this->post->removeShare($share);
        $this->assertFalse($this->post->getShares()->contains($share));
        $this->assertEquals(0, $this->post->getSharesCount());

        // Test de mise à jour du compteur
        $this->post->addShare($share);
        $this->post->updateSharesCounter();
        $this->assertEquals(1, $this->post->getSharesCount());
    }

    public function testHashtags(): void
    {
        $hashtag = new Hashtag();
        $hashtag->setName('test');

        // Test d'ajout d'un hashtag
        $this->post->addHashtag($hashtag);
        $this->assertTrue($this->post->getHashtags()->contains($hashtag));

        // Test de suppression d'un hashtag
        $this->post->removeHashtag($hashtag);
        $this->assertFalse($this->post->getHashtags()->contains($hashtag));
    }

    public function testMentions(): void
    {
        $mentions = ['user1', 'user2'];
        $this->post->setMentions($mentions);
        $this->assertEquals($mentions, $this->post->getMentions());
    }

    public function testFluentInterface(): void
    {
        // Test de l'interface fluide
        $returnedPost = $this->post
            ->setTitle("Test")
            ->setContent("Test content")
            ->setImage("test.jpg")
            ->setImageName("Test Image")
            ->setAuthor($this->author)
            ->setMentions(['user1']);

        $this->assertSame($this->post, $returnedPost);
    }
} 