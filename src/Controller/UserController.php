<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/{userId}/profile', name: 'app_user_profile', requirements: ['userId' => '\d+'])]
    #[Route('/u/{username}', name: 'app_user_profile_by_username')]
    public function profile(
        EntityManagerInterface $entityManager,
        ?int $userId = null,
        ?string $username = null
    ): Response {
        // Récupérer l'utilisateur par son ID ou son nom d'utilisateur
        $userRepository = $entityManager->getRepository(User::class);

        $user = null;
        if ($userId) {
            $user = $userRepository->find($userId);
        } elseif ($username) {
            $user = $userRepository->findByUsername($username);
        }

        // Vérifier si l'utilisateur existe
        if (!$user) {
            throw $this->createNotFoundException('L\'utilisateur n\'existe pas.');
        }

        $currentUser = $this->getUser();

        // Récupérer les posts de l'utilisateur
        $posts = $entityManager->getRepository(\App\Entity\Post::class)->findBy(
            ['author' => $user],
            ['createdAt' => 'DESC']
        );

        // Récupérer les statistiques
        $postsCount = count($posts);

        // Vérifier les relations d'amitié
        $friendshipInfo = [
            'isFriend' => false,
            'hasPendingRequestFrom' => false,
            'hasPendingRequestTo' => false,
            'pendingRequestId' => null
        ];

        if ($currentUser && $currentUser !== $user) {
            $friendshipRepository = $entityManager->getRepository(\App\Entity\Friendship::class);

            // Vérifier si l'utilisateur est déjà ami avec l'utilisateur courant
            $friendship = $friendshipRepository->findAcceptedBetweenUsers($currentUser, $user);
            $friendshipInfo['isFriend'] = ($friendship !== null);

            // Vérifier si l'utilisateur courant a envoyé une demande d'amitié à cet utilisateur
            $pendingRequest = $friendshipRepository->findPendingRequestBetween($currentUser, $user);
            if ($pendingRequest !== null && $pendingRequest->getRequester() === $currentUser) {
                $friendshipInfo['hasPendingRequestFrom'] = true;
            }

            // Vérifier si l'utilisateur a envoyé une demande d'amitié à l'utilisateur courant
            $pendingRequestTo = $friendshipRepository->findPendingRequestBetween($user, $currentUser);
            if ($pendingRequestTo !== null && $pendingRequestTo->getRequester() === $user) {
                $friendshipInfo['hasPendingRequestTo'] = true;
                $friendshipInfo['pendingRequestId'] = $pendingRequestTo->getId();
            }
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'posts' => $posts,
            'postsCount' => $postsCount,
            'friendshipInfo' => $friendshipInfo
        ]);
    }

    #[Route('/search', name: 'app_user_search', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function searchUsers(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $request->query->get('q', '');
        $userRepository = $entityManager->getRepository(User::class);

        if (strlen($query) < 2) {
            return $this->json(['users' => []]);
        }

        $users = $userRepository->searchUsers($query, $this->getUser());

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'fullName' => $user->getFullName(),
                'username' => $user->getUsername() ?? $user->getId(),
                'profilePicture' => $user->getProfilePicture(),
                'isFriend' => $user->isFriend
            ];
        }

        return $this->json(['users' => $results]);
    }
}
