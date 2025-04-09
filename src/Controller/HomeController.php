<?php

namespace App\Controller;

use App\Repository\HashtagRepository;
use App\Repository\JobOfferRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private CacheInterface $cache,
        private EntityManagerInterface $entityManager
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

        // Précharger les collections si l'utilisateur est connecté
        $userStats = [
            'posts_count' => 0,
            'friends_count' => 0,
            'job_offers_count' => 0
        ];
        
        if ($user) {
            $this->entityManager->initializeObject($user);
            $userStats = [
                'posts_count' => $user->getPosts()->count(),
                'friends_count' => count($user->getFriends()),
                'job_offers_count' => $user->isRecruiter() ? $user->getJobOffers()->count() : 0
            ];
        }

        $cacheKey = 'homepage_data_' . ($user ? $user->getId() : 'anonymous');

        $data = $this->cache->get($cacheKey, function (ItemInterface $item) use (
            $postRepository,
            $userRepository,
            $hashtagRepository,
            $jobOfferRepository,
            $user
        ) {
            $item->expiresAfter(300); // Cache pour 5 minutes

            $data = [
                'activeJobOffers' => $jobOfferRepository->findActiveOffers(5),
                'trendingHashtags' => $hashtagRepository->findTrending(10),
                'stats' => [
                    'recruiters' => $userRepository->findByRole('ROLE_RECRUITER'),
                    'applicants' => $userRepository->findByRole('ROLE_APPLICANT')
                ],
                'recentPosts' => $postRepository->findRecentPosts(10)
            ];

            if ($user) {
                $data['recommendedPosts'] = $postRepository->findRecentPosts(10);
                $data['suggestedUsers'] = $userRepository->findSuggestedUsers($user, 5);
            }

            return $data;
        });

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
