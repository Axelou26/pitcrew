<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PostReaction;
use App\Repository\Trait\FlushTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostReaction>
 *
 * @method PostReaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostReaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostReaction[]    findAll()
 * @method PostReaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostReactionRepository extends ServiceEntityRepository
{
    use FlushTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostReaction::class);
    }

    public function save(PostReaction $entity, bool $flush = false): void
    {
        $this->persist($entity);
        if ($flush) {
            $this->flush();
        }
    }

    public function remove(PostReaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->flush();
        }
    }
}
