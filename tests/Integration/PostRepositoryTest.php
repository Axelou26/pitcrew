<?php

namespace App\Tests\Integration;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Hashtag;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests d'intégration pour le PostRepository
 */
class PostRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private PostRepository $postRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->postRepository = $this->entityManager->getRepository(Post::class);

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
            'user'
        ];

        foreach ($tables as $table) {
            $this->entityManager->getConnection()->executeQuery("TRUNCATE TABLE {$table}");
        }

        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        $this->entityManager->clear();
    }

    /**
     * Crée un utilisateur de test
     */
    private function createTestUser(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $this->entityManager->persist($user);
        return $user;
    }

    /**
     * Crée un post de test
     */
    private function createTestPost(User $author, string $content, string $title): Post
    {
        $post = new Post();
        $post->setContent($content);
        $post->setTitle($title);
        $post->setAuthor($author);
        $this->entityManager->persist($post);
        return $post;
    }

    /**
     * Test de la récupération des posts récents avec leurs auteurs
     */
    public function testFindRecentWithAuthors(): void
    {
        $user = $this->createTestUser('test1@example.com');

        $post1 = $this->createTestPost($user, 'Premier post', 'Premier titre');
        $post2 = $this->createTestPost($user, 'Deuxième post', 'Deuxième titre');

        $this->entityManager->flush();

        $recentPosts = $this->postRepository->findRecentWithAuthors(2);

        $this->assertCount(2, $recentPosts);
        $this->assertEquals('Premier post', $recentPosts[0]->getContent());
        $this->assertEquals('Deuxième post', $recentPosts[1]->getContent());
        $this->assertEquals('Test User', $recentPosts[0]->getAuthor()->getFullName());
    }

    /**
     * Test de la recherche de posts par hashtag
     */
    public function testFindByHashtag(): void
    {
        $user = $this->createTestUser('test2@example.com');

        $hashtag = new Hashtag();
        $hashtag->setName('test');
        $this->entityManager->persist($hashtag);

        $post = $this->createTestPost($user, 'Post avec #test', 'Post avec hashtag');
        $post->addHashtag($hashtag);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $hashtag = $this->entityManager->find(Hashtag::class, $hashtag->getId());
        $posts = $this->postRepository->findByHashtag($hashtag);

        $this->assertCount(1, $posts);
        $this->assertEquals('Post avec #test', $posts[0]->getContent());
        $this->assertTrue($posts[0]->getHashtags()->contains($hashtag));
    }

    /**
     * Fournit des données de test pour la recherche de posts
     * @return array<string, array{string, int}>
     */
    public static function searchDataProvider(): array
    {
        return [
            'mot exact' => ['recherche', 1],
            'mot partiel' => ['rech', 1],
            'mot inexistant' => ['xyz', 0],
            'casse différente' => ['RECHERCHE', 1],
        ];
    }

    /**
     * Test de la recherche de posts
     */
    #[DataProvider('searchDataProvider')]
    public function testSearch(string $searchTerm, int $expectedCount): void
    {
        $user = $this->createTestUser('test3@example.com');

        $post1 = $this->createTestPost($user, 'Post avec le mot recherche', 'Post recherche');
        $post2 = $this->createTestPost($user, 'Un autre post', 'Autre post');

        $this->entityManager->flush();

        $results = $this->postRepository->search($searchTerm);

        $this->assertCount($expectedCount, $results);
        if ($expectedCount > 0) {
            $this->assertEquals('Post avec le mot recherche', $results[0]->getContent());
        }
    }

    protected function tearDown(): void
    {
        if ($this->entityManager) {
            $this->cleanDatabase();
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }
}
