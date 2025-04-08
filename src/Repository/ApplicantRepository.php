<?php

namespace App\Repository;

use App\Entity\Applicant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Applicant>
 *
 * @method Applicant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Applicant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Applicant[]    findAll()
 * @method Applicant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Applicant::class);
    }

    /**
     * Recherche des candidats par compétences et mots-clés
     */
    public function searchBySkillsAndKeywords(array $skills = [], ?string $keywords = null): array
    {
        $qb = $this->createQueryBuilder('a');

        // Recherche par compétences techniques
        if (!empty($skills)) {
            $qb->andWhere('a.technicalSkills LIKE :skills')
               ->setParameter('skills', '%' . implode('%', $skills) . '%');
        }

        // Recherche par mots-clés dans la description ou le nom
        if ($keywords) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('a.description', ':keywords'),
                    $qb->expr()->like('a.firstName', ':keywords'),
                    $qb->expr()->like('a.lastName', ':keywords')
                )
            )
            ->setParameter('keywords', '%' . $keywords . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche avancée de candidats
     */
    public function advancedSearch(array $criteria): array
    {
        $qb = $this->createQueryBuilder('a');

        // Compétences techniques
        if (!empty($criteria['technicalSkills'])) {
            $qb->andWhere('a.technicalSkills LIKE :technicalSkills')
               ->setParameter('technicalSkills', '%' . implode('%', $criteria['technicalSkills']) . '%');
        }

        // Soft skills
        if (!empty($criteria['softSkills'])) {
            $qb->andWhere('a.softSkills LIKE :softSkills')
               ->setParameter('softSkills', '%' . implode('%', $criteria['softSkills']) . '%');
        }

        // Expérience minimale
        if (isset($criteria['minExperience'])) {
            // Note: Ceci est simplifié et devrait être adapté à la structure de données réelle
            $qb->andWhere('JSON_LENGTH(a.workExperience) >= :minExperience')
               ->setParameter('minExperience', $criteria['minExperience']);
        }

        // Recherche par mots-clés
        if (!empty($criteria['keywords'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('a.description', ':keywords'),
                    $qb->expr()->like('a.firstName', ':keywords'),
                    $qb->expr()->like('a.lastName', ':keywords')
                )
            )
            ->setParameter('keywords', '%' . $criteria['keywords'] . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les candidats qui correspondent à une offre d'emploi
     */
    public function findMatchingCandidates($jobOffer): array
    {
        $qb = $this->createQueryBuilder('a');

        // Récupérer les compétences requises de l'offre
        $requiredSkills = $jobOffer->getRequiredSkills();

        if (!empty($requiredSkills)) {
            foreach ($requiredSkills as $index => $skill) {
                $paramName = 'skill' . $index;
                $qb->orWhere('a.technicalSkills LIKE :' . $paramName)
                   ->setParameter($paramName, '%' . $skill . '%');
            }
        }

        return $qb->getQuery()->getResult();
    }
}
