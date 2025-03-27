<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Post;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Friendship;

class RecommendationService
{
    private $postRepository;
    private $userRepository;
    private $entityManager;

    public function __construct(
        PostRepository $postRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->postRepository = $postRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
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
        // 1. Posts de l'utilisateur lui-même
        $userPosts = $this->getUserPosts($user);
        
        // 2. Posts des amis
        $friendsPosts = $this->getFriendsPosts($user);
        
        // 3. Partages de l'utilisateur et de ses amis
        $shares = $this->getRelevantShares($user);
        
        // 4. Posts recommandés basés sur les intérêts et l'historique
        $recommendedPosts = $this->getPersonalizedRecommendations($user, $limit);
        
        // Fusionner les ensembles de posts, en évitant les doublons
        $allPosts = array_merge($userPosts, $friendsPosts, $shares, $recommendedPosts);
        
        // Supprimer les doublons en utilisant l'ID du post comme clé
        $uniquePosts = [];
        foreach ($allPosts as $post) {
            $uniquePosts[$post->getId()] = $post;
        }
        
        // Trier les posts par date de création (du plus récent au plus ancien)
        usort($uniquePosts, function ($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        // Limiter le nombre de résultats
        return array_slice($uniquePosts, 0, $limit);
    }

    /**
     * Récupère les posts de l'utilisateur
     */
    private function getUserPosts(User $user): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p, a, s')
           ->from('App\Entity\Post', 'p')
           ->leftJoin('p.author', 'a')
           ->leftJoin('p.shares', 's')
           ->leftJoin('s.user', 'su')
           ->where('p.author = :userId')
           ->setParameter('userId', $user->getId())
           ->orderBy('p.createdAt', 'DESC');
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les posts des amis de l'utilisateur
     */
    private function getFriendsPosts(User $user): array
    {
        // Récupérer les demandes d'amitié acceptées où l'utilisateur est le demandeur
        $sentFriendships = $user->getSentFriendRequests()->filter(function($friendship) {
            return $friendship->getStatus() === Friendship::STATUS_ACCEPTED;
        });
        
        // Récupérer les demandes d'amitié acceptées où l'utilisateur est le destinataire
        $receivedFriendships = $user->getReceivedFriendRequests()->filter(function($friendship) {
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
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p, a, s')
           ->from('App\Entity\Post', 'p')
           ->leftJoin('p.author', 'a')
           ->leftJoin('p.shares', 's')
           ->leftJoin('s.user', 'su')
           ->where('p.author IN (:friendIds)')
           ->setParameter('friendIds', $friendIds)
           ->orderBy('p.createdAt', 'DESC');
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Recommandations personnalisées basées sur les centres d'intérêt
     */
    private function getPersonalizedRecommendations(User $user, int $limit): array
    {
        // Cette méthode utilise un algorithme plus sophistiqué pour recommander du contenu pertinent
        
        // 1. Récupérer les IDs des posts que l'utilisateur a déjà vus/aimés
        $userPostIds = $user->getPosts()->map(function($post) {
            return $post->getId();
        })->toArray();
        
        // 2. Récupérer les IDs des auteurs avec qui l'utilisateur interagit souvent
        $interactedUserIds = $this->getFrequentlyInteractedUsers($user);
        
        // 3. Récupérer les hashtags que l'utilisateur suit ou utilise fréquemment
        $userInterests = $this->getUserInterestsAndHashtags($user);
        
        try {
            // Construire une requête DQL personnalisée pour trouver des posts intéressants
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('p, a, s, su, RAND() as HIDDEN rand')
               ->from('App\Entity\Post', 'p')
               ->leftJoin('p.author', 'a')
               ->leftJoin('p.hashtags', 'h')
               ->leftJoin('p.shares', 's')
               ->leftJoin('s.user', 'su')
               ->where('p.author != :userId') // Exclure les posts de l'utilisateur (déjà inclus séparément)
               ->setParameter('userId', $user->getId());
            
            // Ajouter une clause pour exclure les posts que l'utilisateur a déjà vus
            if (!empty($userPostIds)) {
                $qb->andWhere('p.id NOT IN (:userPostIds)')
                   ->setParameter('userPostIds', $userPostIds);
            }
            
            // Booster le score des posts ayant des hashtags d'intérêt pour l'utilisateur
            if (!empty($userInterests)) {
                $qb->leftJoin('p.hashtags', 'ph')
                   ->orWhere('ph.name IN (:interests)')
                   ->setParameter('interests', $userInterests);
            }
            
            // Booster le score des posts des utilisateurs avec qui l'utilisateur interagit souvent
            if (!empty($interactedUserIds)) {
                $qb->orWhere('p.author IN (:interactedUserIds)')
                   ->setParameter('interactedUserIds', $interactedUserIds);
            }
            
            // Privilégier les posts récents et populaires
            $qb->orderBy('p.likesCounter', 'DESC')
               ->addOrderBy('p.createdAt', 'DESC')
               ->addOrderBy('rand')
               ->setMaxResults($limit * 2); // Récupérer plus de résultats pour avoir plus de diversité
            
            $result = $qb->getQuery()->getResult();
            
            // Filtrer uniquement les posts (ignorer la valeur RAND())
            $filteredResult = [];
            foreach ($result as $item) {
                if ($item instanceof Post) {
                    $filteredResult[] = $item;
                }
            }
            
            // Assurer que les champs JSON ne sont pas null
            foreach ($filteredResult as $post) {
                if ($post->getMentions() === null) {
                    $post->setMentions([]);
                }
                if ($post->getReactionCounts() === null) {
                    $post->updateReactionCounts();
                }
            }
            
            // Mélanger les résultats pour plus de diversité
            shuffle($filteredResult);
            
            return array_slice($filteredResult, 0, $limit);
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide
            return [];
        }
    }
    
    /**
     * Récupère les utilisateurs avec qui l'utilisateur interagit fréquemment
     */
    private function getFrequentlyInteractedUsers(User $user): array
    {
        try {
            // 1. Utilisateurs dont l'utilisateur a aimé des posts
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('DISTINCT p.author')
               ->from('App\Entity\PostLike', 'pl')
               ->leftJoin('pl.post', 'p')
               ->where('pl.user = :userId')
               ->setParameter('userId', $user->getId());
            
            $likedAuthors = $qb->getQuery()->getResult();
            
            // 2. Utilisateurs dont l'utilisateur a commenté des posts
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('DISTINCT p.author')
               ->from('App\Entity\PostComment', 'pc')
               ->leftJoin('pc.post', 'p')
               ->where('pc.author = :userId')
               ->setParameter('userId', $user->getId());
            
            $commentedAuthors = $qb->getQuery()->getResult();
            
            // Fusionner les deux ensembles
            $interactedUsers = array_merge($likedAuthors, $commentedAuthors);
            
            // Extraire uniquement les IDs
            $interactedUserIds = [];
            foreach ($interactedUsers as $author) {
                if ($author instanceof User) {
                    $interactedUserIds[] = $author->getId();
                }
            }
            
            return array_unique($interactedUserIds);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Récupère les centres d'intérêt et hashtags fréquemment utilisés par l'utilisateur
     */
    private function getUserInterestsAndHashtags(User $user): array
    {
        try {
            // 1. Hashtags utilisés dans les posts de l'utilisateur
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('h.name')
               ->from('App\Entity\Post', 'p')
               ->leftJoin('p.hashtags', 'h')
               ->where('p.author = :userId')
               ->setParameter('userId', $user->getId());
            
            $userHashtags = $qb->getQuery()->getResult();
            
            // 2. Hashtags des posts aimés par l'utilisateur
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('h.name')
               ->from('App\Entity\PostLike', 'pl')
               ->leftJoin('pl.post', 'p')
               ->leftJoin('p.hashtags', 'h')
               ->where('pl.user = :userId')
               ->setParameter('userId', $user->getId());
            
            $likedHashtags = $qb->getQuery()->getResult();
            
            // Fusionner et aplatir les résultats
            $allHashtags = array_merge($userHashtags, $likedHashtags);
            $flattenedHashtags = [];
            
            foreach ($allHashtags as $item) {
                if (isset($item['name'])) {
                    $flattenedHashtags[] = $item['name'];
                }
            }
            
            return array_unique($flattenedHashtags);
        } catch (\Exception $e) {
            return [];
        }
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
        // Récupérer les demandes d'amitié acceptées où l'utilisateur est le demandeur
        $sentFriendships = $user->getSentFriendRequests()->filter(function($friendship) {
            return $friendship->getStatus() === Friendship::STATUS_ACCEPTED;
        });
        
        // Récupérer les demandes d'amitié acceptées où l'utilisateur est le destinataire
        $receivedFriendships = $user->getReceivedFriendRequests()->filter(function($friendship) {
            return $friendship->getStatus() === Friendship::STATUS_ACCEPTED;
        });
        
        // Ajouter le user lui-même
        $allUserIds = [$user->getId()];
        
        // Ajouter les amis à la liste
        foreach ($sentFriendships as $friendship) {
            $allUserIds[] = $friendship->getAddressee()->getId();
        }
        
        foreach ($receivedFriendships as $friendship) {
            $allUserIds[] = $friendship->getRequester()->getId();
        }
        
        if (empty($allUserIds)) {
            return [];
        }
        
        try {
            // Récupérer les posts qui ont été partagés avec toutes les relations nécessaires
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('DISTINCT p, a, s, su')
               ->from('App\Entity\Post', 'p')
               ->innerJoin('p.shares', 's')
               ->innerJoin('s.user', 'su')
               ->leftJoin('p.author', 'a')
               ->where('s.user IN (:userIds)')
               ->setParameter('userIds', $allUserIds)
               ->orderBy('s.createdAt', 'DESC');
            
            return $qb->getQuery()->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }
} 