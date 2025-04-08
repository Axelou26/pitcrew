<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PostFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        // Création d'un utilisateur
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $manager->persist($user);
        $manager->flush();

        // Création des posts
        for ($i = 1; $i <= 10; $i++) {
            $post = new Post();
            $post->setTitle('Post ' . $i);
            $post->setContent('Contenu du post ' . $i . '. Lorem ipsum dolor sit amet, consectetur adipiscing elit.');
            $post->setAuthor($user);
            $manager->persist($post);
        }

        $manager->flush();
    }
}
