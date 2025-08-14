<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Applicant;
use App\Entity\JobApplication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobApplication>
 *
 * @method null|JobApplication find($id, $lockMode = null, $lockVersion = null)
 * @method null|JobApplication findOneBy(array<string, mixed> $criteria, array<string, string> $orderBy = null)
 * @method JobApplication[]    findAll()
 * @method JobApplication[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
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
     * @return JobApplication[] Returns an array of JobApplication objects for a user
     */
    public function findByUser(User $user): array
    {
        // Si l'utilisateur est déjà un Applicant, l'utiliser directement
        if ($user instanceof Applicant) {
            // Utiliser une requête SQL native pour contourner le problème de reconnaissance de champ
            $conn = $this->getEntityManager()->getConnection();
            $sql  = '
                SELECT ja.*
                FROM job_application ja
                WHERE ja.applicant_id = :applicant_id
                ORDER BY ja.created_at DESC
            ';
            $stmt      = $conn->prepare($sql);
            $resultSet = $stmt->executeQuery(['applicant_id' => $user->getId()]);
            $results   = $resultSet->fetchAllAssociative();

            return $this->hydrateResults($results);
        }

        // Sinon, vérifier si un Applicant avec le même ID existe
        // Cela fonctionne parce qu'Applicant hérite de User et partage la même table et ID
        $applicant = $this->getEntityManager()
            ->getRepository(Applicant::class)
            ->find($user->getId());

        if (!$applicant) {
            return [];
        }

        // Utiliser une requête SQL native
        $conn = $this->getEntityManager()->getConnection();
        $sql  = '
            SELECT ja.*
            FROM job_application ja
            WHERE ja.applicant_id = :applicant_id
            ORDER BY ja.created_at DESC
        ';
        $stmt      = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['applicant_id' => $applicant->getId()]);
        $results   = $resultSet->fetchAllAssociative();

        return $this->hydrateResults($results);
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
     * Compte le nombre de candidatures pour une offre d'emploi.
     */
    public function countByJobOffer(int $jobOfferId): int
    {
        return (int) $this->createQueryBuilder('ja')
            ->select('COUNT(ja.id)')
            ->andWhere('ja.jobOffer = :jobOfferId')
            ->setParameter('jobOfferId', $jobOfferId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return bool Returns true if the user has already applied to the job offer
     */
    public function hasUserApplied(User $user, int $jobOfferId): bool
    {
        // Si l'utilisateur est déjà un Applicant, l'utiliser directement
        if ($user instanceof Applicant) {
            $applicant = $user;
        }

        if (!isset($applicant)) {
            // Sinon, vérifier si un Applicant avec le même ID existe
            $applicant = $this->getEntityManager()
                ->getRepository(Applicant::class)
                ->find($user->getId());

            if (!$applicant) {
                return false;
            }
        }

        $conn = $this->getEntityManager()->getConnection();
        $sql  = '
            SELECT COUNT(ja.id) as count
            FROM job_application ja
            WHERE ja.applicant_id = :applicant_id
            AND ja.job_offer_id = :job_offer_id
        ';
        $stmt   = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'applicant_id' => $applicant->getId(),
            'job_offer_id' => $jobOfferId,
        ]);

        return (int) $result->fetchOne() > 0;
    }

    /**
     * Hydrate raw database results into JobApplication entities.
     *
     * @param array $results Raw database results
     *
     * @return array Array of JobApplication objects
     */
    private function hydrateResults(array $results): array
    {
        $applicationEntities = [];
        $em                  = $this->getEntityManager();

        foreach ($results as $row) {
            $application = $this->find($row['id']);
            if ($application) {
                $applicationEntities[] = $application;
            }
        }

        return $applicationEntities;
    }
}
