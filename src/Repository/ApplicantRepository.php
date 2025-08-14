<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Applicant;
use App\Entity\JobOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Applicant>
 *
 * @method null|Applicant find($id, $lockMode = null, $lockVersion = null)
 * @method null|Applicant findOneBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null
 * )
 * @method Applicant[]    findAll()
 * @method Applicant[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class ApplicantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Applicant::class);
    }

    /**
     * Recherche par compétences et mots-clés.
     *
     * @param array<int, string> $skills
     *
     * @return Applicant[]
     */
    public function searchBySkillsAndKeywords(array $skills, string $keywords = ''): array
    {
        $queryBuilder = $this->createQueryBuilder('a');

        // Recherche par compétences techniques
        if (!empty($skills)) {
            $queryBuilder->andWhere('a.technicalSkills LIKE :skills')
                ->setParameter('skills', '%' . implode('%', $skills) . '%');
        }

        // Recherche par mots-clés dans la description ou le nom
        if ($keywords) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('a.description', ':keywords'),
                    $queryBuilder->expr()->like('a.firstName', ':keywords'),
                    $queryBuilder->expr()->like('a.lastName', ':keywords')
                )
            )
                ->setParameter('keywords', '%' . $keywords . '%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Recherche avancée avec critères multiples.
     *
     * @param array<string, mixed> $criteria
     *
     * @return Applicant[]
     */
    public function advancedSearch(array $criteria): array
    {
        $queryBuilder = $this->createQueryBuilder('a');

        // Compétences techniques
        if (!empty($criteria['technicalSkills'])) {
            $queryBuilder->andWhere('a.technicalSkills LIKE :technicalSkills')
                ->setParameter('technicalSkills', '%' . implode('%', $criteria['technicalSkills']) . '%');
        }

        // Soft skills
        if (!empty($criteria['softSkills'])) {
            $queryBuilder->andWhere('a.softSkills LIKE :softSkills')
                ->setParameter('softSkills', '%' . implode('%', $criteria['softSkills']) . '%');
        }

        // Expérience minimale
        if (isset($criteria['minExperience'])) {
            // Note: Ceci est simplifié et devrait être adapté à la structure de données réelle
            $queryBuilder->andWhere('JSON_LENGTH(a.workExperience) >= :minExperience')
                ->setParameter('minExperience', $criteria['minExperience']);
        }

        // Recherche par mots-clés
        if (!empty($criteria['keywords'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('a.description', ':keywords'),
                    $queryBuilder->expr()->like('a.firstName', ':keywords'),
                    $queryBuilder->expr()->like('a.lastName', ':keywords')
                )
            )
                ->setParameter('keywords', '%' . $criteria['keywords'] . '%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Trouve les candidats correspondant à une offre d'emploi.
     *
     * @return array<int, Applicant>
     */
    public function findMatchingCandidates(JobOffer $jobOffer, int $limit = 10): array
    {
        $requiredSkills = $jobOffer->getRequiredSkills();

        // Utiliser la connexion native pour exploiter les fonctions JSON de MySQL
        $conn = $this->getEntityManager()->getConnection();

        // Construire la requête SQL native avec JSON_CONTAINS
        $sql    = "SELECT a.id FROM `user` a WHERE a.discr = 'applicant' AND a.is_active = :active";
        $params = ['active' => true];

        // Ajouter des conditions pour les compétences requises
        if (!empty($requiredSkills)) {
            $skillConditions = [];
            foreach ($requiredSkills as $key => $skill) {
                $paramName          = 'skill_' . $key;
                $skillConditions[]  = "JSON_CONTAINS(a.technical_skills, :$paramName)";
                $params[$paramName] = json_encode($skill);
            }

            if (\count($skillConditions) > 0) {
                $sql .= ' AND (' . implode(' OR ', $skillConditions) . ')';
            }
        }

        // Limiter les résultats
        $sql .= ' LIMIT ' . (int) $limit;

        // Exécuter la requête SQL
        $stmt         = $conn->prepare($sql);
        $resultSet    = $stmt->executeQuery($params);
        $applicantIds = $resultSet->fetchFirstColumn();

        if (empty($applicantIds)) {
            return [];
        }

        // Récupérer les entités Applicant complètes par leurs IDs
        return $this->createQueryBuilder('a')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $applicantIds)
            ->getQuery()
            ->getResult();
    }
}
