<?php

declare(strict_types = 1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RegistrationControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client        = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Nettoyer les données de test
        $userRepository = $this->entityManager->getRepository(User::class);
        $testUsers      = $userRepository->findBy([
            'email' => ['john.doe@example.com', 'jane.smith@company.com', 'existing@example.com', 'test@example.com', 'onboarding@example.com'],
        ]);

        foreach ($testUsers as $user) {
            $this->entityManager->remove($user);
        }
        $this->entityManager->flush();
    }

    public function testRegistrationFlow(): void
    {
        // 1. Visite de la page d'inscription
        $crawler = $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="user_type_form"]');

        // 2. Sélection du type d'utilisateur (Postulant)
        $form = $crawler->filter('form[name="user_type_form"]')->form([
            'user_type_form[userType]' => 'ROLE_POSTULANT',
        ]);
        $this->client->submit($form);

        // 3. Suivre la redirection vers le formulaire d'inscription
        $this->assertResponseRedirects('/register/details');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4. Soumission du formulaire d'inscription complet
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[firstName]'             => 'John',
            'registration_form[lastName]'              => 'Doe',
            'registration_form[email]'                 => 'john.doe@example.com',
            'registration_form[phone]'                 => '+33123456789',
            'registration_form[city]'                  => 'Paris',
            'registration_form[jobTitle]'              => 'Ingénieur sport automobile',
            'registration_form[location]'              => 'Île-de-France',
            'registration_form[technicalSkills]'       => 'Mécanique, Aérodynamique, Simulation',
            'registration_form[description]'           => 'Ingénieur passionné de sport automobile avec 5 ans d\'expérience.',
            'registration_form[experienceLevel]'       => 'confirme',
            'registration_form[availability]'          => 'immediate',
            'registration_form[plainPassword][first]'  => 'Password123!',
            'registration_form[plainPassword][second]' => 'Password123!',
            'registration_form[agreeTerms]'            => true,
            'registration_form[agreeNewsletter]'       => true,
        ]);

        $this->client->submit($form);

        // 5. Vérifier la redirection vers la page de vérification email
        $this->assertResponseRedirects('/register/email-verification-sent');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 6. Vérification en base de données
        $userRepository = $this->entityManager->getRepository(User::class);
        $user           = $userRepository->findOneByEmail('john.doe@example.com');

        $this->assertNotNull($user);
        $this->assertFalse($user->isVerified());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertContains('ROLE_POSTULANT', $user->getRoles());
        $this->assertSame('Ingénieur sport automobile', $user->getJobTitle());
        $this->assertSame('Paris', $user->getCity());
    }

    public function testRecruiterRegistrationFlow(): void
    {
        // 1. Visite de la page d'inscription
        $crawler = $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        // 2. Sélection du type d'utilisateur (Recruteur)
        $form = $crawler->filter('form[name="user_type_form"]')->form([
            'user_type_form[userType]' => 'ROLE_RECRUTEUR',
        ]);
        $this->client->submit($form);

        // 3. Suivre la redirection vers le formulaire d'inscription
        $this->assertResponseRedirects('/register/details');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4. Soumission du formulaire d'inscription pour recruteur
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[firstName]'             => 'Jane',
            'registration_form[lastName]'              => 'Smith',
            'registration_form[email]'                 => 'jane.smith@company.com',
            'registration_form[phone]'                 => '+33987654321',
            'registration_form[city]'                  => 'Lyon',
            'registration_form[companyName]'           => 'Tech Racing',
            'registration_form[jobTitle]'              => 'Directeur RH',
            'registration_form[sector]'                => 'formule_1',
            'registration_form[companySize]'           => '51-200',
            'registration_form[website]'               => 'https://www.techracing.com',
            'registration_form[companyDescription]'    => 'Entreprise leader dans le développement de solutions sport automobile.',
            'registration_form[plainPassword][first]'  => 'SecurePass456!',
            'registration_form[plainPassword][second]' => 'SecurePass456!',
            'registration_form[agreeTerms]'            => true,
            'registration_form[agreeNewsletter]'       => false,
        ]);

        $this->client->submit($form);

        // 5. Vérifier la redirection vers la sélection d'abonnement
        $this->assertResponseRedirects('/register/subscription');
    }

    public function testRegistrationWithInvalidData(): void
    {
        // 1. Visite de la page d'inscription
        $crawler = $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        // 2. Sélection du type d'utilisateur
        $form = $crawler->filter('form[name="user_type_form"]')->form([
            'user_type_form[userType]' => 'ROLE_POSTULANT',
        ]);
        $this->client->submit($form);

        // 3. Suivre la redirection
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4. Soumission avec des données invalides
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[firstName]'             => '', // Champ requis vide
            'registration_form[lastName]'              => 'Doe',
            'registration_form[email]'                 => 'invalid-email', // Email invalide
            'registration_form[plainPassword][first]'  => 'weak', // Mot de passe trop faible
            'registration_form[plainPassword][second]' => 'weak',
            'registration_form[agreeTerms]'            => false, // Conditions non acceptées
        ]);

        $this->client->submit($form);

        // 5. Vérifier que la page reste sur le formulaire avec des erreurs
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorExists('.invalid-feedback');
    }

    public function testDuplicateEmailRegistration(): void
    {
        // Créer un utilisateur existant
        $userRepository = $this->entityManager->getRepository(User::class);
        $existingUser   = new User();
        $existingUser->setEmail('existing@example.com');
        $existingUser->setFirstName('Existing');
        $existingUser->setLastName('User');
        $existingUser->setPassword('hashedpassword');
        $existingUser->setRoles(['ROLE_USER']);
        $this->entityManager->persist($existingUser);
        $this->entityManager->flush();

        // 1. Visite de la page d'inscription
        $crawler = $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        // 2. Sélection du type d'utilisateur
        $form = $crawler->filter('form[name="user_type_form"]')->form([
            'user_type_form[userType]' => 'ROLE_POSTULANT',
        ]);
        $this->client->submit($form);

        // 3. Suivre la redirection
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4. Tentative d'inscription avec un email existant
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[firstName]'             => 'New',
            'registration_form[lastName]'              => 'User',
            'registration_form[email]'                 => 'existing@example.com', // Email déjà utilisé
            'registration_form[plainPassword][first]'  => 'Password123!',
            'registration_form[plainPassword][second]' => 'Password123!',
            'registration_form[agreeTerms]'            => true,
        ]);

        $this->client->submit($form);

        // 5. Vérifier que l'erreur est affichée
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorTextContains('.alert', 'Cette adresse email est déjà utilisée');
    }

    public function testPasswordStrengthValidation(): void
    {
        // 1. Visite de la page d'inscription
        $crawler = $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        // 2. Sélection du type d'utilisateur
        $form = $crawler->filter('form[name="user_type_form"]')->form([
            'user_type_form[userType]' => 'ROLE_POSTULANT',
        ]);
        $this->client->submit($form);

        // 3. Suivre la redirection
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4. Test avec un mot de passe faible
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[firstName]'             => 'Test',
            'registration_form[lastName]'              => 'User',
            'registration_form[email]'                 => 'test@example.com',
            'registration_form[plainPassword][first]'  => 'weak',
            'registration_form[plainPassword][second]' => 'weak',
            'registration_form[agreeTerms]'            => true,
        ]);

        $this->client->submit($form);

        // 5. Vérifier que l'erreur de mot de passe est affichée
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorExists('.invalid-feedback');
    }

    public function testOnboardingPage(): void
    {
        // Créer un utilisateur connecté
        $userRepository = $this->entityManager->getRepository(User::class);
        $user           = new User();
        $user->setEmail('onboarding@example.com');
        $user->setFirstName('Onboarding');
        $user->setLastName('User');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_USER', 'ROLE_POSTULANT']);
        $user->setIsVerified(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Simuler une connexion
        $this->client->loginUser($user);

        // Visiter la page d'onboarding
        $crawler = $this->client->request('GET', '/onboarding');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.onboarding-container');
        $this->assertSelectorExists('.step-card');
    }
}
