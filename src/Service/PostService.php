<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Hashtag;
use App\Repository\PostRepository;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

class PostService
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly AdapterInterface $cache,
        private EntityManagerInterface $entityManager,
        private ContentProcessorService $contentProcessor,
        private FileUploader $fileUploader,
        private NotificationService $notificationService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Recherche les posts avec texte libre
     * @return array<Post>
     */
    public function searchPosts(string $query): array
    {
        return $this->postRepository->search($query);
    }

    /**
     * Récupère les posts à afficher dans le fil d'actualité d'un utilisateur
     * @return array<Post>
     */
    public function getFeedPosts(User $user): array
    {
        return $this->postRepository->findPostsForFeed($user);
    }

    /**
     * Trouve les posts récents qui contiennent des hashtags
     * @return array<Post>
     */
    public function getRecentPostsWithHashtags(\DateTimeInterface $fromDate): array
    {
        return $this->postRepository->findRecentPostsWithHashtags($fromDate);
    }

    /**
     * Recherche les posts mentionnant un utilisateur spécifique
     * @return array<Post>
     */
    public function getPostsMentioningUser(User $user): array
    {
        return $this->postRepository->findByMentionedUser($user);
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
        $post = new Post();
        $post->setAuthor($author);
        $post->setContent(trim($content));
        $post->setTitle($title ? trim($title) : '');

        if ($imageFile) {
            try {
                $newFilename = $this->fileUploader->upload(
                    $imageFile,
                    'posts_directory'
                );
                $post->setImage($newFilename);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors du téléchargement de l\'image', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $this->contentProcessor->processUpdatedPostContent($post);
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
        $post->setContent(trim($content));
        $post->setTitle($title ? trim($title) : '');

        if ($imageFile) {
            try {
                $newFilename = $this->fileUploader->upload(
                    $imageFile,
                    'posts_directory',
                    $post->getImage()
                );
                $post->setImage($newFilename);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la mise à jour de l\'image', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $this->contentProcessor->processUpdatedPostContent($post);
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