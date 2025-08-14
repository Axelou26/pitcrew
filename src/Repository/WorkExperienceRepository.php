<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WorkExperience;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkExperience>
 *
 * @method null|WorkExperience find($id, $lockMode = null, $lockVersion = null)
 * @method null|WorkExperience findOneBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null
 * )
 * @method WorkExperience[]    findAll()
 * @method WorkExperience[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class WorkExperienceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkExperience::class);
    }
}
