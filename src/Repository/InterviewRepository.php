<?php

namespace App\Repository;

use App\Entity\Interview;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

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
        $queryBuilder = $this->createQueryBuilder('i')
            ->where('i.scheduledAt > :now')
            ->andWhere('i.status = :status')
            ->setParameter('now', new DateTime())
            ->setParameter('status', 'scheduled')
            ->orderBy('i.scheduledAt', 'ASC');

        $userRoleField = in_array('ROLE_RECRUTEUR', $user->getRoles()) ? 'recruiter' : 'applicant';
        $queryBuilder->andWhere('i.' . $userRoleField . ' = :user')
           ->setParameter('user', $user);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Trouve les entretiens passés pour un utilisateur (recruteur ou candidat)
     */
    public function findPastInterviewsForUser(User $user): array
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->where('i.scheduledAt < :now')
            ->setParameter('now', new DateTime())
            ->orderBy('i.scheduledAt', 'DESC');

        $userRoleField = in_array('ROLE_RECRUTEUR', $user->getRoles()) ? 'recruiter' : 'applicant';
        $queryBuilder->andWhere('i.' . $userRoleField . ' = :user')
            ->setParameter('user', $user);

        return $queryBuilder->getQuery()->getResult();
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
    public function hasScheduleConflict(
        User $user,
        DateTime $startTime,
        DateTime $endTime,
        ?int $excludeInterviewId = null
    ) {
        $queryBuilder = $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->andWhere('i.scheduledAt >= :startTime')
            ->andWhere('i.scheduledAt < :endTime')
            ->setParameter('status', 'scheduled')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        $userRoleField = in_array('ROLE_RECRUTEUR', $user->getRoles()) ? 'recruiter' : 'applicant';
        $queryBuilder->andWhere('i.' . $userRoleField . ' = :user')
           ->setParameter('user', $user);

        if ($excludeInterviewId) {
            $queryBuilder->andWhere('i.id != :excludeId')
                ->setParameter('excludeId', $excludeInterviewId);
        }

        return count($queryBuilder->getQuery()->getResult()) > 0;
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
