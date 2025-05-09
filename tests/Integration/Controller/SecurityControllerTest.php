<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
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
}
