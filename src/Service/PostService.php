<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Hashtag;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Service\Post\PostContentProcessor;
use App\Service\Post\PostImageHandler;
use App\Service\Post\PostSearchCriteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
     * @return Post[]
     */
    public function findPosts(PostSearchCriteria $criteria, int $page = 1, int $limit = 10): array
    {
        return match ($criteria->getType()) {
            PostSearchCriteria::TYPE_SEARCH => $this->postRepository->search($criteria->getQuery() ?? ''),
            PostSearchCriteria::TYPE_FEED   => $this->postRepository->findPostsForFeed(
                $criteria->getUser() ?? throw new InvalidArgumentException('User is required for feed search'),
                $page,
                $limit
            ),
            PostSearchCriteria::TYPE_HASHTAGS => $this->postRepository->findRecentPostsWithHashtags(
                $criteria->getFromDate() ?? throw new InvalidArgumentException(
                    'FromDate is required for hashtags search'
                )
            ),
            PostSearchCriteria::TYPE_MENTIONS => $this->postRepository->findByMentionedUser(
                $criteria->getUser() ?? throw new InvalidArgumentException('User is required for mentions search')
            ),
            default => throw new InvalidArgumentException('Type de recherche invalide'),
        };
    }

    /**
     * @return string[]
     */
    public function extractHashtags(string $content): array
    {
        preg_match_all('/#([a-zA-ZÀ-ÿ0-9_-]+)/', $content, $matches);

        return $matches[1];
    }

    /**
     * @return string[]
     */
    public function extractMentions(string $content): array
    {
        preg_match_all('/@(\w+)/', $content, $matches);

        return $matches[1];
    }

    /**
     * Vérifie si un post contient du contenu inapproprié.
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
     * Génère un résumé du post pour les aperçus.
     */
    public function generateSummary(string $content, int $maxLength = 150): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        $summary   = substr($content, 0, $maxLength);
        $lastSpace = strrpos($summary, ' ');

        if ($lastSpace !== false) {
            $summary = substr($summary, 0, $lastSpace);
        }

        return $summary . '...';
    }

    /**
     * Enrichit le contenu du post avec des liens pour les hashtags et mentions.
     */
    public function enrichContent(string $content): string
    {
        // Convertir les hashtags en liens
        $content = preg_replace(
            '/#([a-zA-ZÀ-ÿ0-9_-]+)/',
            '<a href="/hashtag/$1" class="hashtag">#$1</a>',
            $content ?? ''
        );

        // Convertir les mentions en liens
        $content = preg_replace(
            '/@([a-zA-ZÀ-ÿ]+(?:\s+[a-zA-ZÀ-ÿ]+)*)/',
            '<a href="/profile/$1" class="mention">@$1</a>',
            $content ?? ''
        );

        return $content ?? '';
    }

    /**
     * Crée un nouveau post.
     */
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

        if ($title !== null) {
            $post->setTitle(trim($title));
        }

        if ($imageFile) {
            $post->setImage($this->imageHandler->handleImageUpload($imageFile));
        }

        $this->processPostHashtags($post, $content);
        $this->processPostMentions($post, $content);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

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

        $this->clearPostHashtags($post);
        $this->processPostHashtags($post, $content);
        $this->updatePostMentions($post, $content);

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

    private function processPostHashtags(Post $post, string $content): void
    {
        $hashtags = $this->contentProcessor->extractHashtags($content);
        foreach ($hashtags as $hashtagName) {
            try {
                $hashtag = $this->entityManager->getRepository(Hashtag::class)->findOrCreate($hashtagName);
                $post->addHashtag($hashtag);
            } catch (Exception $e) {
                // En cas d'erreur avec un hashtag, essayer de le récupérer une dernière fois
                $this->logger->warning(
                    'Erreur lors de la création du hashtag: ' . $hashtagName,
                    [
                        'exception'    => $e,
                        'hashtag_name' => $hashtagName,
                    ]
                );

                $hashtag = $this->entityManager->getRepository(Hashtag::class)->findOneBy(
                    ['name' => strtolower(ltrim($hashtagName, '#'))]
                );
                if ($hashtag) {
                    $post->addHashtag($hashtag);
                }
            }
        }
    }

    private function processPostMentions(Post $post, string $content): void
    {
        $mentions = $this->contentProcessor->extractMentions($content);
        foreach ($mentions as $mention) {
            try {
                $mentionedUser = $this->findUserByMention($mention);
                if ($mentionedUser) {
                    $post->addMention($mentionedUser);
                }
            } catch (\Doctrine\ORM\NonUniqueResultException $e) {
                // Plusieurs utilisateurs trouvés avec le même nom, on log l'erreur et on continue
                $this->logger->warning('Plusieurs utilisateurs trouvés avec le nom: ' . $mention, [
                    'exception' => $e,
                    'mention'   => $mention,
                ]);

                $mentionedUser = $this->findUserByMention($mention, true);
                if ($mentionedUser) {
                    $post->addMention($mentionedUser);
                }
            }
        }
    }

    private function findUserByMention(string $mention, bool $fallback = false): ?User
    {
        $queryBuilder = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('LOWER(CONCAT(u.firstName, \' \', u.lastName)) = LOWER(:fullName)')
            ->setParameter('fullName', trim($mention))
            ->setMaxResults(1);

        if ($fallback) {
            return $queryBuilder->getQuery()->getResult()[0] ?? null;
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function clearPostHashtags(Post $post): void
    {
        foreach ($post->getHashtags()->toArray() as $hashtag) {
            $post->removeHashtag($hashtag);
        }
    }

    private function updatePostMentions(Post $post, string $content): void
    {
        $post->setMentions([]);
        $mentions = $this->contentProcessor->extractMentions($content);
        foreach ($mentions as $mention) {
            try {
                $mentionedUser = $this->findUserByMention($mention);
                if ($mentionedUser) {
                    $post->addMention($mentionedUser);
                }
            } catch (\Doctrine\ORM\NonUniqueResultException $e) {
                // Plusieurs utilisateurs trouvés avec le même nom, on log l'erreur et on continue
                $this->logger->warning('Plusieurs utilisateurs trouvés avec le nom: ' . $mention, [
                    'exception' => $e,
                    'mention'   => $mention,
                ]);

                $mentionedUser = $this->findUserByMention($mention, true);
                if ($mentionedUser) {
                    $post->addMention($mentionedUser);
                }
            }
        }
    }
}
