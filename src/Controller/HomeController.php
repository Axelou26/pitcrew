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
    public function index(
        PostRepository $postRepository,
        UserRepository $userRepository,
        HashtagRepository $hashtagRepository,
        JobOfferRepository $jobOfferRepository,
        CacheInterface $cache
    ): Response {
        $data = $cache->get(
            'homepage_data_' . ($this->getUser() ? $this->getUser()->getId() : 'anonymous'),
            function (ItemInterface $item) use (
                $postRepository,
                $userRepository,
                $hashtagRepository,
                $jobOfferRepository
            ) {
                $item->expiresAfter(300); // Cache pour 5 minutes

                $user = $this->getUser();
                $data = [
                    'activeJobOffers' => $jobOfferRepository->findActiveJobOffers(5),
                    'trendingHashtags' => $hashtagRepository->findTrendingHashtags(10),
                    'recentPosts' => $postRepository->findRecentPosts(10)
                ];

                if ($user) {
                    $data['recommendedPosts'] = $postRepository->findRecentPosts(10);
                    $data['suggestedUsers'] = $userRepository->findSuggestedUsers($user, 5);
                    $data['stats'] = [
                        'recruiters' => $userRepository->count(['roles' => ['ROLE_RECRUITER']]),
                        'applicants' => $userRepository->count(['roles' => ['ROLE_APPLICANT']])
                    ];
                    unset($data['recentPosts']); // On n'affiche pas les posts récents pour les utilisateurs connectés
                }

                return $data;
            }
        );

        return $this->render('home/index.html.twig', $data);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }
}
