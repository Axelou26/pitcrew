<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JobOffer;
use App\Entity\User;
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
     * @return JobOffer[]
     */
  
    private function applySearchCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (!empty($criteria['location'])) {
            $qb->andWhere('j.location LIKE :location')
                ->setParameter('location', '%' . $criteria['location'] . '%');
        }

        if (!empty($criteria['contractType'])) {
            $qb->andWhere('j.contractType = :contractType')
                ->setParameter('contractType', $criteria['contractType']);
        }

        if (!empty($criteria['experienceLevel'])) {
            $qb->andWhere('j.experienceLevel = :experienceLevel')
                ->setParameter('experienceLevel', $criteria['experienceLevel']);
        }

        if (!empty($criteria['minSalary'])) {
            $qb->andWhere('j.salary >= :minSalary')
                ->setParameter('minSalary', $criteria['minSalary']);
        }

        if (!empty($criteria['keyword'])) {
            $qb->andWhere('j.title LIKE :keyword OR j.description LIKE :keyword OR j.requiredSkills LIKE :keyword')
                ->setParameter('keyword', '%' . $criteria['keyword'] . '%');
        }
    }

    public function save(JobOffer $jobOffer, bool $flush = false): void
    {
        $this->getEntityManager()->persist($jobOffer);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(JobOffer $jobOffer, bool $flush = false): void
    {
        $this->getEntityManager()->remove($jobOffer);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<int, JobOffer>
     */
    public function search(?string $query = null, ?string $location = null, ?string $contractType = null): array
    {
        $queryBuilder = $this->createQueryBuilder('j')
            ->where('j.isActive = :active')
            ->andWhere('j.expiresAt > :now OR j.expiresAt IS NULL')
            ->setParameter('active', true)
            ->setParameter('now', new DateTime());

        if ($query) {
            $queryBuilder->andWhere('j.title LIKE :query OR j.description LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($location) {
            $queryBuilder->andWhere('j.location LIKE :location')
                ->setParameter('location', '%' . $location . '%');
        }

        if ($contractType) {
            $queryBuilder->andWhere('j.contractType = :contractType')
                ->setParameter('contractType', $contractType);
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

        // Paramètres de base
        $parameters = [
            'currentId' => $jobOffer->getId(),
            'active' => true,
            'now' => new DateTime()
        ];

        // Score de similarité basé sur plusieurs critères
        $conditions = [];

        // Même type de contrat (poids: 2)
        if ($jobOffer->getContractType()) {
            $conditions[] = 'CASE WHEN j.contractType = :contractType THEN 2 ELSE 0 END';
            $parameters['contractType'] = $jobOffer->getContractType();
        }

        // Même localisation (poids: 2)
        if ($jobOffer->getLocation()) {
            $conditions[] = 'CASE WHEN j.location = :location THEN 2 ELSE 0 END';
            $parameters['location'] = $jobOffer->getLocation();
        }

        // Salaire similaire (poids: 1)
        if ($jobOffer->getSalary()) {
            $conditions[] = 'CASE WHEN ABS(j.salary - :salary) <= 5000 THEN 1 ELSE 0 END';
            $parameters['salary'] = $jobOffer->getSalary();
        }

        // Compétences similaires (poids: 3)
        if ($jobOffer->getTitle() && $jobOffer->getDescription()) {
            $titleWords = explode(' ', strtolower($jobOffer->getTitle()));
            $descriptionWords = explode(' ', strtolower($jobOffer->getDescription()));
            $keywords = array_unique(array_merge($titleWords, $descriptionWords));

            foreach ($keywords as $index => $keyword) {
                if (strlen($keyword) > 3) { // Ignorer les mots trop courts
                    $conditions[] =
                        "CASE WHEN LOWER(j.title) LIKE :keyword{$index} OR " .
                        "LOWER(j.description) LIKE :keyword{$index} THEN 3 ELSE 0 END";
                    $parameters["keyword{$index}"] = '%' . strtolower($keyword) . '%';
                }
            }
        }

        // Si aucune condition n'est définie, ajouter une condition par défaut
        if (empty($conditions)) {
            $conditions[] = '0';
        }

        // Calculer le score total
        $scoreExpr = implode(' + ', $conditions);

        $queryBuilder->select('j, (' . $scoreExpr . ') as HIDDEN score')
           ->setParameters($parameters)
           ->orderBy('score', 'DESC')
           ->addOrderBy('j.createdAt', 'DESC')
           ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
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

    /**
     * @param array<string, mixed> $filters
     * @return array<int, JobOffer>
     */
    public function searchOffers(?string $query = null, array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilder('j')
            ->where('j.expiresAt > :now OR j.expiresAt IS NULL')
            ->setParameter('now', new DateTime());

        if ($query) {
            $queryBuilder->andWhere('j.title LIKE :query OR j.description LIKE :query OR j.location LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if (!empty($filters)) {
            if (!empty($filters['contractType'])) {
                $queryBuilder->andWhere('j.contractType = :contractType')
                    ->setParameter('contractType', $filters['contractType']);
            }

            if (!empty($filters['location'])) {
                $queryBuilder->andWhere('j.location LIKE :location')
                    ->setParameter('location', '%' . $filters['location'] . '%');
            }

            if (!empty($filters['minSalary'])) {
                $queryBuilder->andWhere('j.salary >= :minSalary')
                    ->setParameter('minSalary', $filters['minSalary']);
            }
        }

        return $queryBuilder->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws QueryException
     */
    public function countJobOffersByUserAndDateRange(User $user, DateTime $startDate, DateTime $endDate): int
    {
        $result = $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->andWhere('j.recruiter = :user')
            ->andWhere('j.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}
