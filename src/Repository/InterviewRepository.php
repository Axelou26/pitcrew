<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Interview;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Repository\Trait\FlushTrait;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interview>
 *
 * @method null|Interview find($id, $lockMode = null, $lockVersion = null)
 * @method null|Interview findOneBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null
 * )
 * @method Interview[]    findAll()
 * @method Interview[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class InterviewRepository extends ServiceEntityRepository
{
    /** @use FlushTrait<Interview> */
    use FlushTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interview::class);
    }

    /**
     * Trouve les entretiens à venir pour un utilisateur.
     *
     * @return Interview[]
     */
    public function findUpcomingInterviewsForUser(User $user): array
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->where('i.scheduledAt > :now')
            ->andWhere('i.status = :status')
            ->setParameter('now', new DateTime())
            ->setParameter('status', 'scheduled')
            ->orderBy('i.scheduledAt', 'ASC');

        $userRoleField = \in_array('ROLE_RECRUTEUR', $user->getRoles(), true)
            ? 'recruiter'
            : 'applicant';
        $queryBuilder->andWhere('i.' . $userRoleField . ' = :user')
            ->setParameter('user', $user);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Trouve les entretiens passés pour un utilisateur.
     *
     * @return Interview[]
     */
    public function findPastInterviewsForUser(User $user): array
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->where('i.scheduledAt < :now')
            ->setParameter('now', new DateTime())
            ->orderBy('i.scheduledAt', 'DESC');

        $userRoleField = \in_array('ROLE_RECRUTEUR', $user->getRoles(), true)
            ? 'recruiter'
            : 'applicant';
        $queryBuilder->andWhere('i.' . $userRoleField . ' = :user')
            ->setParameter('user', $user);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Trouve les entretiens pour une offre d'emploi.
     *
     * @return Interview[]
     */
    public function findInterviewsForJobOffer(JobOffer $jobOffer): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.jobOffer = :jobOfferId')
            ->setParameter('jobOfferId', $jobOffer->getId())
            ->orderBy('i.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie s'il y a un conflit d'horaire.
     */
    public function hasScheduleConflict(
        User $user,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        ?Interview $excludeInterview = null
    ): bool {
        $queryBuilder = $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->andWhere('i.scheduledAt >= :startTime')
            ->andWhere('i.scheduledAt < :endTime')
            ->setParameter('status', 'scheduled')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        $userRoleField = \in_array('ROLE_RECRUTEUR', $user->getRoles(), true)
            ? 'recruiter'
            : 'applicant';
        $queryBuilder->andWhere('i.' . $userRoleField . ' = :user')
            ->setParameter('user', $user);

        if ($excludeInterview) {
            $queryBuilder->andWhere('i.id != :excludeId')
                ->setParameter('excludeId', $excludeInterview->getId());
        }

        return \count($queryBuilder->getQuery()->getResult()) > 0;
    }

    public function save(Interview $entity, bool $flush = false): void
    {
        $this->persist($entity);
        if ($flush) {
            $this->flush();
        }
    }

    public function remove(Interview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->flush();
        }
    }
}
