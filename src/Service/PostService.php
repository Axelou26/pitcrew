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
    public function findPosts(PostSearchCriteria $criteria, int $page = 1, int $limit = 10): array
    {
        return match ($criteria->getType()) {
            PostSearchCriteria::TYPE_SEARCH => $this->postRepository->search($criteria->getQuery()),
            PostSearchCriteria::TYPE_FEED =>
                $this->postRepository->findPostsForFeed($criteria->getUser(), $page, $limit),
            PostSearchCriteria::TYPE_HASHTAGS =>
                $this->postRepository->findRecentPostsWithHashtags($criteria->getFromDate()),
            PostSearchCriteria::TYPE_MENTIONS =>
                $this->postRepository->findByMentionedUser($criteria->getUser()),
            default => throw new InvalidArgumentException('Type de recherche invalide'),
        };
    }

    /**
     * Analyse les hashtags dans le contenu d'un post
     * @return array<string>
     */
    public function extractHashtags(string $content): array
    {
        preg_match_all('/#([a-zA-ZÀ-ÿ0-9_-]+)/', $content, $matches);
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
            '/#([a-zA-ZÀ-ÿ0-9_-]+)/',
            '<a href="/hashtag/$1" class="hashtag">#$1</a>',
            $content
        );

        // Convertir les mentions en liens
        $content = preg_replace(
            '/@([a-zA-ZÀ-ÿ]+(?:\s+[a-zA-ZÀ-ÿ]+)*)/',
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
        $post = new Post();
        $post->setContent($content);
        $post->setTitle($title);
        $post->setAuthor($author);

        // Traitement du contenu pour les hashtags et mentions
        $processedContent = $this->contentProcessor->process($content);
        $post->setContent($processedContent['content']);

        // Traiter les hashtags
        foreach ($processedContent['hashtags'] as $hashtagName) {
            $hashtag = $this->entityManager->getRepository(Hashtag::class)->findOrCreate($hashtagName);
            $post->addHashtag($hashtag);
        }

        // Traiter les mentions
        foreach ($processedContent['mentions'] as $mentionName) {
            $mentionedUser = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('LOWER(CONCAT(u.firstName, \' \', u.lastName)) = LOWER(:fullName)')
                ->setParameter('fullName', trim($mentionName))
                ->getQuery()
                ->getOneOrNullResult();

            if ($mentionedUser) {
                $post->addMention($mentionedUser);
            }
        }

        // Traitement de l'image si présente
        if ($imageFile) {
            $imagePath = $this->imageHandler->handle($imageFile);
            $post->setImage($imagePath);
            $post->setImageName($imageFile->getClientOriginalName());
        }

        // Sauvegarde et notification
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        // Notifier les utilisateurs mentionnés
        $userRepository = $this->entityManager->getRepository(User::class);
        foreach ($post->getMentions() as $mentionedUserId) {
            $mentionedUser = $userRepository->find($mentionedUserId);
            if ($mentionedUser) {
                $this->notificationService->notifyMention($post, $mentionedUser);
            }
        }

        // Invalider le cache
        $this->cache->deleteItem(sprintf('feed_posts_%d', $author->getId()));

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

        // Supprimer les anciens hashtags
        foreach ($post->getHashtags()->toArray() as $hashtag) {
            $post->removeHashtag($hashtag);
        }

        // Traiter les nouveaux hashtags
        $hashtags = $this->contentProcessor->extractHashtags($content);
        foreach ($hashtags as $hashtagName) {
            $hashtag = $this->entityManager->getRepository(Hashtag::class)->findOrCreate($hashtagName);
            $post->addHashtag($hashtag);
        }

        // Mettre à jour les mentions
        $post->setMentions([]);
        $mentions = $this->contentProcessor->extractMentions($content);
        foreach ($mentions as $mention) {
            $mentionedUser = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('LOWER(CONCAT(u.firstName, \' \', u.lastName)) = LOWER(:fullName)')
                ->setParameter('fullName', trim($mention))
                ->getQuery()
                ->getOneOrNullResult();

            if ($mentionedUser) {
                $post->addMention($mentionedUser);
            }
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
