<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityTest extends WebTestCase
{
    private $client;
    private $userRepository;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    /**
     * Test de la force des mots de passe
     */
    public function testPasswordStrength(): void
    {
        $user = new User();
        $weakPassword = 'password123';
        $strongPassword = 'Str0ngP@ssw0rd!2024';

        // Test avec un mot de passe faible
        $user->setPassword($this->passwordHasher->hashPassword($user, $weakPassword));
        $this->assertFalse(
            preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $weakPassword),
            'Le mot de passe faible ne devrait pas être accepté'
        );

        // Test avec un mot de passe fort
        $user->setPassword($this->passwordHasher->hashPassword($user, $strongPassword));
        $this->assertTrue(
            preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $strongPassword),
            'Le mot de passe fort devrait être accepté'
        );
    }

    /**
     * Test de protection contre les attaques XSS
     */
    public function testXssProtection(): void
    {
        $xssPayload = '<script>alert("XSS")</script>';

        $this->client->request('GET', '/', ['search' => $xssPayload]);
        $response = $this->client->getResponse();

        $this->assertStringNotContainsString($xssPayload, $response->getContent());
        $this->assertStringContainsString('&lt;script&gt;', $response->getContent());
    }

    /**
     * Test de protection contre les attaques CSRF
     */
    public function testCsrfProtection(): void
    {
        // Test sans token CSRF
        $this->client->request('POST', '/post/create', [
            'content' => 'Test post'
        ]);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        // Test avec token CSRF valide
        $this->client->request('GET', '/post/create');
        $crawler = $this->client->getCrawler();
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/post/create', [
            'content' => 'Test post',
            '_token' => $token
        ]);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test de protection contre les attaques par injection SQL
     */
    public function testSqlInjectionProtection(): void
    {
        $sqlInjectionPayload = "' OR '1'='1";

        $this->client->request('GET', '/search', ['q' => $sqlInjectionPayload]);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringNotContainsString('SQL syntax', $response->getContent());
    }

    /**
     * Test de la gestion des sessions
     */
    public function testSessionManagement(): void
    {
        $this->client->request('GET', '/login');
        $session = $this->client->getRequest()->getSession();

        // Vérifier que la session est sécurisée
        $this->assertTrue($session->isStarted());
        $this->assertTrue(ini_get('session.cookie_httponly'));
        $this->assertTrue(ini_get('session.cookie_secure'));
        $this->assertEquals('Lax', ini_get('session.cookie_samesite'));
    }

    /**
     * Test des en-têtes de sécurité
     */
    public function testSecurityHeaders(): void
    {
        $this->client->request('GET', '/');
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
        $this->assertTrue($response->headers->has('X-XSS-Protection'));
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $this->assertTrue($response->headers->has('Strict-Transport-Security'));
    }
}
