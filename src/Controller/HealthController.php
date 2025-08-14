<?php

declare(strict_types=1);

namespace App\Controller;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        $this->entityManager     = $entityManager;
        $this->cache             = $cache;
        $this->kernelEnvironment = $kernelEnvironment;
    }

    #[Route('/health', name: 'app_health')]
    public function index(): Response
    {
        $status = 'healthy';
        $checks = [
            'database'    => true,
            'cache'       => true,
            'timestamp'   => (new DateTimeImmutable())->format('c'),
            'environment' => $this->kernelEnvironment,
        ];

        try {
            // Vérification de la base de données
            $this->entityManager->getConnection()->connect();
        } catch (Exception $e) {
            $status                   = 'unhealthy';
            $checks['database']       = false;
            $checks['database_error'] = $e->getMessage();
        }

        try {
            // Vérification du cache
            $this->cache->hasItem('health_check');
        } catch (Exception $e) {
            $status                = 'unhealthy';
            $checks['cache']       = false;
            $checks['cache_error'] = $e->getMessage();
        }

        $jsonContent = json_encode(['status' => $status, 'checks' => $checks]);
        if ($jsonContent === false) {
            $jsonContent = '{"status":"error","message":"Failed to encode JSON"}';
        }

        $response = new Response(
            $jsonContent,
            $status === 'healthy' ? 200 : 503,
            ['Content-Type' => 'application/json']
        );

        return $response;
    }
}
