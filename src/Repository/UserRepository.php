<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Friendship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private $cache;

    public function __construct(ManagerRegistry $registry, CacheInterface $cache)
    {
        parent::__construct($registry, User::class);
        $this->cache = $cache;
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->save($user, true);
    }

    /**
     * Trouve des utilisateurs suggérés pour un utilisateur donné
     *
     * @param User|null $user L'utilisateur pour lequel on cherche des suggestions
     * @param int $limit Nombre maximum de suggestions à retourner
     * @return User[] Liste des utilisateurs suggérés
     */
    public function findSuggestedUsers(?User $user = null, int $limit = 5): array
    {
        $queryBuilder = $this->createQueryBuilder('u');

        if (!$user) {
            // Si aucun utilisateur n'est fourni, retourne simplement les utilisateurs les plus récents
            $queryBuilder->orderBy('u.createdAt', 'DESC');
            return $queryBuilder->setMaxResults($limit * 2)
                     ->getQuery()
                     ->getResult();
        }

        // Si un utilisateur est fourni :
        // Récupérer les IDs des amis de l'utilisateur
        $friendshipRepository = $this->getEntityManager()->getRepository(\App\Entity\Friendship::class);
        $friends = $friendshipRepository->findFriends($user);
        $friendIds = array_map(function ($friend) {
            return $friend->getId();
        }, $friends);

        // Ajouter l'ID de l'utilisateur courant pour l'exclure des suggestions
        $excludeIds = array_merge($friendIds, [$user->getId()]);

        $queryBuilder->leftJoin('u.posts', 'p')
           ->where('u.id NOT IN (:excludeIds)')
           ->groupBy('u.id')
           ->orderBy('COUNT(p.id)', 'DESC') // Les utilisateurs les plus actifs d'abord
           ->setParameter('excludeIds', $excludeIds);

        return $queryBuilder->setMaxResults($limit * 2)
                 ->getQuery()
                 ->getResult();
    }

    /**
     * Trouve des utilisateurs par rôle
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_' . strtoupper(str_replace('ROLE_', '', $role)) . '"%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des utilisateurs par nom, prénom, entreprise, etc.
     */
    public function searchUsers(string $query, User $currentUser): array
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->where('u.id != :currentUserId')
            ->andWhere('(
                u.firstName LIKE :query OR 
                u.lastName LIKE :query OR 
                u.email LIKE :query OR
                u.company LIKE :query OR
                u.jobTitle LIKE :query
            )')
            ->setParameter('currentUserId', $currentUser->getId())
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->setMaxResults(50);

        $users = $queryBuilder->getQuery()->getResult();

        // Enrichir les résultats avec des informations sur les relations d'amitié
        $friendshipRepository = $this->getEntityManager()->getRepository(Friendship::class);

        foreach ($users as $user) {
            // Vérifier si l'utilisateur est déjà ami avec l'utilisateur courant
            $friendship = $friendshipRepository->findAcceptedBetweenUsers($currentUser, $user);
            $user->isFriend = ($friendship !== null);

            // Initialiser les valeurs par défaut
            $user->hasPendingRequestFrom = false;
            $user->hasPendingRequestTo = false;
            $user->pendingRequestId = null;

            // Vérifier les demandes d'amitié en attente
            $pendingRequest = $friendshipRepository->findPendingRequestBetween($currentUser, $user);
            if (!$pendingRequest) {
                continue;
            }

            if ($pendingRequest->getRequester() === $currentUser) {
                $user->hasPendingRequestFrom = true;
                continue;
            }

            $user->hasPendingRequestTo = true;
            $user->pendingRequestId = $pendingRequest->getId();
        }

        return $users;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les utilisateurs sauf celui spécifié
     * Limité aux amis de l'utilisateur
     */
    public function findAllExcept(User $user): array
    {
        $friendshipRepository = $this->getEntityManager()->getRepository(Friendship::class);
        $friends = $friendshipRepository->findFriends($user);

        if (empty($friends)) {
            return [];
        }

        $friendIds = array_map(function ($friend) {
            return $friend->getId();
        }, $friends);

        return $this->createQueryBuilder('u')
            ->andWhere('u.id != :userId')
            ->andWhere('u.id IN (:friendIds)')
            ->setParameter('userId', $user->getId())
            ->setParameter('friendIds', $friendIds)
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un utilisateur par son nom d'utilisateur
     */
    public function findByUsername(string $username): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('CONCAT(u.firstName, \' \', u.lastName) LIKE :username')
            ->setParameter('username', '%' . $username . '%')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve des suggestions d'utilisateurs de manière optimisée
     *
     * @param string $query Le terme de recherche
     * @param int $limit Nombre maximum de résultats
     * @return array
     */
    public function findSuggestionsOptimized(string $query, int $limit = 5): array
    {
        $cacheKey = 'user_suggestions_' . md5($query . $limit);
        $qb = $this->createQueryBuilder('u')
            ->select('PARTIAL u.{id, firstName, lastName, profilePicture}')
            ->where('LOWER(CONCAT(u.firstName, \' \', u.lastName)) LIKE LOWER(:query)')
            ->orWhere('LOWER(u.firstName) LIKE LOWER(:query)')
            ->orWhere('LOWER(u.lastName) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC');

        // Utiliser le cache de requête Doctrine
        $query = $qb->getQuery();
        $query->setResultCacheId($cacheKey);
        $query->setResultCacheLifetime(300); // 5 minutes

        // Optimisations de performance
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Query\SqlWalker');

        try {
            return $query->getArrayResult();
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide
            return [];
        }
    }

    /**
     * Trouve un utilisateur par son nom complet
     */
    public function findByFullName(string $fullName): ?User
    {
        $parts = explode(' ', trim($fullName));
        if (count($parts) < 2) {
            return null;
        }

        $firstName = $parts[0];
        $lastName = implode(' ', array_slice($parts, 1));

        return $this->createQueryBuilder('u')
            ->where('LOWER(u.firstName) = LOWER(:firstName)')
            ->andWhere('LOWER(u.lastName) = LOWER(:lastName)')
            ->setParameter('firstName', $firstName)
            ->setParameter('lastName', $lastName)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
