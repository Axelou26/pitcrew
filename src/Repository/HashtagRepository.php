<?php

namespace App\Repository;

use App\Entity\Hashtag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @extends ServiceEntityRepository<Hashtag>
 *
 * @method Hashtag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hashtag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hashtag[]    findAll()
 * @method Hashtag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HashtagRepository extends ServiceEntityRepository
{
    private $cache;

    public function __construct(ManagerRegistry $registry, CacheItemPoolInterface $cache)
    {
        parent::__construct($registry, Hashtag::class);
        $this->cache = $cache;
    }

    //    /**
    //     * @return Hashtag[] Returns an array of Hashtag objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('h.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Hashtag
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Trouve un hashtag par son nom ou en crée un nouveau
     */
    public function findOrCreate(string $name): Hashtag
    {
        // Normaliser le nom du hashtag
        $name = ltrim($name, '#');
        $name = strtolower($name);

        $hashtag = $this->findOneBy(['name' => $name]);

        if (!$hashtag) {
            $hashtag = new Hashtag();
            $hashtag->setName($name);
            $this->getEntityManager()->persist($hashtag);
        }

        return $hashtag;
    }

    /**
     * Trouve des suggestions de hashtags basées sur une recherche
     *
     * @param string $query Le terme de recherche
     * @param int $limit Nombre maximum de résultats
     * @return array<Hashtag>
     */
    public function findSuggestions(string $query, int $limit = 5): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.name LIKE :query')
            ->setParameter('query', $query . '%')
            ->orderBy('h.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les hashtags les plus utilisés
     *
     * @param int $limit Nombre maximum de résultats
     * @return array<Hashtag>
     */
    public function findTrending(int $limit = 10): array
    {
        // Essayer de récupérer les hashtags depuis le cache
        $cacheItem = $this->cache->getItem('trending_hashtags');

        if ($cacheItem->isHit()) {
            // Si présent en cache, récupérer les IDs et charger les hashtags
            $hashtagIds = $cacheItem->get();

            if (!empty($hashtagIds)) {
                $hashtags = $this->createQueryBuilder('h')
                    ->where('h.id IN (:hashtagIds)')
                    ->setParameter('hashtagIds', $hashtagIds)
                    ->getQuery()
                    ->getResult();

                // Réorganiser dans le même ordre que les IDs
                $hashtagsMap = [];
                foreach ($hashtags as $hashtag) {
                    $hashtagsMap[$hashtag->getId()] = $hashtag;
                }

                $orderedHashtags = [];
                foreach ($hashtagIds as $id) {
                    if (isset($hashtagsMap[$id])) {
                        $orderedHashtags[] = $hashtagsMap[$id];
                    }
                }

                // Si on a des hashtags, les retourner
                if (!empty($orderedHashtags)) {
                    return array_slice($orderedHashtags, 0, $limit);
                }
            }
        }

        // Sinon, faire la requête normale
        return $this->createQueryBuilder('h')
            ->orderBy('h.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche les hashtags par leur nom (pour l'autocomplétion)
     */
    public function searchByName(string $query, int $limit = 5): array
    {
        $query = ltrim($query, '#');

        return $this->createQueryBuilder('h')
            ->where('h.name LIKE :query')
            ->setParameter('query', $query . '%')
            ->orderBy('h.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche les hashtags récemment utilisés
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.lastUsedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Réinitialise tous les compteurs d'utilisation à 0
     */
    public function resetAllUsageCounts(): void
    {
        $this->createQueryBuilder('h')
            ->update()
            ->set('h.usageCount', 0)
            ->getQuery()
            ->execute();
    }
}
