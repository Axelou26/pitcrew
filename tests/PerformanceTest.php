<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PerformanceTest extends WebTestCase
{
    public function testHomepageLoadTime()
    {
        $client = static::createClient();
        $startTime = microtime(true);
        
        $client->request('GET', '/');
        $endTime = microtime(true);
        
        $this->assertResponseIsSuccessful();
        $this->assertLessThan(1.0, $endTime - $startTime, 'La page d\'accueil devrait se charger en moins d\'une seconde');
    }

    public function testDatabaseQueryPerformance()
    {
        $client = static::createClient();
        $startTime = microtime(true);
        
        $client->request('GET', '/api/posts');
        $endTime = microtime(true);
        
        $this->assertResponseIsSuccessful();
        $this->assertLessThan(0.5, $endTime - $startTime, 'La requête API devrait s\'exécuter en moins de 500ms');
    }

    public function testCachePerformance()
    {
        $client = static::createClient();
        
        // Premier appel (sans cache)
        $startTime = microtime(true);
        $client->request('GET', '/');
        $firstLoadTime = microtime(true) - $startTime;
        
        // Deuxième appel (avec cache)
        $startTime = microtime(true);
        $client->request('GET', '/');
        $secondLoadTime = microtime(true) - $startTime;
        
        $this->assertLessThan($firstLoadTime, $secondLoadTime, 'Le temps de chargement devrait être plus rapide avec le cache');
    }
} 