<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private $passwordHasher;
    private $entityManager;
    private $mailer;

    protected function setUp(): void
    {
        // Création des mocks
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);

        // Initialisation du service
        $this->userService = new UserService(
            $this->passwordHasher,
            $this->entityManager,
            $this->mailer
        );
    }

    public function testCreateUser(): void
    {
        // Données de test
        $email = 'test@example.com';
        $plainPassword = 'password123';

        // Configuration des mocks
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Exécution du test
        $user = $this->userService->createUser($email, $plainPassword);

        // Assertions
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals('hashed_password', $user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertFalse($user->isVerified());
    }

    public function testVerifyUser(): void
    {
        // Création d'un utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setIsVerified(false);

        // Configuration des mocks
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Exécution du test
        $this->userService->verifyUser($user);

        // Assertions
        $this->assertTrue($user->isVerified());
    }

    public function testChangePassword(): void
    {
        // Création d'un utilisateur de test
        $user = new User();
        $newPassword = 'newPassword123';

        // Configuration des mocks
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $newPassword)
            ->willReturn('new_hashed_password');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Exécution du test
        $this->userService->changePassword($user, $newPassword);

        // Assertions
        $this->assertEquals('new_hashed_password', $user->getPassword());
    }

    public function testSendVerificationEmail(): void
    {
        // Création d'un utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');

        // Configuration des mocks
        $this->mailer
            ->expects($this->once())
            ->method('send');

        // Exécution du test
        $this->userService->sendVerificationEmail($user);
    }
} 