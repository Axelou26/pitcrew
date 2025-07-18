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
use Psr\Log\LoggerInterface;

#[Route('/notifications')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class NotificationController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }
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
    public function unread(NotificationRepository $notifRepo, Request $request): Response
    {
        $notifications = $notifRepo->findUnreadByUser($this->getUser());

        // Si c'est une requête AJAX, retourner seulement le contenu des notifications
        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('notification/_notifications_list.html.twig', [
                'notifications' => $notifications,
            ]);

            return new Response($html, 200, [
                'Content-Type' => 'text/html',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        }

        // Sinon, retourner la page complète
        return $this->render('notification/unread.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/{id}/mark-as-read', name: 'app_notification_mark_as_read', methods: ['POST'])]
    public function markAsRead(
        Notification $notification,
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository,
        Request $request
    ) {
        // Vérifier que la notification appartient à l'utilisateur connecté
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette notification.');
        }

        // Marquer la notification comme lue
        $notification->setIsRead(true);
        $entityManager->flush();

        // Invalider le cache des notifications pour cet utilisateur
        $notificationRepository->invalidateUserCache($this->getUser());

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

        // Invalider le cache des notifications pour cet utilisateur
        $notifRepo->invalidateUserCache($user);

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
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository
    ): JsonResponse {
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->getUser() !== $this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Notification non trouvée'], 404);
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        // Invalider le cache des notifications pour cet utilisateur
        $notificationRepository->invalidateUserCache($this->getUser());

        return $this->json(['success' => true]);
    }
}
