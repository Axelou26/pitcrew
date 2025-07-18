<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\HashtagRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private string $appEnv;

    public function __construct(EntityManagerInterface $entityManager, string $kernelEnvironment)
    {
        $this->entityManager = $entityManager;
        $this->appEnv = $kernelEnvironment;
    }

    #[Route('/posts', name: 'posts', methods: ['GET'])]
    public function getPosts(PostRepository $postRepository): JsonResponse
    {
        $posts = $postRepository->findAll();
        return $this->json($posts);
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        try {
            // Vérification de la connexion à la base de données
            $this->entityManager->getConnection()->connect();

            return new JsonResponse([
                'status' => 'healthy',
                'timestamp' => (new DateTime())->format('c'),
                'database' => 'connected',
                'php_version' => PHP_VERSION,
                'symfony_environment' => $this->appEnv
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'unhealthy',
                'error' => 'Database connection failed',
                'message' => $e->getMessage()
            ], 503);
        }
    }

    #[Route('/hashtag-suggestions', name: 'api_hashtag_suggestions', methods: ['GET'])]
    public function hashtagSuggestions(Request $request, HashtagRepository $hashtagRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 1) {
            return $this->json(['success' => true, 'results' => []]);
        }

        $hashtags = $hashtagRepository->findSuggestions($query, 5);

        // Transformer les objets Hashtag en tableau de noms
        $suggestions = array_map(function ($hashtag) {
            return [
                'name' => $hashtag->getName(),
                'usageCount' => $hashtag->getUsageCount()
            ];
        }, $hashtags);

        return $this->json([
            'success' => true,
            'results' => $suggestions
        ]);
    }

    #[Route('/mention-suggestions', name: 'api_mention_suggestions', methods: ['GET'])]
    public function mentionSuggestions(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        // Exiger plus de caractères pour réduire la charge
        if (strlen($query) < 3) {
            return $this->json(['success' => true, 'results' => []], 200, [
                'Cache-Control' => 'public, max-age=60'
            ]);
        }

        // Limiter le nombre de résultats et optimiser la requête
        $users = $userRepository->findSuggestionsOptimized($query, 5);

        // Transformer les objets User en tableau avec les informations minimales nécessaires
        $suggestions = array_map(function ($user) {
            return [
                'firstName' => $user['firstName'],
                'lastName' => $user['lastName'],
                'profilePicture' => $user['profilePicture']
            ];
        }, $users);

        // Configurer les en-têtes de cache avec un TTL plus court
        $response = $this->json([
            'success' => true,
            'results' => $suggestions
        ]);

        $response->setPublic();
        $response->setMaxAge(60); // Cache pendant 1 minute au lieu de 5
        $response->setSharedMaxAge(60);
        // Ajouter l'en-tête pour gzip/deflate
        $response->headers->set('Vary', 'Accept-Encoding');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'public, max-age=60');

        return $response;
    }
}
