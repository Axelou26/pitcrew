<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Friendship;
use App\Entity\Post;
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
        $user           = $this->findUser($entityManager, $userId, $username);
        $currentUser    = $this->getUser();
        $posts          = $this->getUserPosts($entityManager, $user);
        $friendshipInfo = $this->getFriendshipInfo($entityManager, $currentUser, $user);

        return $this->render('user/profile.html.twig', [
            'user'           => $user,
            'posts'          => $posts,
            'postsCount'     => \count($posts),
            'friendshipInfo' => $friendshipInfo,
        ]);
    }

    #[Route('/search', name: 'app_user_search', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function searchUsers(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query          = $request->query->get('q', '');
        $userRepository = $entityManager->getRepository(User::class);

        if (\strlen($query) < 2) {
            return $this->json(['users' => []]);
        }

        $users = $userRepository->searchUsers($query, $this->getUser());

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id'             => $user->getId(),
                'fullName'       => $user->getFullName(),
                'username'       => $user->getUsername() ?? $user->getId(),
                'profilePicture' => $user->getProfilePicture(),
                'isFriend'       => $user->isFriend,
            ];
        }

        return $this->json(['users' => $results]);
    }

    #[Route('/suggestions', name: 'app_user_suggestions')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function suggestions(EntityManagerInterface $entityManager): Response
    {
        $user           = $this->getUser();
        $userRepository = $entityManager->getRepository(User::class);

        // Récupérer plus de suggestions (par exemple 20)
        $suggestedUsers = $userRepository->findSuggestedUsers($user, 20);

        return $this->render('user/suggestions.html.twig', [
            'suggestedUsers' => $suggestedUsers,
        ]);
    }

    private function findUser(
        EntityManagerInterface $entityManager,
        ?int $userId,
        ?string $username
    ): User {
        $userRepository = $entityManager->getRepository(User::class);

        $user = null;
        if ($userId) {
            $user = $userRepository->find($userId);
        } elseif ($username) {
            $user = $userRepository->findByUsername($username);
        }

        if (!$user) {
            throw $this->createNotFoundException('L\'utilisateur n\'existe pas.');
        }

        return $user;
    }

    private function getUserPosts(EntityManagerInterface $entityManager, User $user): array
    {
        return $entityManager->getRepository(Post::class)->findBy(
            ['author' => $user],
            ['createdAt' => 'DESC']
        );
    }

    private function getFriendshipInfo(
        EntityManagerInterface $entityManager,
        ?User $currentUser,
        User $user
    ): array {
        $friendshipInfo = [
            'isFriend'              => false,
            'hasPendingRequestFrom' => false,
            'hasPendingRequestTo'   => false,
            'pendingRequestId'      => null,
        ];

        if (!$currentUser || $currentUser === $user) {
            return $friendshipInfo;
        }

        $friendshipRepository = $entityManager->getRepository(Friendship::class);

        $friendship                 = $friendshipRepository->findAcceptedBetweenUsers($currentUser, $user);
        $friendshipInfo['isFriend'] = ($friendship !== null);

        $pendingRequest = $friendshipRepository->findPendingRequestBetween($currentUser, $user);
        if ($pendingRequest && $pendingRequest->getRequester() === $currentUser) {
            $friendshipInfo['hasPendingRequestFrom'] = true;
        }

        $pendingRequestTo = $friendshipRepository->findPendingRequestBetween($user, $currentUser);
        if ($pendingRequestTo && $pendingRequestTo->getRequester() === $user) {
            $friendshipInfo['hasPendingRequestTo'] = true;
            $friendshipInfo['pendingRequestId']    = $pendingRequestTo->getId();
        }

        return $friendshipInfo;
    }
}
