<?php

namespace App\Controller;

use App\Repository\HashtagRepository;
use App\Repository\JobOfferRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private CacheInterface $cache,
        private EntityManagerInterface $entityManager,
        private RecommendationService $recommendationService
    ) {
    }

    /**
     * Page d'accueil
     */
    #[Route('/', name: 'app_home')]
    public function index(
        PostRepository $postRepository,
        UserRepository $userRepository,
        HashtagRepository $hashtagRepository,
        JobOfferRepository $jobOfferRepository
    ): Response {
        $user = $this->getUser();
        $userId = $user ? $user->getId() : 'anonymous';

        // Cache séparé pour les statistiques utilisateur
        $userStatsCacheKey = 'user_stats_' . $userId;
        $userStats = $this->cache->get($userStatsCacheKey, function (ItemInterface $item) use ($user) {
            $item->expiresAfter(600); // Cache pour 10 minutes

            if (!$user) {
                return [
                    'posts_count' => 0,
                    'friends_count' => 0,
                    'job_offers_count' => 0
                ];
            }

            // Utiliser des requêtes optimisées au lieu de compter les collections
            $postsCount = $this->entityManager->createQuery(
                'SELECT COUNT(p.id) FROM App\Entity\Post p WHERE p.author = :user'
            )->setParameter('user', $user)->getSingleScalarResult();

            $friendsCount = $this->entityManager->createQuery(
                'SELECT COUNT(f.id) FROM App\Entity\Friendship f 
                 WHERE (f.requester = :user OR f.addressee = :user) 
                 AND f.status = :status'
            )->setParameter('user', $user)
             ->setParameter('status', 'accepted')
             ->getSingleScalarResult();

            $jobOffersCount = 0;
            if ($user->isRecruiter()) {
                $jobOffersCount = $this->entityManager->createQuery(
                    'SELECT COUNT(j.id) FROM App\Entity\JobOffer j WHERE j.recruiter = :user'
                )->setParameter('user', $user)->getSingleScalarResult();
            }

            return [
                'posts_count' => $postsCount,
                'friends_count' => $friendsCount,
                'job_offers_count' => $jobOffersCount
            ];
        });

        // Récupérer les données directement sans cache pour les posts
        $data = [
            'activeJobOffers' => $jobOfferRepository->findActiveOffers(5),
            'trendingHashtags' => $hashtagRepository->findTrending(10),
            'stats' => [
                'recruiters' => $userRepository->findByRole('ROLE_RECRUTEUR'),
                'applicants' => $userRepository->findByRole('ROLE_POSTULANT')
            ],
            'recentPosts' => $postRepository->findRecentWithAuthors(10)
        ];

        if ($user) {
            $data['recommendedPosts'] = $this->recommendationService->getRecommendedPosts($user, 10);
            $data['suggestedUsers'] = $userRepository->findSuggestedUsers($user, 5);
        }

        // Ajouter les statistiques de l'utilisateur aux données
        $data['user_stats'] = $userStats;

        return $this->render('home/index.html.twig', $data);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }
}
