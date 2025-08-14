<?php

declare(strict_types = 1);

namespace App\Tests\Unit;

use App\Service\MetricsCollector;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MetricsCollectorTest extends TestCase
{
    private $metricsCollector;
    private $registry;
    private $entityManager;
    private $logger;

    protected function setUp(): void
    {
        $this->registry         = $this->createMock(CollectorRegistry::class);
        $this->entityManager    = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->logger           = $this->createMock(LoggerInterface::class);
        $this->metricsCollector = new MetricsCollector($this->registry, $this->entityManager, $this->logger);
    }

    public function testRecordHttpRequest(): void
    {
        $request  = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET'], '/test');
        $response = new Response('', 200);
        $duration = 0.5;

        $this->metricsCollector->recordHttpRequest($request, $response, $duration);
        $this->assertTrue(true); // Si nous arrivons ici sans erreur, le test est réussi
    }

    public function testRecordDatabaseQuery(): void
    {
        $type     = 'SELECT';
        $table    = 'users';
        $duration = 0.1;

        $this->metricsCollector->recordDatabaseQuery($type, $table, $duration);
        $this->assertTrue(true);
    }

    public function testRecordCacheHit(): void
    {
        $type = 'redis';
        $this->metricsCollector->recordCacheHit($type);
        $this->assertTrue(true);
    }

    public function testRecordCacheMiss(): void
    {
        $type = 'redis';
        $this->metricsCollector->recordCacheMiss($type);
        $this->assertTrue(true);
    }

    public function testUpdateSystemMetrics(): void
    {
        $this->metricsCollector->updateSystemMetrics();
        $this->assertTrue(true);
    }

    public function testUpdateBusinessMetrics(): void
    {
        $this->metricsCollector->updateBusinessMetrics(
            100, // activeUsers
            1000, // postsTotal
            5000, // commentsTotal
            10000, // likesTotal
            2000 // sharesTotal
        );
        $this->assertTrue(true);
    }

    public function testGetRegistry(): void
    {
        $registry = $this->metricsCollector->getRegistry();
        $this->assertInstanceOf(CollectorRegistry::class, $registry);
    }
}
