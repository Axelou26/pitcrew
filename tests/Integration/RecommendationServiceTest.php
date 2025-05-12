<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\Post;
use App\Entity\Hashtag;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class RecommendationServiceTest extends KernelTestCase
{
    private RecommendationService $recommendationService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->recommendationService = $kernel->getContainer()
            ->get(RecommendationService::class);

        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        if (!$this->entityManager) {
            return;
        }

        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'post_hashtag',
            'post',
            'hashtag',
            'user'
        ];

        foreach ($tables as $table) {
            $this->entityManager->getConnection()->executeQuery("TRUNCATE TABLE {$table}");
        }

        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        $this->entityManager->clear();
    }

    public function testGetRecommendedPosts(): void
    {
        // Nettoyer le cache avant le test
        $this->recommendationService->clearCache();

        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test_recommended@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $this->entityManager->persist($user);

        // Créer des posts avec des hashtags
        $hashtag = new Hashtag();
        $hashtag->setName('test');
        $this->entityManager->persist($hashtag);

        $post = new Post();
        $post->setContent('Test post with hashtag');
        $post->setTitle('Test Post');
        $post->addHashtag($hashtag);
        $post->setAuthor($user);
        $this->entityManager->persist($post);

        $this->entityManager->flush();

        // Tester la recommandation
        $recommendedPosts = $this->recommendationService->getRecommendedPosts($user, 5);

        $this->assertIsArray($recommendedPosts);
        $this->assertLessThanOrEqual(5, count($recommendedPosts));
        $this->assertContains($post, $recommendedPosts);
    }

    public function testGetSuggestedUsers(): void
    {
        // Créer des utilisateurs de test avec des posts pour les rendre plus actifs
        $user1 = new User();
        $user1->setEmail('test_suggested1@example.com');
        $user1->setPassword('password');
        $user1->setRoles(['ROLE_USER']);
        $user1->setFirstName('Test1');
        $user1->setLastName('User1');
        $this->entityManager->persist($user1);

        $user2 = new User();
        $user2->setEmail('test_suggested2@example.com');
        $user2->setPassword('password');
        $user2->setRoles(['ROLE_USER']);
        $user2->setFirstName('Test2');
        $user2->setLastName('User2');
        $this->entityManager->persist($user2);

        // Ajouter des posts pour user2 pour le rendre plus actif
        $post1 = new Post();
        $post1->setContent('Test post 1');
        $post1->setTitle('Test Post 1');
        $post1->setAuthor($user2);
        $this->entityManager->persist($post1);

        $post2 = new Post();
        $post2->setContent('Test post 2');
        $post2->setTitle('Test Post 2');
        $post2->setAuthor($user2);
        $this->entityManager->persist($post2);

        $this->entityManager->flush();

        // Tester les suggestions
        $suggestedUsers = $this->recommendationService->getSuggestedUsers($user1, 5);

        $this->assertIsArray($suggestedUsers);
        $this->assertLessThanOrEqual(5, count($suggestedUsers));
        $this->assertContains($user2, $suggestedUsers);
    }

    public function testGetTrendingHashtags(): void
    {
        // Nettoyer le cache avant le test
        $this->recommendationService->clearCache();

        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test_trending@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $this->entityManager->persist($user);

        // Créer des hashtags de test
        $hashtag1 = new Hashtag();
        $hashtag1->setName('trending1');
        $hashtag1->setUsageCount(5); // Plus utilisé que hashtag2
        $this->entityManager->persist($hashtag1);

        $hashtag2 = new Hashtag();
        $hashtag2->setName('trending2');
        $hashtag2->setUsageCount(3);
        $this->entityManager->persist($hashtag2);

        // Créer des posts avec les hashtags
        $post1 = new Post();
        $post1->setContent('Test post 1');
        $post1->setTitle('Test Post 1');
        $post1->setAuthor($user);
        $post1->addHashtag($hashtag1);
        $this->entityManager->persist($post1);

        $post2 = new Post();
        $post2->setContent('Test post 2');
        $post2->setTitle('Test Post 2');
        $post2->setAuthor($user);
        $post2->addHashtag($hashtag2);
        $this->entityManager->persist($post2);

        $this->entityManager->flush();

        // Tester les hashtags tendance
        $trendingHashtags = $this->recommendationService->getTrendingHashtags(5);

        $this->assertIsArray($trendingHashtags);
        $this->assertLessThanOrEqual(5, count($trendingHashtags));
        $this->assertContains($hashtag1, $trendingHashtags);
        $this->assertContains($hashtag2, $trendingHashtags);
        // Vérifier que hashtag1 apparaît avant hashtag2 car il est plus utilisé
        $this->assertSame($hashtag1, $trendingHashtags[0]);
        $this->assertSame($hashtag2, $trendingHashtags[1]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager) {
            $this->entityManager->close();
        }
    }
}
