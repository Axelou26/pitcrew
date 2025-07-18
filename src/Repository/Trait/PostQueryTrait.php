<?php

declare(strict_types=1);

namespace App\Repository\Trait;

use Doctrine\ORM\QueryBuilder;

trait PostQueryTrait
{
    private function addStandardJoins(QueryBuilder $qb): QueryBuilder
    {
        return $qb
            ->select('p', 'a', 'c', 'h', 'l')
            ->leftJoin('p.author', 'a')
            ->leftJoin('p.comments', 'c')
            ->leftJoin('p.hashtags', 'h')
            ->leftJoin('p.likes', 'l');
    }

    private function addOrderByDate(QueryBuilder $qb): QueryBuilder
    {
        return $qb->orderBy('p.createdAt', 'DESC');
    }

    private function createBasePostQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');
        $this->addStandardJoins($qb);
        $this->addOrderByDate($qb);

        return $qb;
    }
}
