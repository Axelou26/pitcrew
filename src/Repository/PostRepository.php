<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Hashtag;
use App\Entity\User;

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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
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
     * @return Post[] Returns an array of Post objects
     */
    public function findRecentPosts(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'author')
            ->leftJoin('p.shares', 'shares')
            ->addSelect('author')
            ->addSelect('shares')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Post[] Returns an array of Post objects ordered by creation date
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'author')
            ->leftJoin('p.likes', 'likes')
            ->leftJoin('p.comments', 'comments')
            ->leftJoin('p.shares', 'shares')
            ->leftJoin('p.hashtags', 'hashtags')
            ->addSelect('author')
            ->addSelect('likes')
            ->addSelect('comments')
            ->addSelect('shares')
            ->addSelect('hashtags')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche les posts contenant un hashtag spécifique
     */
    public function findByHashtag(Hashtag $hashtag): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'author')
            ->leftJoin('p.likes', 'likes')
            ->leftJoin('p.comments', 'comments')
            ->leftJoin('p.shares', 'shares')
            ->leftJoin('p.hashtags', 'hashtags')
            ->addSelect('author')
            ->addSelect('likes')
            ->addSelect('comments')
            ->addSelect('shares')
            ->addSelect('hashtags')
            ->andWhere(':hashtag MEMBER OF p.hashtags')
            ->setParameter('hashtag', $hashtag)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche les posts mentionnant un utilisateur spécifique
     */
    public function findByMentionedUser(User $user): array
    {
        $userId = $user->getId();
        
        return $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'author')
            ->andWhere('JSON_CONTAINS(p.mentions, :userId) = 1')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche les posts avec texte libre
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'author')
            ->andWhere('p.title LIKE :query OR p.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les posts à afficher dans le fil d'actualité d'un utilisateur
     */
    public function findPostsForFeed(User $user, int $limit = 20): array
    {
        // Implémentation basique : posts récents + posts des amis
        // Une implémentation plus avancée prendrait en compte les centres d'intérêt de l'utilisateur
        
        // Récupérer les IDs des amis
        $friendIds = [];
        
        // Récupérer les posts des amis et les posts récents
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'author')
            ->leftJoin('p.likes', 'likes')
            ->leftJoin('p.comments', 'comments')
            ->leftJoin('p.shares', 'shares')
            ->addSelect('author')
            ->addSelect('likes')
            ->addSelect('comments')
            ->addSelect('shares')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);
            
        if (!empty($friendIds)) {
            $qb->andWhere('author.id IN (:friendIds) OR p.createdAt > :recentDate')
               ->setParameter('friendIds', $friendIds)
               ->setParameter('recentDate', new \DateTime('-3 days'));
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de posts qui utilisent un hashtag spécifique
     */
    public function countByHashtag(Hashtag $hashtag): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->innerJoin('p.hashtags', 'h')
            ->where('h.id = :hashtagId')
            ->setParameter('hashtagId', $hashtag->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les posts récents qui contiennent des hashtags (à partir d'une date donnée)
     * 
     * @param \DateTimeInterface $fromDate Date à partir de laquelle chercher
     * @return Post[] Posts trouvés
     */
    public function findRecentPostsWithHashtags(\DateTimeInterface $fromDate): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.hashtags', 'h')
            ->leftJoin('p.author', 'a')
            ->where('p.createdAt >= :fromDate')
            ->andWhere('p.hashtags IS NOT EMPTY')
            ->setParameter('fromDate', $fromDate)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 