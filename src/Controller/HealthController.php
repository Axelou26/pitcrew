<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

class HealthController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CacheItemPoolInterface $cache;
    private string $kernelEnvironment;

    public function __construct(
        EntityManagerInterface $entityManager,
        CacheItemPoolInterface $cache,
        string $kernelEnvironment
    ) {
        $this->entityManager = $entityManager;
        $this->cache = $cache;
        $this->kernelEnvironment = $kernelEnvironment;
    }

    #[Route('/health', name: 'app_health')]
    public function index(): Response
    {
        $status = 'healthy';
        $checks = [
            'database' => true,
            'cache' => true,
            'timestamp' => (new DateTime())->format('c'),
            'environment' => $this->kernelEnvironment
        ];

        try {
            // Vérification de la base de données
            $this->entityManager->getConnection()->connect();
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['database'] = false;
            $checks['database_error'] = $e->getMessage();
        }

        try {
            // Vérification du cache
            $this->cache->hasItem('health_check');
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['cache'] = false;
            $checks['cache_error'] = $e->getMessage();
        }

        $response = new Response(
            json_encode(['status' => $status, 'checks' => $checks]),
            $status === 'healthy' ? 200 : 503,
            ['Content-Type' => 'application/json']
        );

        return $response;
    }
}
