<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private EmailService $emailService,
        private LoggerInterface $logger
    ) {
    }

    public function createUser(string $email, string $plainPassword): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function verifyUser(User $user): void
    {
        $user->setIsVerified(true);
        $this->entityManager->flush();
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $this->entityManager->flush();
    }

    public function sendVerificationEmail(User $user): void
    {
        $this->emailService->sendVerificationEmail($user);
    }

    public function sendWelcomeEmail(User $user): void
    {
        $email = $user->getEmail();
        if ($email !== null) {
            $this->emailService->sendWelcomeEmail($user);
        }
    }
}
