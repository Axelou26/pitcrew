<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Repository\JobOfferRepository;
use App\Repository\FriendshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Repository\HashtagRepository;

class HomeController extends AbstractController
{
    public function __construct(
        private CacheInterface $cache
    ) {
    }

    /**
     * Page d'accueil
     */
    #[Route('/', name: 'app_home')]
    public function index(PostRepository $postRepository, UserRepository $userRepository, HashtagRepository $hashtagRepository, JobOfferRepository $jobOfferRepository, CacheInterface $cache): Response
    {
        // Récupérer les données en parallèle avec le cache
        $data = $cache->get('homepage_data_' . ($this->getUser() ? $this->getUser()->getId() : 'anonymous'), function (ItemInterface $item) use ($postRepository, $userRepository, $hashtagRepository, $jobOfferRepository) {
            $item->expiresAfter(300); // Cache pour 5 minutes

            $user = $this->getUser();
            $data = [];

            // Récupérer les offres d'emploi actives
            $data['activeJobOffers'] = $jobOfferRepository->findActiveJobOffers(5);

            if ($user) {
                // Récupérer les posts recommandés
                $data['recommendedPosts'] = $postRepository->findRecentPosts(10);

                // Récupérer les utilisateurs suggérés
                $data['suggestedUsers'] = $userRepository->findSuggestedUsers($user, 5);

                // Récupérer les hashtags tendance
                $data['trendingHashtags'] = $hashtagRepository->findTrendingHashtags(10);

                // Statistiques
                $data['stats'] = [
                    'recruiters' => $userRepository->count(['roles' => ['ROLE_RECRUITER']]),
                    'applicants' => $userRepository->count(['roles' => ['ROLE_APPLICANT']])
                ];
            } else {
                // Pour les utilisateurs non connectés
                $data['recentPosts'] = $postRepository->findRecentPosts(10);
                $data['trendingHashtags'] = $hashtagRepository->findTrendingHashtags(10);
            }

            return $data;
        });

        return $this->render('home/index.html.twig', $data);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }
}
