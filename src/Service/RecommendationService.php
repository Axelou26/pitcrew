<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Post;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Friendship;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use DateTime;
use Exception;

class RecommendationService
{
    private $postRepository;
    private $userRepository;
    private $entityManager;
    private $cache;

    public function __construct(
        PostRepository $postRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        CacheInterface $cache
    ) {
        $this->postRepository = $postRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->cache = $cache;
    }

    /**
     * Récupère les posts recommandés pour un utilisateur
     *
     * @param User $user L'utilisateur pour lequel on génère des recommandations
     * @param int $limit Nombre maximum de posts à récupérer
     *
     * @return array Liste des posts recommandés
     */
    public function getRecommendedPosts(User $user, int $limit = 10): array
    {
        // Utiliser le cache pour les recommandations
        return $this
            ->cache
            ->get('recommended_posts_' . $user
            ->getId(), function (ItemInterface $item) use ($user, $limit) {
                $item->expiresAfter(300); // Cache pour 5 minutes

            // Récupérer les IDs des amis
                $friendIds = $this->getFriendIds($user);

            // Récupérer les hashtags d'intérêt
                $interests = $this->getUserInterestsAndHashtags($user);

            // Construire la requête
                $qb = $this->entityManager->createQueryBuilder();
                $qb->select('DISTINCT p')
                ->from('App\Entity\Post', 'p')
                ->leftJoin('p.author', 'a')
                ->leftJoin('p.hashtags', 'h');

            // Créer une condition OR pour les différents critères
                $orX = $qb->expr()->orX();
                $orX->add($qb->expr()->eq('a.id', ':userId'));

                if (!empty($friendIds)) {
                    $orX->add($qb->expr()->in('a.id', ':friendIds'));
                    $qb->setParameter('friendIds', $friendIds);
                }

                if (!empty($interests)) {
                    $orX->add($qb->expr()->in('h.name', ':interests'));
                    $qb->setParameter('interests', $interests);
                }

                $qb->where($orX)
                ->setParameter('userId', $user->getId())
                ->orderBy('p.createdAt', 'DESC')
                ->addOrderBy('p.likesCounter', 'DESC')
                ->setMaxResults($limit * 2);

                $posts = $qb->getQuery()->getResult();

            // Calculer un score pour chaque post
                $scoredPosts = [];
                foreach ($posts as $post) {
                    $score = $this->calculatePostScore($post, $user);
                    $scoredPosts[] = [
                    'post' => $post,
                    'score' => $score
                    ];
                }

            // Trier par score
                usort($scoredPosts, function ($a, $b) {
                    return $b['score'] <=> $a['score'];
                });

            // Retourner uniquement les posts
                return array_slice(array_map(function ($item) {
                    return $item['post'];
                }, $scoredPosts), 0, $limit);
            });
    }

    /**
     * Calcule un score pour un post en fonction de plusieurs critères
     */
    private function calculatePostScore(Post $post, User $user): float
    {
        $score = 0;

        // Score basé sur la date (posts plus récents = score plus élevé)
        $age = time() - $post->getCreatedAt()->getTimestamp();
        $score += max(0, 100 - ($age / 3600)); // Diminue le score avec l'âge (en heures)

        // Score basé sur l'engagement
        $score += $post->getLikesCounter() * 2;
        $score += $post->getCommentsCounter() * 3;
        $score += $post->getSharesCounter() * 4;

        // Bonus si c'est un post de l'utilisateur ou d'un ami
        if ($post->getAuthor() === $user) {
            $score *= 1.5;
        } elseif (in_array($post->getAuthor()->getId(), $this->getFriendIds($user))) {
            $score *= 1.3;
        }

        return $score;
    }

    /**
     * Récupère les IDs des amis de l'utilisateur (avec cache)
     */
    private function getFriendIds(User $user): array
    {
        return $this->cache->get('friend_ids_' . $user->getId(), function (ItemInterface $item) use ($user) {
            $item->expiresAfter(3600); // Cache pour 1 heure

            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('DISTINCT CASE 
                    WHEN f.requester = :user THEN IDENTITY(f.addressee)
                    ELSE IDENTITY(f.requester)
                END as friendId')
               ->from('App\Entity\Friendship', 'f')
               ->where('(f.requester = :user OR f.addressee = :user)')
               ->andWhere('f.status = :status')
               ->setParameter('user', $user)
               ->setParameter('status', Friendship::STATUS_ACCEPTED);

            $result = $qb->getQuery()->getScalarResult();
            return array_column($result, 'friendId');
        });
    }

    /**
     * Récupère les hashtags utilisés par l'utilisateur
     */
    private function getUserInterestsAndHashtags(User $user): array
    {
        return $this->cache->get('user_interests_' . $user->getId(), function (ItemInterface $item) use ($user) {
            $item->expiresAfter(3600); // Cache pour 1 heure

            $queryBuilder = $this->entityManager->createQueryBuilder();
            $queryBuilder->select('DISTINCT h.name')
               ->from('App\Entity\Post', 'p')
               ->join('p.hashtags', 'h')
               ->where('p.author = :user')
               ->setParameter('user', $user);

            $result = $queryBuilder->getQuery()->getScalarResult();
            return array_column($result, 'name');
        });
    }

    /**
     * Récupère les posts de l'utilisateur
     */
    private function getUserPosts(User $user): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('p, a')
           ->from('App\Entity\Post', 'p')
           ->leftJoin('p.author', 'a')
           ->where('p.author = :userId')
           ->setParameter('userId', $user->getId())
           ->orderBy('p.createdAt', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Récupère les posts des amis de l'utilisateur
     */
    private function getFriendsPosts(User $user): array
    {
        // Récupérer les demandes d'amitié acceptées où l'utilisateur est le demandeur
        $sentFriendships = $user->getSentFriendRequests()->filter(function ($friendship) {
            return $friendship->getStatus() === Friendship::STATUS_ACCEPTED;
        });

        // Récupérer les demandes d'amitié acceptées où l'utilisateur est le destinataire
        $receivedFriendships = $user->getReceivedFriendRequests()->filter(function ($friendship) {
            return $friendship->getStatus() === Friendship::STATUS_ACCEPTED;
        });

        // Extraire les IDs des amis
        $friendIds = [];

        foreach ($sentFriendships as $friendship) {
            $friendIds[] = $friendship->getAddressee()->getId();
        }

        foreach ($receivedFriendships as $friendship) {
            $friendIds[] = $friendship->getRequester()->getId();
        }

        if (empty($friendIds)) {
            return [];
        }

        // Récupérer les posts des amis avec les relations chargées
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('p, a')
           ->from('App\Entity\Post', 'p')
           ->leftJoin('p.author', 'a')
           ->where('p.author IN (:friendIds)')
           ->setParameter('friendIds', $friendIds)
           ->orderBy('p.createdAt', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Recommandations personnalisées basées sur les centres d'intérêt
     */
    private function getPersonalizedRecommendations(User $user, int $limit): array
    {
        return $this
            ->cache
            ->get('personalized_recommendations_' . $user
            ->getId(), function (ItemInterface $item) use ($user, $limit) {
                $item->expiresAfter(900); // Cache pour 15 minutes

                try {
                    // Récupérer les données nécessaires en une seule requête
                    $queryBuilder = $this->entityManager->createQueryBuilder();
                    $queryBuilder->select('p', 'a', 'h', 'l', 'c')
                       ->from('App\Entity\Post', 'p')
                       ->leftJoin('p.author', 'a')
                       ->leftJoin('p.hashtags', 'h')
                       ->leftJoin('p.likes', 'l')
                       ->leftJoin('p.comments', 'c')
                       ->where('p.author != :userId')
                       ->andWhere($queryBuilder->expr()->orX(
                           // Posts avec hashtags d'intérêt
                           'h.name IN (:interests)',
                           // Posts des utilisateurs avec qui l'utilisateur interagit
                           'p.author IN (:interactedUsers)',
                           // Posts populaires récents
                           'p.likesCounter >= :minLikes AND p.createdAt >= :recentDate'
                       ))
                       ->setParameter('userId', $user->getId())
                       ->setParameter('interests', $this->getUserInterestsAndHashtags($user))
                       ->setParameter('interactedUsers', $this->getFrequentlyInteractedUsers($user))
                       ->setParameter('minLikes', 5)
                       ->setParameter('recentDate', new DateTime('-7 days'))
                       ->orderBy('p.createdAt', 'DESC')
                       ->addOrderBy('p.likesCounter', 'DESC')
                       ->setMaxResults($limit * 2);

                    $posts = $queryBuilder->getQuery()->getResult();

                    // Calculer les scores et trier
                    $scoredPosts = [];
                    foreach ($posts as $post) {
                        $score = $this->calculateRecommendationScore($post, $user);
                        $scoredPosts[] = [
                            'post' => $post,
                            'score' => $score
                        ];
                    }

                    usort($scoredPosts, function ($a, $b) {
                        return $b['score'] <=> $a['score'];
                    });

                    // Retourner les meilleurs posts
                    return array_slice(array_map(function ($item) {
                        return $item['post'];
                    }, $scoredPosts), 0, $limit);
                } catch (\Exception $e) {
                    return [];
                }
            });
    }

    /**
     * Calcule un score de recommandation pour un post
     */
    private function calculateRecommendationScore(Post $post, User $user): float
    {
        $score = 0;

        // Score basé sur la fraîcheur (décroissance exponentielle)
        $age = time() - $post->getCreatedAt()->getTimestamp();
        $score += 100 * exp(-$age / (7 * 24 * 3600)); // Demi-vie de 7 jours

        // Score basé sur l'engagement (avec pondération)
        $score += $post->getLikesCounter() * 2;
        $score += $post->getCommentsCounter() * 3;
        $score += $post->getSharesCounter() * 4;

        // Bonus pour les hashtags d'intérêt
        $userInterests = $this->getUserInterestsAndHashtags($user);
        foreach ($post->getHashtags() as $hashtag) {
            if (in_array($hashtag->getName(), $userInterests)) {
                $score *= 1.2;
            }
        }

        // Bonus pour les auteurs avec qui l'utilisateur interagit
        if (in_array($post->getAuthor()->getId(), $this->getFrequentlyInteractedUsers($user))) {
            $score *= 1.3;
        }

        return $score;
    }

    /**
     * Récupère les utilisateurs avec qui l'utilisateur interagit fréquemment
     */
    private function getFrequentlyInteractedUsers(User $user): array
    {
        return $this
            ->cache
            ->get('frequently_interacted_users_' . $user
            ->getId(), function (ItemInterface $item) use ($user) {
                $item->expiresAfter(1800); // Cache pour 30 minutes

                try {
                    // Récupérer les utilisateurs avec qui l'utilisateur interagit en une seule requête
                    $queryBuilder = $this->entityManager->createQueryBuilder();
                    $queryBuilder->select('DISTINCT u.id')
                        ->from('App\Entity\User', 'u')
                        ->leftJoin(
                            'App\Entity\PostLike',
                            'pl',
                            'WITH',
                            'pl.user = :userId AND pl.post MEMBER OF u.posts'
                        )
                        ->leftJoin(
                            'App\Entity\PostComment',
                            'pc',
                            'WITH',
                            'pc.author = :userId AND pc.post MEMBER OF u.posts'
                        )
                        ->leftJoin(
                            'App\Entity\Friendship',
                            'f',
                            'WITH',
                            '(f.user = :userId AND f.friend = u) OR (f.user = u AND f.friend = :userId)'
                        )
                        ->where('u != :userId')
                        ->andWhere($queryBuilder->expr()->orX(
                            'pl.id IS NOT NULL',
                            'pc.id IS NOT NULL',
                            'f.id IS NOT NULL'
                        ))
                        ->setParameter('userId', $user->getId());

                    $result = $queryBuilder->getQuery()->getScalarResult();
                    return array_column($result, 'id');
                } catch (\Exception $e) {
                    return [];
                }
            });
    }

    /**
     * Suggère des utilisateurs à suivre
     */
    public function getSuggestedUsers(User $user, int $limit = 5): array
    {
        // Utiliser la méthode du repository pour obtenir les suggestions
        return $this->userRepository->findSuggestedUsers($user, $limit);
    }

    /**
     * Récupère les posts partagés par l'utilisateur et ses amis
     */
    private function getRelevantShares(User $user): array
    {
        return $this->cache->get('relevant_shares_' . $user->getId(), function (ItemInterface $item) use ($user) {
            $item->expiresAfter(300); // Cache pour 5 minutes

            try {
                // Récupérer les IDs des amis en une seule requête
                $queryBuilder = $this->entityManager->createQueryBuilder();
                $friendIds = $queryBuilder->select('DISTINCT CASE 
                        WHEN f.user = :userId THEN f.friend
                        ELSE f.user
                    END')
                    ->from('App\Entity\Friendship', 'f')
                    ->where('(f.user = :userId OR f.friend = :userId)')
                    ->andWhere('f.status = :status')
                    ->setParameter('userId', $user->getId())
                    ->setParameter('status', Friendship::STATUS_ACCEPTED)
                    ->getQuery()
                    ->getScalarResult();

                $allUserIds = array_merge([$user->getId()], array_column($friendIds, 1));

                // Récupérer les posts partagés avec leurs relations en une seule requête
                $queryBuilder = $this->entityManager->createQueryBuilder();
                $queryBuilder->select('p', 'a', 'l', 'c', 'h')
                    ->from('App\Entity\Post', 'p')
                    ->innerJoin('p.shares', 's')
                    ->leftJoin('p.author', 'a')
                    ->leftJoin('p.likes', 'l')
                    ->leftJoin('p.comments', 'c')
                    ->leftJoin('p.hashtags', 'h')
                    ->where('s.user IN (:userIds)')
                    ->setParameter('userIds', $allUserIds)
                    ->orderBy('p.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    public function getSuggestedUsersWithFriendshipStatus(User $user, int $limit): array
    {
        $suggestedUsers = $this->fetchSuggestedUsers($user, $limit);
        return $this->enrichUsersWithFriendshipStatus($suggestedUsers, $user);
    }

    private function fetchSuggestedUsers(User $user, int $limit): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        return $queryBuilder->select('u', 'f')
            ->from('App\Entity\User', 'u')
            ->leftJoin(
                'App\Entity\Friendship',
                'f',
                'WITH',
                '(f.user = :currentUser AND f.friend = u) OR (f.user = u AND f.friend = :currentUser)'
            )
            ->where('u != :currentUser')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('currentUser', $user)
            ->setParameter('role', '%"' . $user->getRoles()[0] . '"%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    private function enrichUsersWithFriendshipStatus(array $suggestedUsers, User $currentUser): array
    {
        foreach ($suggestedUsers as $suggestedUser) {
            $friendship = $this->findFriendshipBetweenUsers($suggestedUser, $currentUser);
            $this->setFriendshipStatus($suggestedUser, $friendship, $currentUser);
        }
        return $suggestedUsers;
    }

    private function findFriendshipBetweenUsers(User $suggestedUser, User $currentUser): ?Friendship
    {
        foreach ($suggestedUser->getFriendships() as $friendship) {
            if ($this->isFriendshipBetweenUsers($friendship, $suggestedUser, $currentUser)) {
                return $friendship;
            }
        }
        return null;
    }

    private function isFriendshipBetweenUsers(Friendship $friendship, User $user1, User $user2): bool
    {
        return ($friendship->getUser() === $user1 && $friendship->getFriend() === $user2) ||
               ($friendship->getUser() === $user2 && $friendship->getFriend() === $user1);
    }

    private function setFriendshipStatus(User $suggestedUser, ?Friendship $friendship, User $currentUser): void
    {
        $suggestedUser->isFriend = $friendship && $friendship->getStatus() === 'accepted';

        $isPending = $friendship && $friendship->getStatus() === 'pending';
        $suggestedUser->hasPendingRequestFrom = $isPending && $friendship->getUser() === $currentUser;
        $suggestedUser->hasPendingRequestTo = $isPending && $friendship->getUser() === $suggestedUser;

        if ($suggestedUser->hasPendingRequestTo) {
            $suggestedUser->pendingRequestId = $friendship->getId();
        }
    }

    /**
     * Vide le cache pour les tests
     */
    public function clearCache(): void
    {
        // Récupérer tous les utilisateurs pour nettoyer leurs caches spécifiques
        $users = $this->entityManager->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $userId = $user->getId();
            $this->cache->delete('personalized_recommendations_' . $userId);
            $this->cache->delete('frequently_interacted_users_' . $userId);
            $this->cache->delete('relevant_shares_' . $userId);
        }

        // Nettoyer le cache des hashtags tendances
        $this->cache->delete('trending_hashtags');
    }

    /**
     * Récupère les hashtags les plus utilisés
     *
     * @param int $limit Nombre maximum de hashtags à récupérer
     * @return array Liste des hashtags les plus utilisés
     */
    public function getTrendingHashtags(int $limit = 5): array
    {
        return $this->cache->get('trending_hashtags', function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(900); // Cache pour 15 minutes

            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('h')
               ->from('App\Entity\Hashtag', 'h')
               ->orderBy('h.usageCount', 'DESC')
               ->setMaxResults($limit);

            return $qb->getQuery()->getResult();
        });
    }
}
