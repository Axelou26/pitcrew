<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Hashtag;
use App\Repository\PostRepository;
use Psr\Cache\CacheItemPoolInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use App\Service\Post\PostContentProcessor;
use App\Service\Post\PostImageHandler;
use App\Service\Post\PostSearchCriteria;

class PostService
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CacheItemPoolInterface $cache,
        private EntityManagerInterface $entityManager,
        private readonly PostContentProcessor $contentProcessor,
        private readonly PostImageHandler $imageHandler,
        private readonly NotificationService $notificationService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return array<Post>
     */
    public function findPosts(PostSearchCriteria $criteria): array
    {
        return match ($criteria->getType()) {
            PostSearchCriteria::TYPE_SEARCH => $this->postRepository->search($criteria->getQuery()),
            PostSearchCriteria::TYPE_FEED => $this->postRepository->findPostsForFeed($criteria->getUser()),
            PostSearchCriteria::TYPE_HASHTAGS =>
                $this->postRepository->findRecentPostsWithHashtags($criteria->getFromDate()),
            PostSearchCriteria::TYPE_MENTIONS =>
                $this->postRepository->findByMentionedUser($criteria->getUser()),
            default => throw new \InvalidArgumentException('Type de recherche invalide'),
        };
    }

    /**
     * Analyse les hashtags dans le contenu d'un post
     * @return array<string>
     */
    public function extractHashtags(string $content): array
    {
        preg_match_all('/#(\w+)/', $content, $matches);
        return $matches[1];
    }

    /**
     * Analyse les mentions d'utilisateurs dans le contenu d'un post
     * @return array<string>
     */
    public function extractMentions(string $content): array
    {
        preg_match_all('/@(\w+)/', $content, $matches);
        return $matches[1];
    }

    /**
     * Vérifie si un post contient du contenu inapproprié
     */
    public function containsInappropriateContent(string $content): bool
    {
        // Liste de mots inappropriés à vérifier
        $inappropriateWords = ['spam', 'offensive', 'inappropriate'];

        $content = strtolower($content);
        foreach ($inappropriateWords as $word) {
            if (str_contains($content, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Génère un résumé du post pour les aperçus
     */
    public function generateSummary(string $content, int $maxLength = 150): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        $summary = substr($content, 0, $maxLength);
        $lastSpace = strrpos($summary, ' ');

        if ($lastSpace !== false) {
            $summary = substr($summary, 0, $lastSpace);
        }

        return $summary . '...';
    }

    /**
     * Enrichit le contenu du post avec des liens pour les hashtags et mentions
     */
    public function enrichContent(string $content): string
    {
        // Convertir les hashtags en liens
        $content = preg_replace(
            '/#(\w+)/',
            '<a href="/hashtag/$1" class="hashtag">#$1</a>',
            $content
        );

        // Convertir les mentions en liens
        $content = preg_replace(
            '/@(\w+)/',
            '<a href="/profile/$1" class="mention">@$1</a>',
            $content
        );

        return $content;
    }

    public function createPost(
        string $content,
        User $author,
        ?string $title = null,
        ?UploadedFile $imageFile = null
    ): Post {
        $this->contentProcessor->validate($content);

        $post = new Post();
        $post->setAuthor($author);
        $post->setContent(trim($content));
        $post->setTitle($title ? trim($title) : '');

        if ($imageFile) {
            $post->setImage($this->imageHandler->handleImageUpload($imageFile));
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $this->notificationService->notifyMentionedUsers($post);

        return $post;
    }

    public function updatePost(
        Post $post,
        string $content,
        ?string $title = null,
        ?UploadedFile $imageFile = null
    ): Post {
        $this->contentProcessor->validate($content);

        $post->setContent(trim($content));
        $post->setTitle($title ? trim($title) : '');

        if ($imageFile) {
            $post->setImage($this->imageHandler->handleImageUpload($imageFile, $post->getImage()));
        }

        $this->entityManager->flush();

        return $post;
    }

    public function deletePost(Post $post): void
    {
        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }

    public function validatePostContent(?string $content): void
    {
        if (!$content || trim($content) === '') {
            throw new InvalidArgumentException('Le contenu du post ne peut pas être vide');
        }
    }
}
