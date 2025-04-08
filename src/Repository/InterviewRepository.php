<?php

namespace App\Repository;

use App\Entity\Interview;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interview>
 */
class InterviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interview::class);
    }

    /**
     * Trouve les entretiens à venir pour un utilisateur (recruteur ou candidat)
     */
    public function findUpcomingInterviewsForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.scheduledAt > :now')
            ->andWhere('i.status = :status')
            ->setParameter('now', new \DateTime())
            ->setParameter('status', 'scheduled')
            ->orderBy('i.scheduledAt', 'ASC');

        if (in_array('ROLE_RECRUTEUR', $user->getRoles())) {
            $qb->andWhere('i.recruiter = :user')
                ->setParameter('user', $user);
        } else {
            $qb->andWhere('i.applicant = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les entretiens passés pour un utilisateur (recruteur ou candidat)
     */
    public function findPastInterviewsForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.scheduledAt < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('i.scheduledAt', 'DESC');

        if (in_array('ROLE_RECRUTEUR', $user->getRoles())) {
            $qb->andWhere('i.recruiter = :user')
                ->setParameter('user', $user);
        } else {
            $qb->andWhere('i.applicant = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les entretiens pour une offre d'emploi spécifique
     */
    public function findInterviewsForJobOffer(int $jobOfferId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.jobOffer = :jobOfferId')
            ->setParameter('jobOfferId', $jobOfferId)
            ->orderBy('i.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie les conflits d'horaire pour un utilisateur
     */
    public function hasScheduleConflict(User $user, \DateTime $startTime, \DateTime $endTime, ?int $excludeInterviewId = null): bool
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->andWhere('i.scheduledAt < :endTime')
            ->andWhere('DATE_ADD(i.scheduledAt, 1, \'HOUR\') > :startTime')
            ->setParameter('status', 'scheduled')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        if (in_array('ROLE_RECRUTEUR', $user->getRoles())) {
            $qb->andWhere('i.recruiter = :user')
                ->setParameter('user', $user);
        } else {
            $qb->andWhere('i.applicant = :user')
                ->setParameter('user', $user);
        }

        if ($excludeInterviewId) {
            $qb->andWhere('i.id != :excludeId')
                ->setParameter('excludeId', $excludeInterviewId);
        }

        return count($qb->getQuery()->getResult()) > 0;
    }

    public function save(Interview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Interview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
