<?php

namespace App\Tests\Performance;

use App\Repository\UserRepository;
use App\Repository\PostRepository;
use App\Repository\HashtagRepository;
use App\Repository\JobOfferRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomepagePerformanceTest extends WebTestCase
{
    private $client;
    private $cache;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->cache = new ArrayAdapter();
    }

    /**
     * Test de performance de la page d'accueil
     */
    public function testHomepageLoadTime(): void
    {
        $startTime = microtime(true);

        $this->client->request('GET', '/');
        
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;

        $this->assertLessThan(1.0, $loadTime, 'Le temps de chargement de la page d\'accueil doit être inférieur à 1 seconde');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test de performance du cache
     */
    public function testCachePerformance(): void
    {
        $cacheKey = 'homepage_data_anonymous';
        
        // Premier appel (sans cache)
        $startTime = microtime(true);
        $this->client->request('GET', '/');
        $firstLoadTime = microtime(true) - $startTime;

        // Deuxième appel (avec cache)
        $startTime = microtime(true);
        $this->client->request('GET', '/');
        $secondLoadTime = microtime(true) - $startTime;

        $this->assertLessThan($firstLoadTime, $secondLoadTime, 'Le temps de chargement avec cache doit être inférieur au temps sans cache');
        $this->assertLessThan(0.5, $secondLoadTime, 'Le temps de chargement avec cache doit être inférieur à 0.5 seconde');
    }

    /**
     * Test de performance des requêtes de base de données
     */
    public function testDatabaseQueryPerformance(): void
    {
        $container = static::getContainer();
        $postRepository = $container->get(PostRepository::class);
        $userRepository = $container->get(UserRepository::class);
        $hashtagRepository = $container->get(HashtagRepository::class);
        $jobOfferRepository = $container->get(JobOfferRepository::class);

        // Test des requêtes de posts
        $startTime = microtime(true);
        $posts = $postRepository->findRecentPosts(10);
        $postQueryTime = microtime(true) - $startTime;
        $this->assertLessThan(0.1, $postQueryTime, 'La requête des posts doit être exécutée en moins de 0.1 seconde');

        // Test des requêtes d'utilisateurs
        $startTime = microtime(true);
        $users = $userRepository->findAll();
        $userQueryTime = microtime(true) - $startTime;
        $this->assertLessThan(0.2, $userQueryTime, 'La requête des utilisateurs doit être exécutée en moins de 0.2 seconde');

        // Test des requêtes de hashtags
        $startTime = microtime(true);
        $hashtags = $hashtagRepository->findTrendingHashtags(10);
        $hashtagQueryTime = microtime(true) - $startTime;
        $this->assertLessThan(0.1, $hashtagQueryTime, 'La requête des hashtags doit être exécutée en moins de 0.1 seconde');

        // Test des requêtes d'offres d'emploi
        $startTime = microtime(true);
        $jobOffers = $jobOfferRepository->findActiveJobOffers(5);
        $jobOfferQueryTime = microtime(true) - $startTime;
        $this->assertLessThan(0.1, $jobOfferQueryTime, 'La requête des offres d\'emploi doit être exécutée en moins de 0.1 seconde');
    }

    /**
     * Test de la consommation de mémoire
     */
    public function testMemoryUsage(): void
    {
        $startMemory = memory_get_usage();
        
        $this->client->request('GET', '/');
        
        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;

        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'La consommation de mémoire doit être inférieure à 10 Mo');
    }
} 