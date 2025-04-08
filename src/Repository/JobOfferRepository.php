<?php

namespace App\Repository;

use App\Entity\JobOffer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     * @return JobOffer[] Returns an array of active JobOffer objects
     */
    public function findActiveOffers(): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.isActive = :active')
            ->andWhere('j.expiresAt > :now OR j.expiresAt IS NULL')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return JobOffer[] Returns an array of JobOffer objects matching the search criteria
     */
    public function search(?string $query = null, ?string $location = null, ?string $contractType = null): array
    {
        $qb = $this->createQueryBuilder('j')
            ->where('j.isActive = :active')
            ->andWhere('j.expiresAt > :now OR j.expiresAt IS NULL')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime());

        if ($query) {
            $qb->andWhere('j.title LIKE :query OR j.description LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($location) {
            $qb->andWhere('j.location LIKE :location')
                ->setParameter('location', '%' . $location . '%');
        }

        if ($contractType) {
            $qb->andWhere('j.contractType = :contractType')
                ->setParameter('contractType', $contractType);
        }

        return $qb->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return JobOffer[] Returns an array of similar JobOffer objects
     */
    public function findSimilarOffers(JobOffer $jobOffer, int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('j')
            ->where('j.id != :currentId')
            ->andWhere('j.isActive = :active')
            ->andWhere('j.expiresAt > :now OR j.expiresAt IS NULL');

        // Paramètres de base
        $parameters = [
            'currentId' => $jobOffer->getId(),
            'active' => true,
            'now' => new \DateTime()
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
                    $conditions[] = "CASE WHEN LOWER(j.title) LIKE :keyword{$index} OR LOWER(j.description) LIKE :keyword{$index} THEN 3 ELSE 0 END";
                    $parameters["keyword{$index}"] = '%' . $keyword . '%';
                }
            }
        }

        // Si aucune condition n'est définie, ajouter une condition par défaut
        if (empty($conditions)) {
            $conditions[] = '0';
        }

        // Calculer le score total
        $scoreExpr = implode(' + ', $conditions);

        $qb->select('j, (' . $scoreExpr . ') as HIDDEN score')
           ->setParameters($parameters)
           ->orderBy('score', 'DESC')
           ->addOrderBy('j.createdAt', 'DESC')
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return JobOffer[] Returns an array of expired JobOffer objects
     */
    public function findExpiredOffers(): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.expiresAt <= :now')
            ->andWhere('j.expiresAt IS NOT NULL')
            ->setParameter('now', new \DateTime())
            ->orderBy('j.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int Returns the number of active job offers
     */
    public function countActiveOffers(): int
    {
        return $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.isActive = :active')
            ->andWhere('j.expiresAt > :now OR j.expiresAt IS NULL')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche et filtre les offres d'emploi
     */
    public function searchOffers(?string $query = null, ?array $filters = []): array
    {
        $qb = $this->createQueryBuilder('j')
            ->where('j.expiresAt > :now OR j.expiresAt IS NULL')
            ->setParameter('now', new \DateTime());

        // Recherche par mot-clé
        if ($query) {
            $qb->andWhere('j.title LIKE :query OR j.description LIKE :query OR j.location LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        // Filtres
        if (!empty($filters)) {
            // Filtre par type de contrat
            if (!empty($filters['contractType'])) {
                $qb->andWhere('j.contractType = :contractType')
                    ->setParameter('contractType', $filters['contractType']);
            }

            // Filtre par lieu
            if (!empty($filters['location'])) {
                $qb->andWhere('j.location LIKE :location')
                    ->setParameter('location', '%' . $filters['location'] . '%');
            }

            // Filtre par salaire minimum
            if (!empty($filters['minSalary'])) {
                $qb->andWhere('j.salary >= :minSalary')
                    ->setParameter('minSalary', $filters['minSalary']);
            }
        }

        return $qb->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return JobOffer[] Returns an array of JobOffer objects for a specific recruiter
     */
    public function findByRecruiter($recruiter): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'offres d'emploi publiées par un utilisateur dans une période donnée
     */
    public function countJobOffersByUserAndDateRange(User $user, \DateTime $startDate, \DateTime $endDate): int
    {
        return $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->andWhere('j.recruiter = :user')
            ->andWhere('j.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
