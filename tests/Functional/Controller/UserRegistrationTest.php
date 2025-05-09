<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\DomCrawler\Crawler;

class UserRegistrationTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        if (!$this->entityManager) {
            return;
        }

        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');

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

        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        $this->entityManager->clear();
    }

    private function submitRegistrationForm(Crawler $crawler, array $formData): Crawler
    {
        $form = $crawler->filter('form[name="registration_form"]')->form(array_merge([
            'registration_form[email]' => 'test@example.com',
            'registration_form[plainPassword]' => 'MotDePasse123!',
            'registration_form[firstName]' => 'John',
            'registration_form[lastName]' => 'Doe',
            'registration_form[agreeTerms]' => true,
            'registration_form[jobTitle]' => 'Ingénieur F1',
            'registration_form[skills]' => 'Mécanique, Aérodynamique',
        ], $formData));

        return $this->client->submit($form);
    }

    public function testCompleteRegistrationProcess(): void
    {
        // 1. Visite de la page d'inscription
        $crawler = $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        // 2. Sélection du type d'utilisateur
        $form = $crawler->filter('form[name="user_type_form"]')->form([
            'user_type_form[userType]' => 'ROLE_POSTULANT'
        ]);
        $this->client->submit($form);

        // 3. Suivre la redirection vers le formulaire d'inscription
        $this->assertResponseRedirects('/register/details');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4. Soumission du formulaire d'inscription
        $crawler = $this->submitRegistrationForm($crawler, [
            'registration_form[email]' => 'nouveau@example.com'
        ]);

        // 5. Vérifier la redirection
        $this->assertResponseRedirects('/email-verification-sent');

        // 6. Vérification en base de données
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneByEmail('nouveau@example.com');

        $this->assertNotNull($user);
        $this->assertFalse($user->isVerified());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertContains('ROLE_POSTULANT', $user->getRoles());
    }

    public function testRegistrationWithInvalidData(): void
    {
        // 1. Visite de la page d'inscription
        $crawler = $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        // 2. Sélection du type d'utilisateur
        $form = $crawler->filter('form[name="user_type_form"]')->form([
            'user_type_form[userType]' => 'ROLE_POSTULANT'
        ]);
        $this->client->submit($form);

        // 3. Suivre la redirection vers le formulaire d'inscription
        $this->assertResponseRedirects('/register/details');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4. Soumission du formulaire avec données invalides
        $crawler = $this->submitRegistrationForm($crawler, [
            'registration_form[email]' => 'invalid-email',
            'registration_form[plainPassword]' => 'short',
            'registration_form[firstName]' => '',
            'registration_form[lastName]' => '',
            'registration_form[agreeTerms]' => false,
            'registration_form[jobTitle]' => '',
            'registration_form[skills]' => '',
        ]);

        // 5. Vérification des erreurs
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $errors = $crawler->filter('.invalid-feedback');
        $this->assertGreaterThan(0, $errors->count(), 'Aucun message d\'erreur trouvé');

        // Récupérer tous les messages d'erreur
        $errorMessages = $errors->each(function ($node) {
            return trim($node->text());
        });

        // Vérifier la présence de chaque message d'erreur
        $expectedErrors = [
            'Le prénom est obligatoire',
            'Le nom est obligatoire',
            'L\'email n\'est pas valide',
            'Veuillez entrer le poste recherché',
            'Veuillez entrer vos compétences',
            'Le mot de passe doit contenir au moins 8 caractères',
            'Vous devez accepter les conditions d\'utilisation'
        ];

        foreach ($expectedErrors as $expectedError) {
            $this->assertTrue(in_array($expectedError, $errorMessages), "Le message d'erreur '$expectedError' n'a pas été trouvé");
        }
    }

    public function testRegistrationWithInvalidEmails(): void
    {
        // 1. Visite de la page d'inscription
        $crawler = $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        // 2. Sélection du type d'utilisateur
        $form = $crawler->filter('form[name="user_type_form"]')->form([
            'user_type_form[userType]' => 'ROLE_POSTULANT'
        ]);
        $this->client->submit($form);

        // 3. Suivre la redirection vers le formulaire d'inscription
        $this->assertResponseRedirects('/register/details');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $invalidEmails = [
            'plainaddress',
            '@missinguser.com',
            'user@',
            '.user@example.com',
            'user.@example.com',
            'user..name@example.com',
            'user@example..com',
            'user@.example.com',
            'user@example.',
            'user name@example.com',
            'user@example.com.',
            'user@-example.com',
            'user@example-.com',
            str_repeat('a', 255) . '@example.com',
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $crawler = $this->submitRegistrationForm($crawler, [
                'registration_form[email]' => $invalidEmail
            ]);

            $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
            $this->assertSelectorExists('.invalid-feedback', 'L\'email n\'est pas valide');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Nettoyage de la base de données après les tests
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'nouveau@example.com']);

        if ($user) {
            // Supprimer d'abord les relations d'amitié
            $this->entityManager->createQuery('DELETE FROM App\Entity\Friendship f WHERE f.requester = :user OR f.addressee = :user')
                ->setParameter('user', $user)
                ->execute();

            // Puis supprimer l'utilisateur
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
