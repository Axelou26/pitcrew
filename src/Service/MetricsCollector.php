<?php

namespace App\Service;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MetricsCollector
{
    private CollectorRegistry $registry;
    private LoggerInterface $logger;
    private array $metrics = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->registry = new CollectorRegistry(new InMemory());
        $this->initializeMetrics();
    }

    private function initializeMetrics(): void
    {
        // HTTP metrics
        $this->metrics['http_requests_total'] = $this->registry->getOrRegisterCounter(
            'app',
            'http_requests_total',
            'Total number of HTTP requests',
            ['method', 'route', 'status']
        );

        $this->metrics['http_request_duration_seconds'] = $this->registry->getOrRegisterHistogram(
            'app',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'route'],
            [0.1, 0.5, 1, 2, 5]
        );

        // Database metrics
        $this->metrics['database_queries_total'] = $this->registry->getOrRegisterCounter(
            'app',
            'database_queries_total',
            'Total number of database queries',
            ['type', 'table']
        );

        $this->metrics['database_query_duration_seconds'] = $this->registry->getOrRegisterHistogram(
            'app',
            'database_query_duration_seconds',
            'Database query duration in seconds',
            ['type', 'table'],
            [0.01, 0.05, 0.1, 0.5, 1]
        );

        // Cache metrics
        $this->metrics['cache_hits_total'] = $this->registry->getOrRegisterCounter(
            'app',
            'cache_hits_total',
            'Total number of cache hits',
            ['type']
        );

        $this->metrics['cache_misses_total'] = $this->registry->getOrRegisterCounter(
            'app',
            'cache_misses_total',
            'Total number of cache misses',
            ['type']
        );

        // System metrics
        $this->metrics['memory_usage_bytes'] = $this->registry->getOrRegisterGauge(
            'app',
            'memory_usage_bytes',
            'Memory usage in bytes'
        );

        $this->metrics['cpu_usage'] = $this->registry->getOrRegisterGauge(
            'app',
            'cpu_usage',
            'CPU usage'
        );

        // Business metrics
        $this->metrics['active_users'] = $this->registry->getOrRegisterGauge(
            'app',
            'active_users',
            'Number of active users'
        );

        $this->metrics['posts_total'] = $this->registry->getOrRegisterGauge(
            'app',
            'posts_total',
            'Total number of posts'
        );

        $this->metrics['comments_total'] = $this->registry->getOrRegisterGauge(
            'app',
            'comments_total',
            'Total number of comments'
        );

        $this->metrics['likes_total'] = $this->registry->getOrRegisterGauge(
            'app',
            'likes_total',
            'Total number of likes'
        );

        $this->metrics['shares_total'] = $this->registry->getOrRegisterGauge(
            'app',
            'shares_total',
            'Total number of shares'
        );
    }

    public function recordHttpRequest(Request $request, Response $response, float $duration): void
    {
        $method = $request->getMethod();
        $route = $request->getPathInfo();
        $status = $response->getStatusCode();

        $this->metrics['http_requests_total']->inc(['method' => $method, 'route' => $route, 'status' => $status]);
        $this->metrics['http_request_duration_seconds']->observe($duration, ['method' => $method, 'route' => $route]);
    }

    public function recordDatabaseQuery(string $type, string $table, float $duration): void
    {
        $this->metrics['database_queries_total']->inc(['type' => $type, 'table' => $table]);
        $this->metrics['database_query_duration_seconds']->observe($duration, ['type' => $type, 'table' => $table]);
    }

    public function recordCacheHit(string $type): void
    {
        $this->metrics['cache_hits_total']->inc(['type' => $type]);
    }

    public function recordCacheMiss(string $type): void
    {
        $this->metrics['cache_misses_total']->inc(['type' => $type]);
    }

    public function updateSystemMetrics(): void
    {
        // Mémoire
        $memoryUsage = memory_get_usage(true);
        $this->metrics['memory_usage_bytes']->set($memoryUsage);

        // CPU (si disponible)
        $cpuLoad = 0;
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cpuLoad = $load[0];
        }
        $this->metrics['cpu_usage']->set($cpuLoad);
    }

    public function updateBusinessMetrics(
        int $activeUsers,
        int $postsTotal,
        int $commentsTotal,
        int $likesTotal,
        int $sharesTotal
    ): void {
        $this->metrics['active_users']->set($activeUsers);
        $this->metrics['posts_total']->set($postsTotal);
        $this->metrics['comments_total']->set($commentsTotal);
        $this->metrics['likes_total']->set($likesTotal);
        $this->metrics['shares_total']->set($sharesTotal);
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }
}
