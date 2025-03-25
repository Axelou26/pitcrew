<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Repository\JobOfferRepository;
use App\Repository\FriendshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        PostRepository $postRepository, 
        UserRepository $userRepository, 
        JobOfferRepository $jobOfferRepository,
        FriendshipRepository $friendshipRepository,
        \App\Service\RecommendationService $recommendationService,
        \App\Repository\HashtagRepository $hashtagRepository
    ): Response
    {
        // Récupérer les offres d'emploi actives
        $offers = $jobOfferRepository->findActiveOffers();
        
        // Récupérer les publications
        $user = $this->getUser();
        
        if ($user) {
            // Si l'utilisateur est connecté, utiliser le service de recommandation
            $posts = $recommendationService->getRecommendedPosts($user, 12);
            
            // Récupérer les utilisateurs suggérés
            $suggestedUsers = $recommendationService->getSuggestedUsers($user, 3);
            
            // Enrichir les utilisateurs suggérés avec les informations d'amitié
            foreach ($suggestedUsers as $suggestedUser) {
                // Vérifier si l'utilisateur est déjà ami avec l'utilisateur suggéré
                $friendship = $friendshipRepository->findAcceptedBetweenUsers($user, $suggestedUser);
                $suggestedUser->isFriend = ($friendship !== null);
                
                // Vérifier si l'utilisateur a envoyé une demande d'amitié à l'utilisateur suggéré
                $pendingRequest = $friendshipRepository->findPendingRequestBetweenUsers($user, $suggestedUser, true);
                $suggestedUser->hasPendingRequestFrom = ($pendingRequest !== null);
                
                // Vérifier si l'utilisateur suggéré a envoyé une demande d'amitié à l'utilisateur
                $pendingRequestTo = $friendshipRepository->findPendingRequestBetweenUsers($suggestedUser, $user, true);
                $suggestedUser->hasPendingRequestTo = ($pendingRequestTo !== null);
                if ($pendingRequestTo) {
                    $suggestedUser->pendingRequestId = $pendingRequestTo->getId();
                }
            }
        } else {
            // Sinon, afficher simplement les posts récents
            $posts = $postRepository->findBy([], ['createdAt' => 'DESC'], 6);
            $suggestedUsers = [];
        }
        
        // Compter les recruteurs et les candidats
        $recruiters = $userRepository->findByRole('ROLE_RECRUTEUR');
        $applicants = $userRepository->findByRole('ROLE_POSTULANT');
        
        // Compter directement les utilisateurs avec le rôle ROLE_POSTULANT
        $applicantsCount = count($applicants);
        
        // Récupérer les hashtags tendance
        $trendingHashtags = $hashtagRepository->findTrending(5);
        
        return $this->render('home/index.html.twig', [
            'offers' => $offers,
            'posts' => $posts,
            'recruiters' => $recruiters,
            'applicants' => $applicants,
            'applicantsCount' => $applicantsCount,
            'suggestedUsers' => $suggestedUsers,
            'trendingHashtags' => $trendingHashtags,
        ]);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }
} 