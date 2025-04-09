<?php

declare(strict_types=1);

namespace App\Repository\Trait;

use Doctrine\ORM\EntityManagerInterface;

trait FlushTrait
{
    protected function getEntityManager(): EntityManagerInterface
    {
        return parent::getEntityManager();
    }

    public function persist(object $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    public function remove(object $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function save(object $entity): void
    {
        $this->persist($entity);
        $this->flush();
    }

    public function delete(object $entity): void
    {
        $this->remove($entity);
        $this->flush();
    }
}
