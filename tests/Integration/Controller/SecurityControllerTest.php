<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Doctrine\ORM\EntityManagerInterface;

class SecurityControllerTest extends WebTestCase
{
    private $client;
    private AbstractDatabaseTool $databaseTool;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Désactiver les clés étrangères
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');

        // Nettoyer les tables
        $tables = [
            'job_application',
            'job_offer',
            'post_hashtag',
            'post',
            'hashtag',
            'user',
            'friendship',
            'notification',
            'favorite',
            'post_like',
            'post_comment',
            'recruiter_subscription',
            'interview',
            'education',
            'work_experience',
            'support_ticket'
        ];

        foreach ($tables as $table) {
            $this->entityManager->getConnection()->executeQuery("TRUNCATE TABLE {$table}");
        }

        // Réactiver les clés étrangères
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        // Charger les fixtures
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $this->databaseTool->loadFixtures([
            'App\DataFixtures\TestUserFixtures'
        ]);

        // S'assurer qu'aucun utilisateur n'est connecté
        $this->client->request('GET', '/logout');
    }

    public function testLogin(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Se connecter');

        $this->client->submitForm('Se connecter', [
            'email' => 'test@example.com',
            'password' => 'password123',
            '_csrf_token' => $crawler->filter('input[name="_csrf_token"]')->attr('value'),
        ]);

        $this->assertResponseRedirects('/dashboard');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->client->submitForm('Se connecter', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
            '_csrf_token' => $crawler->filter('input[name="_csrf_token"]')->attr('value'),
        ]);

        $this->assertResponseRedirects('/login');
        $crawler = $this->client->followRedirect();
        $this->assertSelectorExists('.alert.alert-danger');
    }

    public function testLogout(): void
    {
        $this->client->request('GET', '/logout');
        $this->assertResponseRedirects('/');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Nettoyer la base de données
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->entityManager->getConnection()->getSchemaManager()->listTableNames() as $table) {
            $this->entityManager->getConnection()->executeQuery("TRUNCATE TABLE {$table}");
        }
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
