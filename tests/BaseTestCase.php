<?php

declare(strict_types = 1);

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseTestCase extends KernelTestCase
{
    protected ContainerInterface $container;
    protected EntityManagerInterface $entityManager;
    protected ValidatorInterface $validator;
    protected UserPasswordEncoderInterface $passwordEncoder;
    protected MailerInterface $mailer;
    protected RouterInterface $router;
    protected RequestStack $requestStack;
    protected KernelInterface $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernel    = self::bootKernel();
        $this->container = static::getContainer();

        // Services communs
        $this->entityManager   = $this->container->get(EntityManagerInterface::class);
        $this->validator       = $this->container->get(ValidatorInterface::class);
        $this->passwordEncoder = $this->container->get(UserPasswordEncoderInterface::class);
        $this->mailer          = $this->container->get(MailerInterface::class);
        $this->router          = $this->container->get(RouterInterface::class);
        $this->requestStack    = $this->container->get(RequestStack::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Nettoyage de l'EntityManager
        if ($this->entityManager->isOpen()) {
            $this->entityManager->close();
        }
    }

    /**
     * Crée un utilisateur de test.
     */
    protected function createTestUser(
        string $email = 'test@example.com',
        string $password = 'password123',
        array $roles = ['ROLE_USER']
    ): \App\Entity\User {
        $user = new \App\Entity\User();
        $user->setEmail($email);
        $user->setRoles($roles);

        $encodedPassword = $this->passwordEncoder->encodePassword($user, $password);
        $user->setPassword($encodedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Crée un recruteur de test.
     */
    protected function createTestRecruiter(
        string $email = 'recruiter@example.com',
        string $companyName = 'Test Company'
    ): \App\Entity\Recruiter {
        $recruiter = new \App\Entity\Recruiter();
        $recruiter->setEmail($email);
        $recruiter->setCompanyName($companyName);
        $recruiter->setRoles(['ROLE_RECRUITER']);

        $encodedPassword = $this->passwordEncoder->encodePassword($recruiter, 'password123');
        $recruiter->setPassword($encodedPassword);

        $this->entityManager->persist($recruiter);
        $this->entityManager->flush();

        return $recruiter;
    }

    /**
     * Crée un post de test.
     */
    protected function createTestPost(
        \App\Entity\User $author,
        string $content = 'Test post content'
    ): \App\Entity\Post {
        $post = new \App\Entity\Post();
        $post->setContent($content);
        $post->setAuthor($author);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    /**
     * Crée une offre d'emploi de test.
     */
    protected function createTestJobOffer(
        \App\Entity\Recruiter $recruiter,
        string $title = 'Test Job Offer'
    ): \App\Entity\JobOffer {
        $jobOffer = new \App\Entity\JobOffer();
        $jobOffer->setTitle($title);
        $jobOffer->setDescription('Test job offer description');
        $jobOffer->setRecruiter($recruiter);
        $jobOffer->setContractType('CDI');
        $jobOffer->setLocation('Paris');
        $jobOffer->setSalary(50000);

        $this->entityManager->persist($jobOffer);
        $this->entityManager->flush();

        return $jobOffer;
    }

    /**
     * Crée une candidature de test.
     */
    protected function createTestApplication(
        \App\Entity\User $applicant,
        \App\Entity\JobOffer $jobOffer
    ): \App\Entity\Application {
        $application = new \App\Entity\Application();
        $application->setApplicant($applicant);
        $application->setJobOffer($jobOffer);
        $application->setCoverLetter('Test cover letter');
        $application->setResume('test-resume.pdf');

        $this->entityManager->persist($application);
        $this->entityManager->flush();

        return $application;
    }

    /**
     * Crée une notification de test.
     */
    protected function createTestNotification(
        \App\Entity\User $user,
        string $title = 'Test Notification',
        string $message = 'Test notification message'
    ): \App\Entity\Notification {
        $notification = new \App\Entity\Notification();
        $notification->setUser($user);
        $notification->setTitle($title);
        $notification->setMessage($message);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée un hashtag de test.
     */
    protected function createTestHashtag(string $name = 'test'): \App\Entity\Hashtag
    {
        $hashtag = new \App\Entity\Hashtag();
        $hashtag->setName($name);

        $this->entityManager->persist($hashtag);
        $this->entityManager->flush();

        return $hashtag;
    }

    /**
     * Crée une conversation de test.
     */
    protected function createTestConversation(
        \App\Entity\User $user1,
        \App\Entity\User $user2
    ): \App\Entity\Conversation {
        $conversation = new \App\Entity\Conversation();
        $conversation->addParticipant($user1);
        $conversation->addParticipant($user2);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $conversation;
    }

    /**
     * Crée un message de test.
     */
    protected function createTestMessage(
        \App\Entity\Conversation $conversation,
        \App\Entity\User $sender,
        string $content = 'Test message'
    ): \App\Entity\Message {
        $message = new \App\Entity\Message();
        $message->setConversation($conversation);
        $message->setSender($sender);
        $message->setContent($content);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    /**
     * Crée une amitié de test.
     */
    protected function createTestFriendship(
        \App\Entity\User $sender,
        \App\Entity\User $receiver,
        string $status = 'pending'
    ): \App\Entity\Friendship {
        $friendship = new \App\Entity\Friendship();
        $friendship->setSender($sender);
        $friendship->setReceiver($receiver);
        $friendship->setStatus($status);

        $this->entityManager->persist($friendship);
        $this->entityManager->flush();

        return $friendship;
    }

    /**
     * Crée un entretien de test.
     */
    protected function createTestInterview(
        \App\Entity\User $candidate,
        \App\Entity\JobOffer $jobOffer,
        ?\DateTimeImmutable $date = null
    ): \App\Entity\Interview {
        $interview = new \App\Entity\Interview();
        $interview->setCandidate($candidate);
        $interview->setJobOffer($jobOffer);
        $interview->setDate($date ?? new \DateTimeImmutable('+1 day'));
        $interview->setType('video');
        $interview->setStatus('scheduled');

        $this->entityManager->persist($interview);
        $this->entityManager->flush();

        return $interview;
    }

    /**
     * Nettoie la base de données de test.
     */
    protected function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform   = $connection->getDatabasePlatform();

        // Désactiver les contraintes de clés étrangères
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        // Tables à nettoyer dans l'ordre (pour éviter les contraintes)
        $tables = [
            'message',
            'conversation',
            'post_like',
            'post_comment',
            'post_hashtag',
            'post_mention',
            'post',
            'notification',
            'favorite',
            'friendship',
            'interview',
            'application',
            'job_offer',
            'recruiter_subscription',
            'subscription',
            'hashtag',
            'user',
        ];

        foreach ($tables as $table) {
            try {
                $connection->executeStatement($platform->getTruncateTableSQL($table, true));
            } catch (\Exception $e) {
                // Ignorer les erreurs si la table n'existe pas
            }
        }

        // Réactiver les contraintes de clés étrangères
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Valide une entité.
     */
    protected function validateEntity(object $entity): array
    {
        return $this->validator->validate($entity);
    }

    /**
     * Vérifie si une entité est valide.
     */
    protected function assertEntityIsValid(object $entity): void
    {
        $violations = $this->validateEntity($entity);
        $this->assertCount(0, $violations, 'Entity should be valid');
    }

    /**
     * Vérifie si une entité a des violations spécifiques.
     */
    protected function assertEntityHasViolations(object $entity, array $expectedViolations): void
    {
        $violations       = $this->validateEntity($entity);
        $actualViolations = [];

        foreach ($violations as $violation) {
            $actualViolations[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
        }

        $this->assertSame($expectedViolations, $actualViolations);
    }

    /**
     * Génère un email unique pour les tests.
     */
    protected function generateUniqueEmail(): string
    {
        return 'test_' . uniqid() . '@example.com';
    }

    /**
     * Génère un nom unique pour les tests.
     */
    protected function generateUniqueName(): string
    {
        return 'Test_' . uniqid();
    }
}
