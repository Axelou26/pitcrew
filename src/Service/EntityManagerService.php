<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EntityManagerService
{
    private EntityManagerInterface $entityManager;
    private FlashMessageService $flashMessageService;
    private AccessControlService $accessControlService;

    public function __construct(
        EntityManagerInterface $entityManager,
        FlashMessageService $flashMessageService,
        AccessControlService $accessControlService
    ) {
        $this->entityManager = $entityManager;
        $this->flashMessageService = $flashMessageService;
        $this->accessControlService = $accessControlService;
    }

    /**
     * Crée une nouvelle entité
     */
    public function create(object $entity, string $entityName = null): void
    {
        try {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $entityName = $entityName ?? $this->getEntityName($entity);
            $this->flashMessageService->entityCreated($entityName);
        } catch (\Exception $e) {
            $this->flashMessageService->operationFailed('création', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Met à jour une entité existante
     */
    public function update(object $entity, string $entityName = null): void
    {
        try {
            $this->entityManager->flush();

            $entityName = $entityName ?? $this->getEntityName($entity);
            $this->flashMessageService->entityUpdated($entityName);
        } catch (\Exception $e) {
            $this->flashMessageService->operationFailed('mise à jour', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprime une entité
     */
    public function delete(object $entity, string $entityName = null): void
    {
        try {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            $entityName = $entityName ?? $this->getEntityName($entity);
            $this->flashMessageService->entityDeleted($entityName);
        } catch (\Exception $e) {
            $this->flashMessageService->operationFailed('suppression', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Trouve une entité par son ID
     */
    public function find(string $entityClass, int $id): ?object
    {
        $repository = $this->entityManager->getRepository($entityClass);
        return $repository->find($id);
    }

    /**
     * Trouve une entité ou lance une exception si elle n'existe pas
     */
    public function findOrFail(string $entityClass, int $id): object
    {
        $entity = $this->find($entityClass, $id);

        if (!$entity) {
            $entityName = $this->getEntityNameFromClass($entityClass);
            $this->accessControlService->denyAccess('entity_not_found');
        }

        return $entity;
    }

    /**
     * Obtient le repository pour une classe d'entité
     */
    public function getRepository(string $entityClass): EntityRepository
    {
        return $this->entityManager->getRepository($entityClass);
    }

    /**
     * Vérifie si une entité existe
     */
    public function exists(string $entityClass, int $id): bool
    {
        return $this->find($entityClass, $id) !== null;
    }

    /**
     * Compte le nombre d'entités
     */
    public function count(string $entityClass, array $criteria = []): int
    {
        $repository = $this->getRepository($entityClass);
        return $repository->count($criteria);
    }

    /**
     * Trouve toutes les entités
     */
    public function findAll(
        string $entityClass,
        array $criteria = [],
        array $orderBy = null,
        int $limit = null,
        int $offset = null
    ): array {
        $repository = $this->getRepository($entityClass);
        return $repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Trouve une entité par critères
     */
    public function findOneBy(string $entityClass, array $criteria): ?object
    {
        $repository = $this->getRepository($entityClass);
        return $repository->findOneBy($criteria);
    }

    /**
     * Exécute une requête personnalisée
     */
    public function executeQuery(string $dql, array $parameters = []): array
    {
        $query = $this->entityManager->createQuery($dql);

        foreach ($parameters as $key => $value) {
            $query->setParameter($key, $value);
        }

        return $query->getResult();
    }

    /**
     * Exécute une requête native
     */
    public function executeNativeQuery(string $sql, array $parameters = []): array
    {
        $connection = $this->entityManager->getConnection();
        $statement = $connection->prepare($sql);

        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }

        return $statement->executeQuery()->fetchAllAssociative();
    }

    /**
     * Obtient le nom de l'entité à partir de l'objet
     */
    private function getEntityName(object $entity): string
    {
        $className = get_class($entity);
        return $this->getEntityNameFromClass($className);
    }

    /**
     * Obtient le nom de l'entité à partir de la classe
     */
    private function getEntityNameFromClass(string $className): string
    {
        $parts = explode('\\', $className);
        $entityName = end($parts);

        // Convertir CamelCase en nom lisible
        $entityName = preg_replace('/([a-z])([A-Z])/', '$1 $2', $entityName);
        $entityName = strtolower($entityName);

        return $entityName;
    }

    /**
     * Obtient l'EntityManager
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Obtient le FlashMessageService
     */
    public function getFlashMessageService(): FlashMessageService
    {
        return $this->flashMessageService;
    }

    /**
     * Obtient l'AccessControlService
     */
    public function getAccessControlService(): AccessControlService
    {
        return $this->accessControlService;
    }
}
