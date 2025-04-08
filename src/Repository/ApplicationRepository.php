<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 *
 * @method Application|null find($id, $lockMode = null, $lockVersion = null)
 * @method Application|null findOneBy(array $criteria, array $orderBy = null)
 * @method Application[]    findAll()
 * @method Application[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    /**
     * @return Application[] Returns an array of Application objects for a recruiter
     */
    public function findByRecruiter(User $recruiter): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.jobOffer', 'j')
            ->where('j.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array Returns an array of applications grouped by job offer
     */
    public function findApplicationsByRecruiter(User $recruiter): array
    {
        $applications = $this->findByRecruiter($recruiter);
        $grouped = [];

        foreach ($applications as $application) {
            $jobOffer = $application->getJobOffer();
            if (!isset($grouped[$jobOffer->getId()])) {
                $grouped[$jobOffer->getId()] = [
                    'jobOffer' => $jobOffer,
                    'applications' => []
                ];
            }
            $grouped[$jobOffer->getId()]['applications'][] = $application;
        }

        return $grouped;
    }

    /**
     * @return Application[] Returns an array of pending applications
     */
    public function findPendingApplications(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array Returns statistics about applications
     */
    public function getStatistics(User $recruiter): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id) as total')
            ->addSelect('SUM(CASE WHEN a.status = :pending THEN 1 ELSE 0 END) as pending')
            ->addSelect('SUM(CASE WHEN a.status = :accepted THEN 1 ELSE 0 END) as accepted')
            ->addSelect('SUM(CASE WHEN a.status = :rejected THEN 1 ELSE 0 END) as rejected')
            ->join('a.jobOffer', 'j')
            ->where('j.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->setParameter('pending', 'pending')
            ->setParameter('accepted', 'accepted')
            ->setParameter('rejected', 'rejected')
            ->getQuery();

        return $qb->getSingleResult();
    }

    /**
     * @return Application[] Returns an array of recent applications
     */
    public function findRecentApplications(User $recruiter, int $days = 7): array
    {
        $date = new \DateTime();
        $date->modify('-' . $days . ' days');

        return $this->createQueryBuilder('a')
            ->join('a.jobOffer', 'j')
            ->where('j.recruiter = :recruiter')
            ->andWhere('a.createdAt >= :date')
            ->setParameter('recruiter', $recruiter)
            ->setParameter('date', $date)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
