<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestUserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Créer l'utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'password123')
        );
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER', 'ROLE_POSTULANT']);
        $user->setIsVerified(true);

        $manager->persist($user);

        $manager->flush();
    }
}
