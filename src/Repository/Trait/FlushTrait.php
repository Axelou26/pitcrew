<?php

declare(strict_types=1);

namespace App\Repository\Trait;

use Doctrine\ORM\EntityManagerInterface;

/**
 * @template T of object
 */
trait FlushTrait
{
    /**
     * @param T $entity
     */
    public function persist(object $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * @param T $entity
     */
    public function remove(object $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @param T $entity
     */
    public function save(object $entity): void
    {
        $this->persist($entity);
        $this->flush();
    }

    /**
     * @param T $entity
     */
    public function delete(object $entity): void
    {
        $this->remove($entity);
        $this->flush();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return parent::getEntityManager();
    }
}
