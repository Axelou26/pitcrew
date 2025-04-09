<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JobOffer;
use App\Entity\User;
use App\Repository\Trait\FlushTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use DateTime;

/**
 * @extends ServiceEntityRepository<JobOffer>
 *
 * @method JobOffer|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobOffer|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobOffer[]    findAll()
 * @method JobOffer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobOfferRepository extends ServiceEntityRepository
{
    use FlushTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOffer::class);
    }

    /**
     * @return JobOffer[]
     */
    public function findActiveOffers(int $limit = 10): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.isActive = :active')
            ->andWhere('j.isPublished = :published')
            ->setParameter('active', true)
            ->setParameter('published', true)
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return JobOffer[]
     */
    public function findByRecruiter(User $recruiter): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, JobOffer>
     */
    public function searchOffers(?string $query = null, array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilder('j')
            ->where('j.expiresAt > :now OR j.expiresAt IS NULL')
            ->andWhere('j.isActive = :active')
            ->setParameter('now', new DateTime())
            ->setParameter('active', true);

        if ($query) {
            $queryBuilder->andWhere('j.title LIKE :query OR j.description LIKE :query OR j.company LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if (!empty($filters['location'])) {
            $queryBuilder->andWhere('j.location LIKE :location')
                ->setParameter('location', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['contractType'])) {
            $queryBuilder->andWhere('j.contractType = :contractType')
                ->setParameter('contractType', $filters['contractType']);
        }

        if (!empty($filters['experienceLevel'])) {
            $queryBuilder->andWhere('j.experienceLevel = :experienceLevel')
                ->setParameter('experienceLevel', $filters['experienceLevel']);
        }

        if (!empty($filters['minSalary'])) {
            $queryBuilder->andWhere('j.salary >= :minSalary')
                ->setParameter('minSalary', $filters['minSalary']);
        }

        return $queryBuilder->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, JobOffer>
     */
    public function findSimilarOffers(JobOffer $jobOffer, int $limit = 3): array
    {
        $queryBuilder = $this->createQueryBuilder('j')
            ->where('j.id != :currentId')
            ->andWhere('j.isActive = :active')
            ->andWhere('j.expiresAt > :now OR j.expiresAt IS NULL');

        $parameters = [
            'currentId' => $jobOffer->getId(),
            'active' => true,
            'now' => new DateTime()
        ];

        $conditions = $this->buildSimilarityConditions($jobOffer, $parameters);
        $scoreExpr = implode(' + ', $conditions);

        $queryBuilder->select('j, (' . $scoreExpr . ') as HIDDEN score')
           ->setParameters($parameters)
           ->orderBy('score', 'DESC')
           ->addOrderBy('j.createdAt', 'DESC')
           ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed> $parameters
     * @return string[]
     */
    private function buildSimilarityConditions(JobOffer $jobOffer, array &$parameters): array
    {
        $conditions = [];

        if ($jobOffer->getContractType()) {
            $conditions[] = 'CASE WHEN j.contractType = :contractType THEN 2 ELSE 0 END';
            $parameters['contractType'] = $jobOffer->getContractType();
        }

        if ($jobOffer->getLocation()) {
            $conditions[] = 'CASE WHEN j.location = :location THEN 2 ELSE 0 END';
            $parameters['location'] = $jobOffer->getLocation();
        }

        if ($jobOffer->getSalary()) {
            $conditions[] = 'CASE WHEN ABS(j.salary - :salary) <= 5000 THEN 1 ELSE 0 END';
            $parameters['salary'] = $jobOffer->getSalary();
        }

        if ($jobOffer->getTitle() && $jobOffer->getDescription()) {
            $this->addKeywordConditions($jobOffer, $conditions, $parameters);
        }

        return empty($conditions) ? ['0'] : $conditions;
    }

    /**
     * @param string[] $conditions
     * @param array<string, mixed> $parameters
     */
    private function addKeywordConditions(JobOffer $jobOffer, array &$conditions, array &$parameters): void
    {
        $titleWords = explode(' ', strtolower($jobOffer->getTitle()));
        $descriptionWords = explode(' ', strtolower($jobOffer->getDescription()));
        $keywords = array_unique(array_merge($titleWords, $descriptionWords));

        foreach ($keywords as $index => $keyword) {
            if (strlen($keyword) > 3) {
                $conditions[] =
                    "CASE WHEN LOWER(j.title) LIKE :keyword{$index} OR " .
                    "LOWER(j.description) LIKE :keyword{$index} THEN 3 ELSE 0 END";
                $parameters["keyword{$index}"] = '%' . strtolower($keyword) . '%';
            }
        }
    }

    /**
     * @return array<int, JobOffer>
     */
    public function findExpiredOffers(): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.expiresAt <= :now')
            ->andWhere('j.expiresAt IS NOT NULL')
            ->setParameter('now', new DateTime())
            ->orderBy('j.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws QueryException
     */
    public function countActiveOffers(): int
    {
        $result = $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.isActive = :active')
            ->andWhere('j.expiresAt > :now OR j.expiresAt IS NULL')
            ->setParameter('active', true)
            ->setParameter('now', new DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function countJobOffersByUserAndDateRange(User $user, DateTime $startDate, DateTime $endDate): int
    {
        $result = $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.recruiter = :user')
            ->andWhere('j.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}
