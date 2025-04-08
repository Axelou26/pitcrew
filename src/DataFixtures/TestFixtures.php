<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création d'un utilisateur standard
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);
        $user->setIsVerified(true);
        $manager->persist($user);
        $this->addReference('user-standard', $user);

        // Création d'un administrateur
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);
        $admin->setIsVerified(true);
        $manager->persist($admin);
        $this->addReference('user-admin', $admin);

        // Création d'un utilisateur non vérifié
        $unverified = new User();
        $unverified->setEmail('unverified@example.com');
        $unverified->setFirstName('Jane');
        $unverified->setLastName('Smith');
        $unverified->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($unverified, 'password123');
        $unverified->setPassword($hashedPassword);
        $unverified->setIsVerified(false);
        $manager->persist($unverified);
        $this->addReference('user-unverified', $unverified);

        $manager->flush();
    }
}
