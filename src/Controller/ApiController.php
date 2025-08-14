<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\HashtagRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private string $appEnv;

    public function __construct(EntityManagerInterface $entityManager, string $kernelEnvironment)
    {
        $this->entityManager = $entityManager;
        $this->appEnv        = $kernelEnvironment;
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
                'status'              => 'healthy',
                'timestamp'           => (new DateTimeImmutable())->format('c'),
                'database'            => 'connected',
                'php_version'         => \PHP_VERSION,
                'symfony_environment' => $this->appEnv,
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'status'  => 'unhealthy',
                'error'   => 'Database connection failed',
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    #[Route('/hashtag-suggestions', name: 'api_hashtag_suggestions', methods: ['GET'])]
    public function hashtagSuggestions(Request $request, HashtagRepository $hashtagRepository): JsonResponse
    {
        try {
            $query = $request->query->get('q', '');

            if ($query === '') {
                // Si la requête est vide, retourner les hashtags tendances
                $hashtags = $hashtagRepository->findTrending(10);
                $suggestions = array_map(function ($hashtag) {
                    return [
                        'name'       => $hashtag->getName(),
                        'usageCount' => $hashtag->getUsageCount(),
                    ];
                }, $hashtags);

                return $this->json([
                    'success' => true,
                    'results' => $suggestions,
                ]);
            }

            $hashtags = $hashtagRepository->findSuggestions($query, 5);

            // Transformer les objets Hashtag en tableau de noms
            $suggestions = array_map(function ($hashtag) {
                return [
                    'name'       => $hashtag->getName(),
                    'usageCount' => $hashtag->getUsageCount(),
                ];
            }, $hashtags);

            $response = $this->json([
                'success' => true,
                'results' => $suggestions,
            ]);

            // Configurer le cache pour améliorer les performances
            $response->setSharedMaxAge(60); // Cache public pendant 1 minute
            $response->setMaxAge(60);
            $response->headers->set('Cache-Control', 'public, max-age=60');
            $response->headers->set('Vary', 'Accept-Encoding');

            return $response;
        } catch (Exception $e) {
            // Journaliser l'erreur
            $this->entityManager->getConnection()->getConfiguration()
                ->getSQLLogger()?->startQuery('Erreur dans hashtag-suggestions: ' . $e->getMessage(), []);

            // Retourner une réponse d'erreur formatée
            return $this->json([
                'success' => false,
                'error'   => 'Une erreur est survenue lors de la récupération des suggestions de hashtags',
                'message' => $this->appEnv === 'dev' ? $e->getMessage() : 'Service temporairement indisponible',
            ], 500);
        }
    }

    #[Route('/mention-suggestions', name: 'api_mention_suggestions', methods: ['GET'])]
    public function mentionSuggestions(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        // Si la requête est vide, renvoyer des utilisateurs populaires
        if ($query === '') {
            $users = $userRepository->findPopularUsers(10);
            $suggestions = array_map(function ($user) {
                return [
                    'firstName'      => $user->getFirstName(),
                    'lastName'       => $user->getLastName(),
                    'profilePicture' => $user->getProfilePicture(),
                ];
            }, $users);

            return $this->json([
                'success' => true,
                'results' => $suggestions,
            ], 200, [
                'Cache-Control' => 'public, max-age=60',
            ]);
        }

        // Exiger plus de caractères pour réduire la charge
        if (strlen($query) < 3) {
            return $this->json(['success' => true, 'results' => []], 200, [
                'Cache-Control' => 'public, max-age=60',
            ]);
        }

        // Limiter le nombre de résultats et optimiser la requête
        $users = $userRepository->findSuggestionsOptimized($query, 5);

        // Transformer les objets User en tableau avec les informations minimales nécessaires
        $suggestions = array_map(function ($user) {
            return [
                'firstName'      => $user['firstName'],
                'lastName'       => $user['lastName'],
                'profilePicture' => $user['profilePicture'],
            ];
        }, $users);

        // Configurer les en-têtes de cache avec un TTL plus court
        $response = $this->json([
            'success' => true,
            'results' => $suggestions,
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
