<?php

namespace App\Controller;

use App\Entity\Friendship;
use App\Entity\User;
use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/friendship')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class FriendshipController extends AbstractController
{
    #[Route('/requests', name: 'app_friendship_requests')]
    public function requests(FriendshipRepository $friendshipRepository): Response
    {
        $user = $this->getUser();
        
        $pendingRequests = $friendshipRepository->findPendingRequestsReceived($user);
        $sentRequests = $friendshipRepository->findPendingRequestsSent($user);
        $friends = $friendshipRepository->findFriends($user);
        
        return $this->render('friendship/requests.html.twig', [
            'pendingRequests' => $pendingRequests,
            'sentRequests' => $sentRequests,
            'friends' => $friends,
        ]);
    }
    
    #[Route('/send/{id}', name: 'app_friendship_send')]
    public function send($id, Request $request, EntityManagerInterface $entityManager, FriendshipRepository $friendshipRepository): Response
    {
        $requester = $this->getUser();
        
        // Récupérer l'utilisateur destinataire par son ID
        $userRepository = $entityManager->getRepository(User::class);
        $addressee = $userRepository->find($id);
        
        // Vérifier si l'utilisateur existe
        if (!$addressee) {
            $this->addFlash('error', 'L\'utilisateur n\'existe pas.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }
        
        // Vérifier que l'utilisateur n'essaie pas de s'envoyer une demande à lui-même
        if ($requester === $addressee) {
            $this->addFlash('error', 'Vous ne pouvez pas vous envoyer une demande d\'amitié à vous-même.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }
        
        // Vérifier s'il existe déjà une demande d'amitié entre ces utilisateurs
        $existingFriendship = $friendshipRepository->findBetweenUsers($requester, $addressee);
        
        if ($existingFriendship) {
            if ($existingFriendship->isPending()) {
                $this->addFlash('info', 'Une demande d\'amitié est déjà en cours avec cet utilisateur.');
            } elseif ($existingFriendship->isAccepted()) {
                $this->addFlash('info', 'Vous êtes déjà ami avec cet utilisateur.');
            } else {
                $this->addFlash('info', 'Une demande d\'amitié a déjà été traitée avec cet utilisateur.');
            }
        } else {
            // Créer une nouvelle demande d'amitié
            $friendship = new Friendship();
            $friendship->setRequester($requester);
            $friendship->setAddressee($addressee);
            
            $entityManager->persist($friendship);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre demande d\'amitié a été envoyée.');
        }
        
        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
    }
    
    #[Route('/accept/{id}', name: 'app_friendship_accept')]
    public function accept(Friendship $friendship, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est bien le destinataire de la demande
        if ($friendship->getAddressee() !== $user) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à accepter cette demande d\'amitié.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_friendship_requests')));
        }
        
        // Vérifier que la demande est en attente
        if (!$friendship->isPending()) {
            $this->addFlash('error', 'Cette demande d\'amitié a déjà été traitée.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_friendship_requests')));
        }
        
        // Accepter la demande
        $friendship->accept();
        $entityManager->flush();
        
        $this->addFlash('success', 'Vous êtes maintenant ami avec ' . $friendship->getRequester()->getFullName() . '.');
        
        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_friendship_requests')));
    }
    
    #[Route('/decline/{id}', name: 'app_friendship_decline')]
    public function decline(Friendship $friendship, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur actuel est bien le destinataire de la demande
        if ($friendship->getAddressee() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_friendship_requests')));
        }

        $friendship->decline();
        $entityManager->flush();

        $this->addFlash('success', 'Demande d\'amitié refusée.');
        return $this->redirectToRoute('app_friendship_requests');
    }
    
    #[Route('/cancel/{id}', name: 'app_friendship_cancel')]
    public function cancel(Friendship $friendship, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur actuel est bien l'expéditeur de la demande
        if ($friendship->getRequester() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
            return $this->redirectToRoute('app_friendship_requests');
        }

        // Supprimer la demande d'amitié
        $entityManager->remove($friendship);
        $entityManager->flush();

        $this->addFlash('success', 'Demande d\'amitié annulée.');
        return $this->redirectToRoute('app_friendship_requests');
    }
    
    #[Route('/remove/{id}', name: 'app_friendship_remove')]
    public function remove($id, Request $request, EntityManagerInterface $entityManager, FriendshipRepository $friendshipRepository): Response
    {
        $user = $this->getUser();
        
        // Récupérer l'utilisateur ami par son ID
        $userRepository = $entityManager->getRepository(User::class);
        $friend = $userRepository->find($id);
        
        // Vérifier si l'utilisateur existe
        if (!$friend) {
            $this->addFlash('error', 'L\'utilisateur n\'existe pas.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_friendship_requests')));
        }
        
        // Vérifier s'il existe une amitié entre ces utilisateurs
        $friendship = $friendshipRepository->findBetweenUsers($user, $friend);
        
        if (!$friendship || !$friendship->isAccepted()) {
            $this->addFlash('error', 'Vous n\'êtes pas ami avec cet utilisateur.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_friendship_requests')));
        }
        
        // Supprimer l'amitié
        $entityManager->remove($friendship);
        $entityManager->flush();
        
        $this->addFlash('success', 'Vous avez retiré ' . $friend->getFullName() . ' de vos amis.');
        
        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_friendship_requests')));
    }
} 