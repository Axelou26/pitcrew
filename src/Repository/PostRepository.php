<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Hashtag;
use App\Entity\User;
use App\Repository\Trait\PostQueryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Item\ItemInterface;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    use PostQueryTrait;

    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_KEY_RECENT_POSTS = 'recent_posts_%d';
    private const CACHE_KEY_ALL_POSTS = 'all_posts_ordered';
    private const CACHE_KEY_POSTS_BY_HASHTAG = 'posts_by_hashtag_%d';
    private const CACHE_KEY_POSTS_BY_MENTION = 'posts_mentioned_%d';
    private const CACHE_KEY_SEARCH_POSTS = 'search_posts_%s';
    private const CACHE_KEY_FEED_POSTS = 'feed_posts_%d';
    private const CACHE_KEY_RECENT_WITH_AUTHORS = 'recent_posts_with_authors_%d';

    private AdapterInterface $cache;

    public function __construct(ManagerRegistry $registry, AdapterInterface $cache)
    {
        parent::__construct($registry, Post::class);
        $this->cache = $cache;
    }

    public function save(Post $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Post $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère un résultat depuis le cache ou l'enregistre s'il n'existe pas
     */
    private function getCachedResult(string $key, callable $callback): mixed
    {
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            $item->set($callback());
            $item->expiresAfter(self::CACHE_TTL);
            $this->cache->save($item);
        }

        return $item->get();
    }

    /**
     * @return array<Post>
     */
    public function findRecentPosts(int $limit = 10): array
    {
        return $this->getCachedResult(
            sprintf(self::CACHE_KEY_RECENT_POSTS, $limit),
            fn(): array => $this->createBasePostQuery()
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
            fn(): array => $this->createBasePostQuery()
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
            sprintf(self::CACHE_KEY_POSTS_BY_HASHTAG, $hashtag->getId()),
            fn(): array => $this->createQueryBuilder('p')
                ->select('p', 'a', 'l', 'c', 'h', 's')
                ->leftJoin('p.author', 'a')
                ->leftJoin('p.likes', 'l')
                ->leftJoin('p.comments', 'c')
                ->innerJoin('p.hashtags', 'h')
                ->leftJoin('p.shares', 's')
                ->where('h = :hashtag')
                ->setParameter('hashtag', $hashtag)
                ->orderBy('p.createdAt', 'DESC')
                ->getQuery()
                ->getResult()
        );
    }

    /**
     * @return array<Post>
     */
    public function findByMentionedUser(User $user): array
    {
        return $this->getCachedResult(
            sprintf(self::CACHE_KEY_POSTS_BY_MENTION, $user->getId()),
            fn(): array => $this->createBasePostQuery()
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
            sprintf(self::CACHE_KEY_SEARCH_POSTS, md5($query)),
            fn(): array => $this->createBasePostQuery()
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
    public function findPostsForFeed(User $user): array
    {
        return $this->getCachedResult(
            sprintf(self::CACHE_KEY_FEED_POSTS, $user->getId()),
            fn(): array => $this->createBasePostQuery()
                ->where('p.author = :user')
                ->orWhere('p.author IN (:friends)')
                ->setParameter('user', $user)
                ->setParameter('friends', $user->getFriends())
                ->getQuery()
                ->getResult()
        );
    }

    /**
     * Compte le nombre de posts qui utilisent un hashtag spécifique
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
     * Récupère les posts récents avec leurs auteurs
     * @return array<Post>
     */
    public function findRecentWithAuthors(int $limit): array
    {
        return $this
            ->getCachedResult(sprintf(self::CACHE_KEY_RECENT_WITH_AUTHORS, $limit), function () use ($limit): array {
                $qb = $this->createQueryBuilder('p')
                ->select('p', 'a', 'l', 'c', 'h', 's')
                ->leftJoin('p.author', 'a')
                ->leftJoin('p.likes', 'l')
                ->leftJoin('p.comments', 'c')
                ->leftJoin('p.hashtags', 'h')
                ->leftJoin('p.shares', 's')
                ->orderBy('p.createdAt', 'DESC')
                ->setMaxResults($limit);

                return $qb->getQuery()->getResult();
            });
    }
}
