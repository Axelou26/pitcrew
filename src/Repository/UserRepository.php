<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Friendship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
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
        $qb = $this->createQueryBuilder('u');
        
        if ($user) {
            // Récupérer les IDs des amis de l'utilisateur
            $friendshipRepository = $this->getEntityManager()->getRepository(\App\Entity\Friendship::class);
            $friends = $friendshipRepository->findFriends($user);
            $friendIds = array_map(function($friend) {
                return $friend->getId();
            }, $friends);
            
            // Ajouter l'ID de l'utilisateur courant pour l'exclure des suggestions
            $excludeIds = array_merge($friendIds, [$user->getId()]);
            
            $qb->leftJoin('u.posts', 'p')
               ->where('u.id NOT IN (:excludeIds)')
               ->groupBy('u.id')
               ->orderBy('COUNT(p.id)', 'DESC') // Les utilisateurs les plus actifs d'abord
               ->setParameter('excludeIds', $excludeIds);
        } else {
            // Si aucun utilisateur n'est fourni, retourne simplement les utilisateurs les plus récents
            $qb->orderBy('u.createdAt', 'DESC');
        }
        
        return $qb->setMaxResults($limit)
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
            ->setParameter('role', '%"'.$role.'"%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des utilisateurs par nom, prénom, entreprise, etc.
     */
    public function searchUsers(string $query, User $currentUser): array
    {
        $qb = $this->createQueryBuilder('u')
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
        
        $users = $qb->getQuery()->getResult();
        
        // Enrichir les résultats avec des informations sur les relations d'amitié
        $friendshipRepository = $this->getEntityManager()->getRepository(Friendship::class);
        
        foreach ($users as $user) {
            // Vérifier si l'utilisateur est déjà ami avec l'utilisateur courant
            $friendship = $friendshipRepository->findAcceptedBetweenUsers($currentUser, $user);
            $user->isFriend = ($friendship !== null);
            
            // Vérifier si l'utilisateur courant a envoyé une demande d'amitié à cet utilisateur
            $pendingRequest = $friendshipRepository->findPendingRequestBetweenUsers($currentUser, $user, true);
            $user->hasPendingRequestFrom = ($pendingRequest !== null);
            
            // Vérifier si l'utilisateur a envoyé une demande d'amitié à l'utilisateur courant
            $pendingRequestTo = $friendshipRepository->findPendingRequestBetweenUsers($user, $currentUser, true);
            $user->hasPendingRequestTo = ($pendingRequestTo !== null);
            if ($pendingRequestTo) {
                $user->pendingRequestId = $pendingRequestTo->getId();
            }
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
        
        $friendIds = array_map(function($friend) {
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
} 