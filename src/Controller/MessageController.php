<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messages')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_message_index', methods: ['GET'])]
    public function index(ConversationRepository $convRepo): Response
    {
        $conversations = $convRepo->findConversationsForUser($this->getUser());

        return $this->render('message/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_message_conversation', methods: ['GET'])]
    public function conversation(
        Conversation $conversation,
        MessageRepository $messageRepository,
        ConversationRepository $convRepo,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que l'utilisateur fait partie de la conversation
        if (
            $conversation->getParticipant1() !== $this->getUser() &&
            $conversation->getParticipant2() !== $this->getUser()
        ) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer toutes les conversations de l'utilisateur
        $conversations = $convRepo->findConversationsForUser($this->getUser());

        // Marquer les messages comme lus
        $unreadMessages = $messageRepository->findUnreadMessagesInConversation(
            $conversation,
            $this->getUser()
        );

        foreach ($unreadMessages as $message) {
            $message->setIsRead(true);
        }
        $entityManager->flush();

        return $this->render('message/conversation.html.twig', [
            'conversation' => $conversation,
            'conversations' => $conversations,
        ]);
    }

    #[Route('/conversation/{id}/send', name: 'app_message_send', methods: ['POST'])]
    public function send(
        Request $request,
        Conversation $conversation,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifier que l'utilisateur fait partie de la conversation
        if (
            $conversation->getParticipant1() !== $this->getUser() &&
            $conversation->getParticipant2() !== $this->getUser()
        ) {
            throw $this->createAccessDeniedException();
        }

        $content = trim($request->request->get('content', ''));
        if (empty($content)) {
            return new JsonResponse(['error' => 'Le message ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        $message = new Message();
        $message->setSender($this->getUser());
        $message->setRecipient($conversation->getOtherParticipant($this->getUser()));
        $message->setContent($content);
        $message->setConversation($conversation);

        $entityManager->persist($message);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            'sender' => [
                'id' => $message->getSender()->getId(),
                'fullName' => $message->getSender()->getFullName(),
            ],
        ]);
    }

    #[Route('/start/{conversationId}', name: 'app_message_start', methods: ['GET', 'POST'])]
    public function startConversation(
        Request $request,
        int $conversationId,
        UserRepository $userRepository,
        ConversationRepository $convRepo,
        EntityManagerInterface $entityManager
    ): Response {
        // Trouver l'utilisateur par son ID
        $otherUser = $userRepository->find($conversationId);

        // Vérifier si l'utilisateur existe
        if (!$otherUser) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_message_index');
        }

        // Vérifier qu'on ne démarre pas une conversation avec soi-même
        if ($otherUser === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas démarrer une conversation avec vous-même.');
            return $this->redirectToRoute('app_message_index');
        }

        // Vérifier si l'utilisateur est ami avec le destinataire
        $friendshipRepository = $entityManager->getRepository(\App\Entity\Friendship::class);
        $isFriend = $friendshipRepository->areFriends($this->getUser(), $otherUser);

        if (!$isFriend) {
            $this->addFlash('error', 'Vous pouvez uniquement envoyer des messages à vos amis.');
            return $this->redirectToRoute('app_message_index');
        }

        // Vérifier si une conversation existe déjà
        $conversation = $convRepo->findConversationBetweenUsers(
            $this->getUser(),
            $otherUser
        );

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setParticipant1($this->getUser());
            $conversation->setParticipant2($otherUser);
            $entityManager->persist($conversation);
            $entityManager->flush();
        }

        if ($request->isMethod('POST')) {
            $content = trim($request->request->get('content', ''));
            if (!empty($content)) {
                $message = new Message();
                $message->setSender($this->getUser());
                $message->setRecipient($otherUser);
                $message->setContent($content);
                $message->setConversation($conversation);

                $entityManager->persist($message);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('app_message_conversation', ['id' => $conversation->getId()]);
    }

    #[Route('/unread', name: 'app_message_unread', methods: ['GET'])]
    public function unreadCount(MessageRepository $messageRepository): JsonResponse
    {
        $count = $messageRepository->countUnreadMessages($this->getUser());

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/new-conversation', name: 'app_message_new_conversation', methods: ['POST'])]
    public function newConversation(
        Request $request,
        UserRepository $userRepository,
        ConversationRepository $convRepo,
        EntityManagerInterface $entityManager
    ): Response {
        $recipientId = $request->request->get('recipient');
        $messageContent = trim($request->request->get('message', ''));

        if (!$recipientId || empty($messageContent)) {
            $this->addFlash('error', 'Veuillez sélectionner un destinataire et saisir un message.');
            return $this->redirectToRoute('app_message_index');
        }

        $recipient = $userRepository->find($recipientId);
        if (!$recipient) {
            $this->addFlash('error', 'Le destinataire sélectionné n\'existe pas.');
            return $this->redirectToRoute('app_message_index');
        }

        // Vérifier qu'on ne démarre pas une conversation avec soi-même
        if ($recipient === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas démarrer une conversation avec vous-même.');
            return $this->redirectToRoute('app_message_index');
        }

        // Vérifier si l'utilisateur est ami avec le destinataire
        $friendshipRepository = $entityManager->getRepository(\App\Entity\Friendship::class);
        $isFriend = $friendshipRepository->areFriends($this->getUser(), $recipient);

        if (!$isFriend) {
            $this->addFlash('error', 'Vous pouvez uniquement envoyer des messages à vos amis.');
            return $this->redirectToRoute('app_message_index');
        }

        // Vérifier si une conversation existe déjà
        $conversation = $convRepo->findConversationBetweenUsers(
            $this->getUser(),
            $recipient
        );

        if (!$conversation) {
            // Créer une nouvelle conversation
            $conversation = new Conversation();
            $conversation->setParticipant1($this->getUser());
            $conversation->setParticipant2($recipient);
            $entityManager->persist($conversation);
        }

        // Créer le message
        $message = new Message();
        $message->setSender($this->getUser());
        $message->setRecipient($recipient);
        $message->setContent($messageContent);
        $message->setConversation($conversation);

        $entityManager->persist($message);
        $entityManager->flush();

        $this->addFlash('success', 'Votre message a été envoyé.');
        return $this->redirectToRoute('app_message_conversation', ['id' => $conversation->getId()]);
    }

    #[Route('/get-recipients', name: 'app_message_get_recipients', methods: ['GET'])]
    public function getRecipients(
        UserRepository $userRepository,
        FriendshipRepository $friendshipRepository,
        EntityManagerInterface $entityManager
    ) {
        $currentUser = $this->getUser();

        try {
            // Récupérer uniquement les amis de l'utilisateur
            $friends = $friendshipRepository->findFriends($currentUser);

            // Débogage : compter les amitiés acceptées
            $count = $entityManager->createQuery(
                'SELECT COUNT(f) FROM App\Entity\Friendship f 
                 WHERE (f.requester = :user OR f.addressee = :user)
                 AND f.status = :status'
            )
            ->setParameter('user', $currentUser)
            ->setParameter('status', 'accepted')
            ->getSingleScalarResult();

            $recipients = [];

            // N'utiliser que les amis comme destinataires
            foreach ($friends as $friend) {
                $recipients[] = [
                    'id' => $friend->getId(),
                    'fullName' => $friend->getFullName(),
                    'isRecruiter' => $friend->isRecruiter(),
                    'profilePicture' => $friend->getProfilePicture()
                ];
            }

            return new JsonResponse([
                'recipients' => $recipients,
                'debug' => [
                    'friendCount' => count($friends),
                    'acceptedFriendshipsCount' => $count,
                    'currentUserId' => $currentUser->getId()
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'error' => 'Une erreur est survenue lors de la récupération des contacts.',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
