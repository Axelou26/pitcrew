<?php

namespace App\Command;

use App\Entity\Hashtag;
use App\Repository\HashtagRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Cache\CacheItemPoolInterface;
use DateTimeImmutable;

#[AsCommand(
    name: 'app:update-trending-hashtags',
    description: 'Met à jour les hashtags tendance',
)]
class UpdateTrendingHashtagsCommand extends Command
{
    protected static $defaultName = 'app:update-trending-hashtags';
    protected static $defaultDescription = 'Met à jour les hashtags tendance';

    private EntityManagerInterface $entityManager;
    private HashtagRepository $hashtagRepository;
    private PostRepository $postRepository;
    private CacheItemPoolInterface $cache;

    public function __construct(
        EntityManagerInterface $entityManager,
        HashtagRepository $hashtagRepository,
        PostRepository $postRepository,
        CacheItemPoolInterface $cache
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->hashtagRepository = $hashtagRepository;
        $this->postRepository = $postRepository;
        $this->cache = $cache;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->info('Début de la mise à jour des hashtags tendance...');

            // Récupérer tous les posts des dernières 24 heures
            $yesterday = new \DateTime('-24 hours');
            $recentPosts = $this->postRepository->findPostsSince($yesterday);

            // Compteur pour chaque hashtag
            $hashtagCounts = [];

            foreach ($recentPosts as $post) {
                $hashtags = $post->getHashtags();
                foreach ($hashtags as $hashtag) {
                    $hashtagName = $hashtag->getName();
                    if (!isset($hashtagCounts[$hashtagName])) {
                        $hashtagCounts[$hashtagName] = 0;
                    }
                    $hashtagCounts[$hashtagName]++;
                }
            }

            // Trier les hashtags par nombre d'utilisations
            arsort($hashtagCounts);
            // Ne garder que les 5 premiers
            $hashtagCounts = array_slice($hashtagCounts, 0, 5, true);

            // Réinitialiser tous les compteurs à 0
            $this->hashtagRepository->resetAllUsageCounts();

            // Mettre à jour le compteur d'utilisation pour les 5 hashtags les plus utilisés
            foreach ($hashtagCounts as $hashtagName => $count) {
                $hashtag = $this->hashtagRepository->findOneBy(['name' => $hashtagName]);
                if ($hashtag) {
                    $hashtag->setUsageCount($count);
                    $hashtag->setLastUsedAt(new \DateTimeImmutable());
                }
            }

            $this->entityManager->flush();

            // Mettre en cache les hashtags tendance
            $trendingHashtags = $this->hashtagRepository->findTrending(5);
            $cacheItem = $this->cache->getItem('trending_hashtags');
            $cacheItem->set(array_map(fn($h) => $h->getId(), $trendingHashtags));
            $cacheItem->expiresAfter(new \DateInterval('PT24H')); // Expire après 24 heures
            $this->cache->save($cacheItem);

            $io->success('Les hashtags tendance ont été mis à jour avec succès.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Une erreur est survenue : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
