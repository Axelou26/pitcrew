<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Hashtag;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\Trait\FlushTrait;
use App\Repository\Trait\PostQueryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method null|Post find($id, $lockMode = null, $lockVersion = null)
 * @method null|Post findOneBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null
 * )
 * @method Post[]    findAll()
 * @method Post[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class PostRepository extends ServiceEntityRepository
{
    /** @use FlushTrait<Post> */
    use FlushTrait;
    use PostQueryTrait;

    private const CACHE_TTL                     = 3600; // 1 heure
    private const CACHE_KEY_RECENT_POSTS        = 'recent_posts_%d';
    private const CACHE_KEY_ALL_POSTS           = 'all_posts';
    private const CACHE_KEY_POSTS_BY_HASHTAG    = 'posts_by_hashtag_%d';
    private const CACHE_KEY_POSTS_BY_MENTION    = 'posts_by_mention_%d';
    private const CACHE_KEY_SEARCH_POSTS        = 'search_posts_%s';
    private const CACHE_KEY_FEED_POSTS          = 'feed_posts';
    private const CACHE_KEY_RECENT_WITH_AUTHORS = 'recent_with_authors_%d';

    public function __construct(
        ManagerRegistry $registry,
        private CacheInterface $cache
    ) {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return array<Post>
     */
    public function findRecentPosts(int $limit = 10): array
    {
        return $this->getCachedResult(
            \sprintf(self::CACHE_KEY_RECENT_POSTS, $limit),
            fn (): array => $this->createBasePostQuery()
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult()
        );
    }

    /**
     * @return array<Post>
     */
    public function findAllOrderedByDate(): array
    {
        return $this->getCachedResult(
            self::CACHE_KEY_ALL_POSTS,
            fn (): array => $this->createBasePostQuery()
                ->getQuery()
                ->getResult()
        );
    }

    /**
     * @return array<Post>
     */
    public function findByHashtag(Hashtag $hashtag): array
    {
        return $this->getCachedResult(
            \sprintf(self::CACHE_KEY_POSTS_BY_HASHTAG, $hashtag->getId()),
            function () use ($hashtag): array {
                $qb = $this->createQueryBuilder('p')
                    ->select('p', 'a', 'c', 'h', 'o')
                    ->leftJoin('p.author', 'a')
                    ->leftJoin('p.comments', 'c')
                    ->innerJoin('p.hashtags', 'h')
                    ->leftJoin('p.originalPost', 'o')
                    ->where('h = :hashtag')
                    ->setParameter('hashtag', $hashtag)
                    ->orderBy('p.createdAt', 'DESC');

                return $qb->getQuery()->getResult();
            }
        );
    }

    /**
     * @return array<Post>
     */
    public function findByMentionedUser(User $user): array
    {
        return $this->getCachedResult(
            \sprintf(self::CACHE_KEY_POSTS_BY_MENTION, $user->getId()),
            fn (): array => $this->createBasePostQuery()
                ->where('JSON_CONTAINS(p.mentions, :userId) = 1')
                ->setParameter('userId', $user->getId())
                ->getQuery()
                ->getResult()
        );
    }

    /**
     * @return array<Post>
     */
    public function search(string $query): array
    {
        return $this->getCachedResult(
            \sprintf(self::CACHE_KEY_SEARCH_POSTS, md5($query)),
            fn (): array => $this->createBasePostQuery()
                ->where('p.content LIKE :query OR p.title LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(50)
                ->getQuery()
                ->getResult()
        );
    }

    /**
     * @return array<Post>
     */
    public function findPostsForFeed(User $user, int $page = 1, int $limit = 10): array
    {
        $firstResult = ($page - 1) * $limit;
        $friendships = $user->getFriendships();
        $friends     = $friendships->map(fn ($friendship) => $friendship->getOtherUser($user))->toArray();

        return $this->getCachedResult(
            \sprintf(self::CACHE_KEY_FEED_POSTS . '_%d_%d', $user->getId(), $page),
            function () use ($user, $friends, $firstResult, $limit): array {
                $qb = $this->createBasePostQuery();

                if (empty($friends)) {
                    // Si l'utilisateur n'a pas d'amis, afficher seulement ses propres posts
                    $qb->where('p.author = :user')
                        ->setParameter('user', $user);

                    return $qb->orderBy('p.createdAt', 'DESC')
                        ->setFirstResult($firstResult)
                        ->setMaxResults($limit)
                        ->getQuery()
                        ->getResult();
                }

                // Sinon, afficher ses posts et ceux de ses amis
                $qb->where('p.author = :user')
                    ->orWhere('p.author IN (:friends)')
                    ->setParameter('user', $user)
                    ->setParameter('friends', $friends);

                return $qb->orderBy('p.createdAt', 'DESC')
                    ->setFirstResult($firstResult)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
            }
        );
    }

    /**
     * Compte le nombre de posts qui utilisent un hashtag spécifique.
     */
    public function countByHashtag(Hashtag $hashtag): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->innerJoin('p.hashtags', 'h')
            ->where('h.id = :hashtagId')
            ->setParameter('hashtagId', $hashtag->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<Post>
     */
    public function findRecentPostsWithHashtags(\DateTimeInterface $fromDate): array
    {
        return $this->createBasePostQuery()
            ->where('p.createdAt >= :fromDate')
            ->andWhere('p.hashtags IS NOT EMPTY')
            ->setParameter('fromDate', $fromDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les posts récents avec leurs auteurs.
     *
     * @return array<Post>
     */
    public function findRecentWithAuthors(int $limit = 10): array
    {
        $cacheKey = \sprintf(self::CACHE_KEY_RECENT_WITH_AUTHORS, $limit);

        return $this->cache->get($cacheKey, function () use ($limit) {
            return $this->createQueryBuilder('p')
                ->select('p', 'a', 'c', 'l')
                ->leftJoin('p.author', 'a')
                ->leftJoin('p.comments', 'c')
                ->leftJoin('p.likes', 'l')
                ->orderBy('p.createdAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        });
    }

    public function findByHashtags(array $hashtags, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'a', 'h')
            ->leftJoin('p.author', 'a')
            ->leftJoin('p.hashtags', 'h')
            ->where('h.name IN (:hashtags)')
            ->setParameter('hashtags', $hashtags)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les posts depuis une date donnée.
     *
     * @return Post[]
     */
    public function findPostsSince(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Post>
     */
    public function findTrendingPosts(int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, COUNT(pl.id) as likeCount')
            ->leftJoin('p.likes', 'pl')
            ->groupBy('p.id')
            ->orderBy('likeCount', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère un résultat depuis le cache ou l'enregistre s'il n'existe pas.
     */
    private function getCachedResult(string $key, callable $callback): mixed
    {
        try {
            return $this->cache->get($key, function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(self::CACHE_TTL);

                return $callback();
            });
        } catch (\Exception $e) {
            // En cas d'erreur de cache, exécuter directement la callback
            return $callback();
        }
    }
}
