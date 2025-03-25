<?php

namespace App\Repository;

use App\Entity\Hashtag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
    public function __construct(ManagerRegistry $registry)
    {
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
     * Recherche les hashtags les plus populaires
     */
    public function findTrending(int $limit = 10): array
    {
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
}
