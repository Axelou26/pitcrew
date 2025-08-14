<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Applicant;
use App\Entity\Application;
use App\Entity\JobOffer;
use App\Entity\Recruiter;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 *
 * @method null|Application find($id, $lockMode = null, $lockVersion = null)
 * @method null|Application findOneBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null
 * )
 * @method Application[]    findAll()
 * @method Application[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
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
    public function findByRecruiter(Recruiter $recruiter): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.jobOffer', 'jo')
            ->where('jo.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('a.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les candidatures d'un recruteur.
     *
     * @return Application[]
     */
    public function findApplicationsByRecruiter(User $recruiter): array
    {
        $jobOfferId = $recruiter->getJobOffer()?->getId();
        if ($jobOfferId === null) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.jobOffer = :jobOfferId')
            ->setParameter('jobOfferId', $jobOfferId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Application[] Returns an array of Application objects for a user
     */
    public function findByUser(User $user): array
    {
        // Si l'utilisateur est déjà un Applicant, l'utiliser directement
        if ($user instanceof Applicant) {
            return $this->createQueryBuilder('a')
                ->where('a.applicant = :applicant')
                ->setParameter('applicant', $user)
                ->orderBy('a.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // Sinon, vérifier si un Applicant avec le même ID existe
        // Cela fonctionne parce qu'Applicant hérite de User et partage la même table et ID
        $applicant = $this->getEntityManager()
            ->getRepository(Applicant::class)
            ->find($user->getId());

        if (!$applicant) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->where('a.applicant = :applicant')
            ->setParameter('applicant', $applicant)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les candidatures pour une offre d'emploi.
     *
     * @return Application[]
     */
    public function findApplicationsByJobOffer(JobOffer $jobOffer): array
    {
        $jobOfferId = $jobOffer->getId();
        if ($jobOfferId === null) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.jobOffer = :jobOfferId')
            ->setParameter('jobOfferId', $jobOfferId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
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
     * Récupère les statistiques des candidatures.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(Recruiter $recruiter): array
    {
        $queryBuilder = $this->createQueryBuilder('a')
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

        return $queryBuilder->getSingleResult();
    }

    /**
     * @return Application[] Returns an array of recent applications
     */
    public function findRecentApplications(User $recruiter, int $days = 7): array
    {
        $date = new DateTimeImmutable();
        $date = $date->modify('-' . $days . ' days');

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

    /**
     * Compte les candidatures pour un recruteur.
     */
    public function countForRecruiter(User $recruiter): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.jobOffer', 'jo')
            ->where('jo.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findApplicationsByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->select('a', 'j')
            ->leftJoin('a.jobOffer', 'j')
            ->where('a.applicant = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de candidatures pour une offre d'emploi.
     */
    public function countByJobOffer(JobOffer $jobOffer): int
    {
        if ($jobOffer->getId() === null) {
            return 0;
        }

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.jobOffer = :jobOfferId')
            ->setParameter('jobOfferId', $jobOffer->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
