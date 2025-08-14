<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;

#[Route('/api')]
class MentionApiController extends AbstractController
{
    #[Route('/mention-suggestions', name: 'app_api_mention_suggestions', methods: ['GET'])]
    public function getMentionSuggestions(Request $request, UserRepository $userRepository): JsonResponse
    {
        // Récupérer le terme de recherche
        $searchTerm = $request->query->get('q', '');

        // Limiter le nombre de résultats
        $limit = 10;

        // Obtenir l'utilisateur actuel
        $currentUser = $this->getUser();

        // Rechercher les utilisateurs correspondants
        $users = $userRepository->findByNameLike($searchTerm, $limit, $currentUser);

        // Formater les résultats
        $formattedResults = [];
        foreach ($users as $user) {
            $formattedResults[] = [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'username' => $user->getUsername(),
                'profilePicture' => $user->getProfilePicture(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'results' => $formattedResults
        ]);
    }

    #[Route('/hashtag-suggestions', name: 'app_api_hashtag_suggestions', methods: ['GET'])]
    public function getHashtagSuggestions(Request $request): JsonResponse
    {
        // Cette méthode serait implémentée si vous avez besoin de suggestions de hashtags
        return new JsonResponse([
            'success' => true,
            'results' => []
        ]);
    }
}
