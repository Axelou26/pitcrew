<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications')]
class NotificationApiController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    #[Route('/count', name: 'app_api_notification_count', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function count(NotificationRepository $notifRepo, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Utiliser un cache système avec le bon TTL
            $count    = $notifRepo->countUnreadByUser($user);
            $response = new JsonResponse(['count' => $count]);

            // Générer un ETag basé sur le compte et l'ID utilisateur
            $etag = md5($count . '_' . $user->getId());
            $response->setEtag($etag);

            // Cache HTTP optimisé avec stale-while-revalidate
            $response->setPublic();
            $response->setMaxAge(60); // Augmenter à 1 minute
            // Permet d'utiliser le cache périmé pendant le rechargement
            $response->headers->addCacheControlDirective('stale-while-revalidate', 60);
            // En cas d'erreur, utiliser le cache périmé pendant 5 minutes
            $response->headers->addCacheControlDirective('stale-if-error', 300);

            // Compression optimisée
            $response->headers->set('Vary', 'Accept-Encoding');

            // Vérifier si la réponse a changé
            if ($response->isNotModified($request)) {
                return $response;
            }

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du comptage des notifications : ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => $user?->getId(),
            ]);

            return new JsonResponse(
                ['error' => 'Une erreur est survenue'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/test', name: 'app_api_notification_test', methods: ['GET'])]
    public function testAuth(Request $request): JsonResponse
    {
        $user = $this->getUser();

        return new JsonResponse([
            'is_authenticated' => $user !== null,
            'user_id'          => $user !== null ? $user->getId() : null,
            'user_email'       => $user !== null ? $user->getEmail() : null,
            'request_headers'  => $request->headers->all(),
            'session_id'       => $request->getSession() !== null ? $request->getSession()->getId() : null,
        ]);
    }
}
