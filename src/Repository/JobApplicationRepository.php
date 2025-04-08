<?php

namespace App\Repository;

use App\Entity\JobApplication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobApplication>
 *
 * @method JobApplication|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobApplication|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobApplication[]    findAll()
 * @method JobApplication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobApplication::class);
    }

    /**
     * @return JobApplication[] Returns an array of JobApplication objects for a recruiter
     */
    public function findByRecruiter(User $recruiter): array
    {
        return $this->createQueryBuilder('ja')
            ->join('ja.jobOffer', 'jo')
            ->where('jo.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('ja.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return JobApplication[] Returns an array of recent JobApplication objects for a recruiter
     */
    public function findRecentApplicationsForRecruiter(User $recruiter, int $limit = 5): array
    {
        return $this->createQueryBuilder('ja')
            ->join('ja.jobOffer', 'jo')
            ->where('jo.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('ja.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return JobApplication[] Returns an array of JobApplication objects for a job offer
     */
    public function findByJobOffer(int $jobOfferId): array
    {
        return $this->createQueryBuilder('ja')
            ->where('ja.jobOffer = :jobOfferId')
            ->setParameter('jobOfferId', $jobOfferId)
            ->orderBy('ja.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int Returns the number of applications for a job offer
     */
    public function countByJobOffer(int $jobOfferId): int
    {
        return $this->createQueryBuilder('ja')
            ->select('COUNT(ja.id)')
            ->where('ja.jobOffer = :jobOfferId')
            ->setParameter('jobOfferId', $jobOfferId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return bool Returns true if the user has already applied to the job offer
     */
    public function hasUserApplied(User $user, int $jobOfferId): bool
    {
        $result = $this->createQueryBuilder('ja')
            ->select('COUNT(ja.id)')
            ->where('ja.applicant = :user')
            ->andWhere('ja.jobOffer = :jobOfferId')
            ->setParameter('user', $user)
            ->setParameter('jobOfferId', $jobOfferId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
