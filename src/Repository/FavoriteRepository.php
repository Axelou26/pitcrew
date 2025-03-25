<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\JobOffer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 *
 * @method Favorite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Favorite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Favorite[]    findAll()
 * @method Favorite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * Récupère toutes les offres favorites d'un utilisateur
     */
    public function findFavoriteJobOffers(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', Favorite::TYPE_JOB_OFFER)
            ->join('f.jobOffer', 'j')
            ->addSelect('j')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les candidats favoris d'un recruteur
     */
    public function findFavoriteCandidates(User $recruiter): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.type = :type')
            ->setParameter('user', $recruiter)
            ->setParameter('type', Favorite::TYPE_CANDIDATE)
            ->join('f.candidate', 'c')
            ->addSelect('c')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une offre est dans les favoris d'un utilisateur
     */
    public function isJobOfferFavorite(User $user, JobOffer $jobOffer): bool
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.user = :user')
            ->andWhere('f.jobOffer = :jobOffer')
            ->andWhere('f.type = :type')
            ->setParameter('user', $user)
            ->setParameter('jobOffer', $jobOffer)
            ->setParameter('type', Favorite::TYPE_JOB_OFFER)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Vérifie si un candidat est dans les favoris d'un recruteur
     */
    public function isCandidateFavorite(User $recruiter, User $candidate): bool
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.user = :recruiter')
            ->andWhere('f.candidate = :candidate')
            ->andWhere('f.type = :type')
            ->setParameter('recruiter', $recruiter)
            ->setParameter('candidate', $candidate)
            ->setParameter('type', Favorite::TYPE_CANDIDATE)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    //    /**
    //     * @return Favorite[] Returns an array of Favorite objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Favorite
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
