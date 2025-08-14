<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Friendship;
use App\Entity\User;
use App\Repository\Trait\FlushTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method null|User find($id, $lockMode = null, $lockVersion = null)
 * @method null|User findOneBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null
 * )
 * @method User[]    findAll()
 * @method User[]    findBy(
 *     array<string, mixed> $criteria,
 *     array<string, string> $orderBy = null,
 *     int $limit = null,
 *     int $offset = null
 * )
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /** @use FlushTrait<User> */
    use FlushTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->save($user);
    }

    /**
     * Trouve des utilisateurs suggérés pour un utilisateur donné.
     *
     * @param null|User $user L'utilisateur pour lequel on cherche des suggestions
     * @param int $limit Nombre maximum de suggestions à retourner
     *
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
        $friendshipRepository = $this->getEntityManager()->getRepository(Friendship::class);
        $friendships          = $friendshipRepository->findAcceptedFriendships($user);
        $friendIds            = array_map(function ($friendship) use ($user) {
            return $friendship->getOtherUser($user)->getId();
        }, $friendships);

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
     * Trouve des utilisateurs par rôle.
     *
     * @return User[]
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
     *
     * @return User[]
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
            $friendship     = $friendshipRepository->findAcceptedBetweenUsers($currentUser, $user);
            $user->isFriend = ($friendship !== null);

            // Initialiser les valeurs par défaut
            $user->hasPendingRequestFrom = false;
            $user->hasPendingRequestTo   = false;
            $user->pendingRequestId      = null;

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
            $user->pendingRequestId    = $pendingRequest->getId();
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
     * Limité aux amis de l'utilisateur.
     *
     * @return User[]
     */
    public function findAllExcept(int $userId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id != :userId')
            ->setParameter('userId', $userId)
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un utilisateur par son nom d'utilisateur.
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
     * Trouve des suggestions d'utilisateurs de manière optimisée.
     *
     * @param string $query Le terme de recherche
     * @param int $limit Nombre maximum de résultats
     *
     * @return array<int, array<string, mixed>>
     */
    public function findSuggestionsOptimized(string $query, int $limit = 5): array
    {
        // Utiliser un cache en mémoire avec TTL court
        $cacheKey = 'user_suggestions_' . md5($query . $limit);

        // Simplifier la requête pour de meilleures performances
        $qb = $this->createQueryBuilder('u')
            ->select('PARTIAL u.{id, firstName, lastName, profilePicture}')
            ->where('CONCAT(LOWER(u.firstName), \' \', LOWER(u.lastName)) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->orderBy('u.lastName', 'ASC');

        // Utiliser le cache de requête Doctrine mais avec un TTL plus court
        $query = $qb->getQuery();
        $query->setResultCacheId($cacheKey);
        $query->setResultCacheLifetime(60); // Réduire à 1 minute pour garder les données fraîches

        // Optimisations de performance
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);

        try {
            return $query->getArrayResult();
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide
            return [];
        }
    }

    /**
     * Trouve un utilisateur par son nom complet.
     */
    public function findByFullName(string $fullName): ?User
    {
        $parts = explode(' ', trim($fullName));
        if (\count($parts) < 2) {
            return null;
        }

        $firstName = $parts[0];
        $lastName  = implode(' ', \array_slice($parts, 1));

        return $this->createQueryBuilder('u')
            ->where('LOWER(u.firstName) = LOWER(:firstName)')
            ->andWhere('LOWER(u.lastName) = LOWER(:lastName)')
            ->setParameter('firstName', $firstName)
            ->setParameter('lastName', $lastName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUsersByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%' . $role . '%')
            ->getQuery()
            ->getResult();
    }

    public function findFriends(User $user): array
    {
        return $this->createQueryBuilder('u')
            ->select('u')
            ->leftJoin('u.friendships', 'f')
            ->where('f.requester = :user OR f.addressee = :user')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'accepted')
            ->getQuery()
            ->getResult();
    }

    public function findAcceptedBetweenUsers(User $user1, User $user2): ?Friendship
    {
        return $this->getEntityManager()
            ->getRepository(Friendship::class)
            ->createQueryBuilder('f')
            ->where(
                '(f.requester = :user1 AND f.addressee = :user2) OR ' .
                '(f.requester = :user2 AND f.addressee = :user1)'
            )
            ->andWhere('f.status = :status')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setParameter('status', 'accepted')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingRequestBetween(User $requester, User $addressee): ?Friendship
    {
        return $this->getEntityManager()
            ->getRepository(Friendship::class)
            ->createQueryBuilder('f')
            ->where('f.requester = :requester AND f.addressee = :addressee')
            ->andWhere('f.status = :status')
            ->setParameter('requester', $requester)
            ->setParameter('addressee', $addressee)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les utilisateurs les plus populaires/actifs
     *
     * @param int $limit
     *
     * @return User[]
     */
    public function findPopularUsers(int $limit = 10): array
    {
        // Cette requête trouve les utilisateurs avec le plus d'activités
        try {
            return $this->createQueryBuilder('u')
                ->leftJoin('u.posts', 'p')
                ->leftJoin('u.comments', 'c')
                ->select('u')
                ->addSelect('COUNT(p.id) + COUNT(c.id) as HIDDEN activity')
                ->groupBy('u.id')
                ->orderBy('activity', 'DESC')
                ->addOrderBy('u.createdAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            // En cas d'erreur (par exemple si la relation n'existe pas), utiliser une méthode de fallback
            return $this->createQueryBuilder('u')
                ->orderBy('u.createdAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        }
    }

    /**
     * Recherche des utilisateurs par nom ou prénom pour l'autocomplétion des mentions.
     *
     * @param string $searchTerm Le terme recherché
     * @param int $limit Limite de résultats
     * @param User|null $currentUser L'utilisateur courant pour exclure des résultats
     *
     * @return User[] Les utilisateurs correspondant à la recherche
     */
    public function findByNameLike(string $searchTerm, int $limit = 10, ?User $currentUser = null): array
    {
        $qb = $this->createQueryBuilder('u');

        // Condition de base pour la recherche de nom/prénom
        $qb->where('LOWER(CONCAT(u.firstName, \' \', u.lastName)) LIKE LOWER(:searchTerm)')
           ->setParameter('searchTerm', '%' . $searchTerm . '%')
           ->orderBy('u.firstName', 'ASC')
           ->addOrderBy('u.lastName', 'ASC')
           ->setMaxResults($limit);

        // Exclure l'utilisateur courant s'il est fourni
        if ($currentUser) {
            $qb->andWhere('u.id != :currentUserId')
               ->setParameter('currentUserId', $currentUser->getId());

            // Prioriser les amis de l'utilisateur courant
            $friendshipRepository = $this->getEntityManager()->getRepository(Friendship::class);
            $friendships = $friendshipRepository->findAcceptedFriendships($currentUser);

            if (!empty($friendships)) {
                $friendIds = array_map(function ($friendship) use ($currentUser) {
                    return $friendship->getOtherUser($currentUser)->getId();
                }, $friendships);

                if (!empty($friendIds)) {
                    $qb->addSelect('CASE WHEN u.id IN (:friendIds) THEN 1 ELSE 0 END AS HIDDEN is_friend')
                       ->setParameter('friendIds', $friendIds)
                       ->orderBy('is_friend', 'DESC')
                       ->addOrderBy('u.firstName', 'ASC');
                }
            }
        }

        return $qb->getQuery()->getResult();
    }
}
