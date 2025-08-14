<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Friendship;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RecommendationService
{
    private PostRepository $postRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private CacheInterface $cache;

    public function __construct(
        PostRepository $postRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        CacheInterface $cache
    ) {
        $this->postRepository = $postRepository;
        $this->userRepository = $userRepository;
        $this->entityManager  = $entityManager;
        $this->cache          = $cache;
    }

    /**
     * @return Post[]
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
                        $score         = $this->calculatePostScore($post, $user);
                        $scoredPosts[] = [
                            'post'  => $post,
                            'score' => $score,
                        ];
                    }

                    // Trier par score
                    usort($scoredPosts, function ($a, $b) {
                        return $b['score'] <=> $a['score'];
                    });

                    // Retourner uniquement les posts
                    return \array_slice(array_map(function ($item) {
                        return $item['post'];
                    }, $scoredPosts), 0, $limit);
                });
    }

    /**
     * Suggère des utilisateurs à suivre.
     */
    public function getSuggestedUsers(User $user, int $limit = 5): array
    {
        // Utiliser la méthode du repository pour obtenir les suggestions
        return $this->userRepository->findSuggestedUsers($user, $limit);
    }

    public function getSuggestedUsersWithFriendshipStatus(User $user, int $limit): array
    {
        $suggestedUsers = $this->fetchSuggestedUsers($user, $limit);

        return $this->enrichUsersWithFriendshipStatus($suggestedUsers, $user);
    }

    /**
     * Vide le cache pour les tests.
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
     * Récupère les hashtags les plus utilisés.
     *
     * @param int $limit Nombre maximum de hashtags à récupérer
     *
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

    /**
     * Calcule un score pour un post en fonction de plusieurs critères.
     */
    private function calculatePostScore(Post $post, User $user): float
    {
        $score = 0;

        // Score basé sur la date (posts plus récents = score plus élevé)
        $createdAt = $post->getCreatedAt();
        if ($createdAt === null) {
            return 0;
        }

        $age = time() - $createdAt->getTimestamp();
        // Diminue le score avec l'âge (en heures)
        $score += max(0, 100 - ($age / 3600));

        // Score basé sur l'engagement
        $score += $post->getLikesCounter() * 2;
        $score += $post->getCommentsCounter() * 3;
        $score += $post->getReposts()->count() * 4;

        // Bonus si c'est un post de l'utilisateur ou d'un ami
        $author = $post->getAuthor();
        if ($author === null) {
            return $score;
        }

        if ($author === $user) {
            $score *= 1.5;
        } elseif (\in_array($author->getId(), $this->getFriendIds($user), true)) {
            $score *= 1.3;
        }

        return $score;
    }

    /**
     * @return int[]
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
     * @return string[]
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
     * @return Post[]
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
     * @return Post[]
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
     * @return array<int, Post>
     */
    private function getPersonalizedRecommendations(User $user): array
    {
        $hashtags = $user->getHashtags();
        if ($hashtags === null) {
            return [];
        }

        $userHashtags = [];
        foreach ($hashtags as $hashtag) {
            $userHashtags[] = $hashtag->getName();
        }

        return $this->postRepository->findByHashtags($userHashtags);
    }

    /**
     * Calcule un score de recommandation pour un post.
     */
    private function calculateRecommendationScore(Post $post, User $user): float
    {
        $score = 0;

        // Score basé sur la fraîcheur (décroissance exponentielle)
        $createdAt = $post->getCreatedAt();
        if ($createdAt === null) {
            return 0;
        }

        $age = time() - $createdAt->getTimestamp();
        $score += 100 * exp(-$age / (7 * 24 * 3600)); // Demi-vie de 7 jours

        // Score basé sur l'engagement (avec pondération)
        $score += $post->getLikesCounter() * 2;
        $score += $post->getCommentsCounter() * 3;
        $score += $post->getReposts()->count() * 4;

        // Bonus pour les hashtags d'intérêt
        $userInterests = $this->getUserInterestsAndHashtags($user);
        $postHashtags  = $post->getHashtags();
        if ($postHashtags !== null) {
            foreach ($postHashtags as $hashtag) {
                if (\in_array($hashtag->getName(), $userInterests, true)) {
                    $score *= 1.2;
                }
            }
        }

        // Bonus pour les auteurs avec qui l'utilisateur interagit
        if (\in_array($post->getAuthor()->getId(), $this->getFrequentlyInteractedUsers($user), true)) {
            $score *= 1.3;
        }

        return $score;
    }

    /**
     * @return array<int, User>
     */
    private function getFrequentlyInteractedUsers(User $user): array
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user->getId()]);
        if ($user === null) {
            return [];
        }

        $userId = $user->getId();
        if ($userId === null) {
            return [];
        }

        // Logique pour récupérer les utilisateurs avec lesquels l'utilisateur interagit fréquemment
        return [];
    }

    /**
     * Récupère les posts partagés par l'utilisateur et ses amis.
     */
    private function getRelevantShares(User $user): array
    {
        return $this->cache->get('relevant_shares_' . $user->getId(), function (ItemInterface $item) use ($user) {
            $item->expiresAfter(300); // Cache pour 5 minutes

            try {
                // Récupérer les IDs des amis en une seule requête
                $queryBuilder = $this->entityManager->createQueryBuilder();
                $friendIds    = $queryBuilder->select('DISTINCT CASE
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
        return ($friendship->getUser() === $user1 && $friendship->getFriend() === $user2)
               || ($friendship->getUser() === $user2 && $friendship->getFriend() === $user1);
    }

    private function setFriendshipStatus(User $suggestedUser, ?Friendship $friendship, User $currentUser): void
    {
        $suggestedUser->isFriend = $friendship && $friendship->getStatus() === 'accepted';

        $isPending                            = $friendship && $friendship->getStatus() === 'pending';
        $suggestedUser->hasPendingRequestFrom = $isPending && $friendship->getUser() === $currentUser;
        $suggestedUser->hasPendingRequestTo   = $isPending && $friendship->getUser() === $suggestedUser;

        if ($suggestedUser->hasPendingRequestTo && $friendship !== null) {
            $friendshipId = $friendship->getId();
            if ($friendshipId !== null) {
                $suggestedUser->pendingRequestId = $friendshipId;
            }
        }
    }
}
