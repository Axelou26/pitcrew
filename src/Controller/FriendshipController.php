<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Friendship;
use App\Entity\User;
use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        $pendingRequests = $friendshipRepository->findByPendingRequestsReceived($user);
        $sentRequests    = $friendshipRepository->findByPendingRequestsSent($user);
        $friends         = $friendshipRepository->findFriends($user);

        return $this->render('friendship/requests.html.twig', [
            'pendingRequests' => $pendingRequests,
            'sentRequests'    => $sentRequests,
            'friends'         => $friends,
        ]);
    }

    #[Route('/send/{addresseeId}', name: 'app_friendship_send')]
    public function send(
        int $addresseeId,
        Request $request,
        EntityManagerInterface $entityManager,
        FriendshipRepository $friendshipRepository
    ): Response {
        $requester      = $this->getUser();
        $userRepository = $entityManager->getRepository(User::class);
        $addressee      = $userRepository->find($addresseeId);

        // Vérifier si l'utilisateur existe
        if (!$addressee) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'message' => 'L\'utilisateur n\'existe pas.']);
            }
            $this->addFlash('error', 'L\'utilisateur n\'existe pas.');

            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        // Valider la demande d'amitié
        $validationResponse = $this->validateFriendshipRequest($requester, $addressee, $request);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        // Vérifier s'il existe déjà une demande d'amitié
        $existingFriendship = $friendshipRepository->findBetweenUsers($requester, $addressee);
        if ($existingFriendship) {
            return $this->handleExistingFriendship($existingFriendship, $request);
        }

        // Créer une nouvelle demande d'amitié
        $friendship = new Friendship();
        $friendship->setRequester($requester);
        $friendship->setAddressee($addressee);

        $entityManager->persist($friendship);
        $entityManager->flush();

        // Gérer la réponse AJAX
        if ($request->isXmlHttpRequest()) {
            $suggestedUsers = $userRepository->findSuggestedUsers($requester, 5);
            $suggestedUsers = array_filter($suggestedUsers, function ($user) use ($addressee) {
                return $user->getId() !== $addressee->getId();
            });
            $newSuggestion = array_shift($suggestedUsers);

            return $this->createSuggestionResponse($requester, $addressee, $newSuggestion);
        }

        // Réponse standard
        $this->addFlash('success', 'Votre demande d\'amitié a été envoyée.');

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

        $requester = $friendship->getRequester();
        $this->addFlash('success', \sprintf('Vous êtes maintenant ami avec %s.', $requester->getFullName()));

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
    public function remove(
        int $friendId,
        Request $request,
        EntityManagerInterface $entityManager,
        FriendshipRepository $friendshipRepository
    ): Response {
        $user = $this->getUser();

        // Récupérer l'utilisateur ami par son ID
        $userRepository = $entityManager->getRepository(User::class);
        $friend         = $userRepository->find($friendId);

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

    private function validateFriendshipRequest(User $requester, User $addressee, Request $request): ?Response
    {
        // Vérifier que l'utilisateur n'essaie pas de s'envoyer une demande à lui-même
        if ($requester === $addressee) {
            $message = 'Vous ne pouvez pas vous envoyer une demande d\'amitié à vous-même.';
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'message' => $message]);
            }
            $this->addFlash('error', $message);

            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        return null;
    }

    private function handleExistingFriendship(
        Friendship $friendship,
        Request $request
    ): Response {
        $message = 'Une demande d\'amitié a déjà été traitée avec cet utilisateur.';
        if ($friendship->isPending()) {
            $message = 'Une demande d\'amitié est déjà en cours avec cet utilisateur.';
        } elseif ($friendship->isAccepted()) {
            $message = 'Vous êtes déjà ami avec cet utilisateur.';
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'message' => $message]);
        }

        $this->addFlash('info', $message);

        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
    }

    private function createSuggestionResponse(
        User $requester,
        User $addressee,
        ?User $newSuggestion = null
    ): JsonResponse {
        if (!$newSuggestion) {
            return $this->json([
                'success'       => true,
                'message'       => 'Votre demande d\'amitié a été envoyée.',
                'newSuggestion' => null,
            ]);
        }

        return $this->json([
            'success'       => true,
            'message'       => 'Votre demande d\'amitié a été envoyée.',
            'newSuggestion' => [
                'id'             => $newSuggestion->getId(),
                'fullName'       => $newSuggestion->getFullName(),
                'jobTitle'       => $newSuggestion->getJobTitle(),
                'company'        => $newSuggestion->getCompany(),
                'profilePicture' => $newSuggestion->getProfilePicture(),
                'profileUrl'     => $this->generateUrl('app_profile_view', ['id' => $newSuggestion->getId()]),
                'addFriendUrl'   => $this->generateUrl(
                    'app_friendship_send',
                    ['addresseeId' => $newSuggestion->getId()]
                ),
            ],
        ]);
    }
}
