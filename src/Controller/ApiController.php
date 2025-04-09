<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;

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
}
