<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'app_notification_index', methods: ['GET'])]
    public function index(NotificationRepository $notifRepo): Response
    {
        $user = $this->getUser();
        $notifications = $notifRepo->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/unread', name: 'app_notification_unread', methods: ['GET'])]
    public function unread(NotificationRepository $notifRepo): Response
    {
        $notifications = $notifRepo->findUnreadByUser($this->getUser());

        return $this->render('notification/unread.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/count', name: 'app_notification_count', methods: ['GET'])]
    public function count(NotificationRepository $notifRepo): JsonResponse
    {
        $count = $notifRepo->countUnreadByUser($this->getUser());

        return $this->json(['count' => $count]);
    }

    #[Route('/api/notifications/count', name: 'app_api_notification_count', methods: ['GET'])]
    public function apiCount(NotificationRepository $notifRepo, Request $request): JsonResponse
    {
        $response = new JsonResponse(['count' => $notifRepo->countUnreadByUser($this->getUser())]);
        
        // Cache pour 30 secondes avec validation ETag
        $response->setPublic();
        $response->setMaxAge(30);
        $response->setEtag(md5($response->getContent()));
        
        // Vérifie si la réponse a changé
        if ($response->isNotModified($request)) {
            return $response;
        }
        
        return $response;
    }

    #[Route('/{id}/mark-as-read', name: 'app_notification_mark_as_read', methods: ['POST'])]
    public function markAsRead(
        Notification $notification,
        EntityManagerInterface $entityManager,
        Request $request
    ) {
        // Vérifier que la notification appartient à l'utilisateur connecté
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette notification.');
        }

        // Marquer la notification comme lue
        $notification->setIsRead(true);
        $entityManager->flush();

        // Si c'est une requête AJAX, retourner une réponse JSON
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        // Rediriger vers l'URL cible de la notification
        $targetUrl = $notification->getTargetUrl() ?: $this->generateUrl('app_notification_index');
        return $this->redirect($targetUrl);
    }

    #[Route('/mark-all-as-read', name: 'app_notification_mark_all_as_read', methods: ['POST'])]
    public function markAllAsRead(
        NotificationRepository $notifRepo,
        EntityManagerInterface $entityManager,
        Request $request
    ) {
        $user = $this->getUser();
        $unreadNotifications = $notifRepo->findBy(['user' => $user, 'isRead' => false]);

        foreach ($unreadNotifications as $notification) {
            $notification->setIsRead(true);
        }

        $entityManager->flush();

        // Si c'est une requête AJAX, retourner une réponse JSON
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        // Rediriger vers la page des notifications
        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/{id}/delete', name: 'app_notification_delete', methods: ['POST'])]
    public function delete(
        Notification $notification,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->getUser() !== $this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Notification non trouvée'], 404);
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }
}
