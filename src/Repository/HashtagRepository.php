<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Hashtag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<Hashtag>
 *
 * @method null|Hashtag find($id, $lockMode = null, $lockVersion = null)
 * @method null|Hashtag findOneBy(array<string, mixed> $criteria, array<string, string> $orderBy = null)
 * @method Hashtag[]    findAll()
 * @method Hashtag[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class HashtagRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private CacheInterface $cache
    ) {
        parent::__construct($registry, Hashtag::class);
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

    public function getTrendingHashtags(int $limit = 10): array
    {
        $cacheKey = 'trending_hashtags_' . $limit;

        return $this->cache !== null
            ? $this->cache->get($cacheKey, function (ItemInterface $item) use ($limit) {
                $item->expiresAfter(3600); // Cache pour 1 heure

                return $this->fetchTrendingHashtags($limit);
            })
            : $this->fetchTrendingHashtags($limit);
    }

    public function findOrCreate(string $name): Hashtag
    {
        // Normaliser le nom du hashtag
        $name = ltrim($name, '#');
        $name = strtolower($name);

        // D'abord, essayer de trouver le hashtag existant
        $hashtag = $this->findOneBy(['name' => $name]);

        if ($hashtag) {
            return $hashtag;
        }

        // Si le hashtag n'existe pas, essayer de le créer avec gestion d'erreur
        try {
            $hashtag = new Hashtag();
            $hashtag->setName($name);
            $this->_em->persist($hashtag);

            // Ne pas flusher ici, laisser le service principal le faire
            return $hashtag;
        } catch (\Exception $e) {
            // En cas d'erreur, essayer de récupérer le hashtag qui pourrait avoir été créé entre temps
            $this->_em->clear();
            $hashtag = $this->findOneBy(['name' => $name]);

            if ($hashtag) {
                return $hashtag;
            }

            // Si on ne trouve toujours pas le hashtag, relancer l'exception
            throw $e;
        }
    }

    /**
     * Trouve des suggestions de hashtags basées sur une recherche.
     *
     * @param string $query Le terme de recherche
     * @param int $limit Nombre maximum de résultats
     *
     * @return array<Hashtag>
     */
    public function findSuggestions(string $query, int $limit = 5): array
    {
        // Nettoyer et normaliser la requête
        $query = trim(ltrim($query, '#'));
        if (empty($query)) {
            return [];
        }

        try {
            // Clé de cache unique pour cette requête
            $cacheKey = 'hashtag_suggestions_' . strtolower($query);

            // Vérifier si les résultats sont déjà en cache
            $cachedResults = $this->cache->get($cacheKey, function () use ($query, $limit) {
                $qb = $this->createQueryBuilder('h')
                    ->where('LOWER(h.name) LIKE LOWER(:query)')
                    ->setParameter('query', $query . '%')
                    ->orderBy('h.usageCount', 'DESC')
                    ->addOrderBy('h.name', 'ASC')
                    ->setMaxResults($limit);

                // Ajouter un index hint pour utiliser l'index sur name si disponible
                $query = $qb->getQuery();
                $query->setHint('doctrine.query.hint_force_partial_load', true);

                return $query->getResult();
            });

            return $cachedResults;
        } catch (\Exception $e) {
            // En cas d'erreur avec le cache, exécuter la requête directement
            $qb = $this->createQueryBuilder('h')
                ->where('LOWER(h.name) LIKE LOWER(:query)')
                ->setParameter('query', $query . '%')
                ->orderBy('h.usageCount', 'DESC')
                ->addOrderBy('h.name', 'ASC')
                ->setMaxResults($limit);

            return $qb->getQuery()->getResult();
        }
    }

    /**
     * Trouve les hashtags les plus utilisés.
     *
     * @param int $limit Nombre maximum de résultats
     *
     * @return array<Hashtag>
     */
    public function findTrending(int $limit = 10): array
    {
        // Essayer de récupérer les hashtags depuis le cache
        $hashtagIds = $this->cache->get('trending_hashtags', function () {
            return [];
        });

        if ($hashtagIds !== null) {
            // Si présent en cache, récupérer les IDs et charger les hashtags

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
                    return \array_slice($orderedHashtags, 0, $limit);
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
     * Recherche les hashtags par leur nom (pour l'autocomplétion).
     *
     * @return Hashtag[]
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
     * Trouve les hashtags récents.
     *
     * @return Hashtag[]
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
     * Réinitialise tous les compteurs d'utilisation à 0.
     */
    public function resetAllUsageCounts(): void
    {
        $this->createQueryBuilder('h')
            ->update()
            ->set('h.usageCount', 0)
            ->getQuery()
            ->execute();
    }

    private function fetchTrendingHashtags(int $limit): array
    {
        return $this->createQueryBuilder('h')
            ->select('h.name, COUNT(p.id) as postCount')
            ->leftJoin('h.posts', 'p')
            ->groupBy('h.id, h.name')
            ->orderBy('postCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
