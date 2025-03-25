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
    #[Route('/{id}/profile', name: 'app_user_profile')]
    public function profile(User $user, EntityManagerInterface $entityManager): Response
    {
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
            $pendingRequest = $friendshipRepository->findPendingRequestBetweenUsers($currentUser, $user, true);
            $friendshipInfo['hasPendingRequestFrom'] = ($pendingRequest !== null);
            
            // Vérifier si l'utilisateur a envoyé une demande d'amitié à l'utilisateur courant
            $pendingRequestTo = $friendshipRepository->findPendingRequestBetweenUsers($user, $currentUser, true);
            $friendshipInfo['hasPendingRequestTo'] = ($pendingRequestTo !== null);
            if ($pendingRequestTo) {
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
} 