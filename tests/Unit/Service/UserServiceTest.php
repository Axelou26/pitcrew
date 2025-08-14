<?php

declare(strict_types = 1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\EmailService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private EmailService $emailService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager  = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->emailService   = $this->createMock(EmailService::class);
        $this->logger         = $this->createMock(LoggerInterface::class);

        $this->userService = new UserService(
            $this->passwordHasher,
            $this->entityManager,
            $this->emailService,
            $this->logger
        );
    }

    public function testCreateUser(): void
    {
        // Données de test
        $email         = 'test@example.com';
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
        $this->assertSame($email, $user->getEmail());
        $this->assertSame('hashed_password', $user->getPassword());
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
        $user        = new User();
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
        $this->assertSame('new_hashed_password', $user->getPassword());
    }

    public function testSendVerificationEmail(): void
    {
        // Création d'un utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');

        // Configuration des mocks
        $this->emailService
            ->expects($this->once())
            ->method('sendVerificationEmail')
            ->with($user);

        // Exécution du test
        $this->userService->sendVerificationEmail($user);
    }
}
