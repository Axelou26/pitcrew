<?php

namespace App\Tests\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Hashtag;
use App\Service\PostService;
use App\Service\NotificationService;
use App\Service\Post\PostContentProcessor;
use App\Service\Post\PostImageHandler;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Repository\HashtagRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostServiceTest extends TestCase
{
    private PostService $postService;
    private EntityManagerInterface $entityManager;
    private PostContentProcessor $contentProcessor;
    private PostImageHandler $imageHandler;
    private NotificationService $notificationService;
    private LoggerInterface $logger;
    private CacheItemPoolInterface $cache;
    private PostRepository $postRepository;
    private UserRepository $userRepository;
    private HashtagRepository $hashtagRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->contentProcessor = $this->createMock(PostContentProcessor::class);
        $this->imageHandler = $this->createMock(PostImageHandler::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->postRepository = $this->createMock(PostRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->hashtagRepository = $this->createMock(HashtagRepository::class);

        $this->postService = new PostService(
            $this->postRepository,
            $this->cache,
            $this->entityManager,
            $this->contentProcessor,
            $this->imageHandler,
            $this->notificationService,
            $this->logger
        );
    }

    public function testCreatePostWithHashtagsAndMentions(): void
    {
        // Arrange
        $content = "Test post with #hashtag and @username";
        $author = new User();
        $author->setEmail('author@example.com');

        $mentionedUser = $this->createMock(User::class);
        $mentionedUser->method('getId')->willReturn(1);

        $hashtag = new Hashtag();
        $hashtag->setName('hashtag');

        $this->contentProcessor->expects($this->once())
            ->method('validate')
            ->with($content);

        $this->contentProcessor->expects($this->once())
            ->method('extractHashtags')
            ->with($content)
            ->willReturn(['hashtag']);

        $this->contentProcessor->expects($this->once())
            ->method('extractMentions')
            ->with($content)
            ->willReturn(['username']);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Hashtag::class, $this->hashtagRepository],
                [User::class, $this->userRepository]
            ]);

        $this->hashtagRepository->expects($this->once())
            ->method('findOrCreate')
            ->with('hashtag')
            ->willReturn($hashtag);

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => 'username'])
            ->willReturn($mentionedUser);

        // Act
        $post = $this->postService->createPost($content, $author);

        // Assert
        $this->assertCount(1, $post->getHashtags());
        $this->assertContains($hashtag, $post->getHashtags());
        $this->assertCount(1, $post->getMentions());
        $this->assertContains(1, $post->getMentions());
    }

    public function testUpdatePostWithHashtagsAndMentions(): void
    {
        // Arrange
        $content = "Updated post with #newtag and @newuser";
        $post = new Post();
        $author = new User();
        $author->setEmail('author@example.com');
        $post->setAuthor($author);

        $oldHashtag = new Hashtag();
        $oldHashtag->setName('oldtag');
        $post->addHashtag($oldHashtag);

        $newHashtag = new Hashtag();
        $newHashtag->setName('newtag');

        $mentionedUser = $this->createMock(User::class);
        $mentionedUser->method('getId')->willReturn(1);

        $this->contentProcessor->expects($this->once())
            ->method('validate')
            ->with($content);

        $this->contentProcessor->expects($this->once())
            ->method('extractHashtags')
            ->with($content)
            ->willReturn(['newtag']);

        $this->contentProcessor->expects($this->once())
            ->method('extractMentions')
            ->with($content)
            ->willReturn(['newuser']);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Hashtag::class, $this->hashtagRepository],
                [User::class, $this->userRepository]
            ]);

        $this->hashtagRepository->expects($this->once())
            ->method('findOrCreate')
            ->with('newtag')
            ->willReturn($newHashtag);

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => 'newuser'])
            ->willReturn($mentionedUser);

        // Act
        $updatedPost = $this->postService->updatePost($post, $content);

        // Assert
        $this->assertCount(1, $updatedPost->getHashtags());
        $this->assertContains($newHashtag, $updatedPost->getHashtags());
        $this->assertNotContains($oldHashtag, $updatedPost->getHashtags());
        $this->assertCount(1, $updatedPost->getMentions());
        $this->assertContains(1, $updatedPost->getMentions());
    }
} 