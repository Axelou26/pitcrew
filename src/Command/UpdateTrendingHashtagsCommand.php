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

#[AsCommand(
    name: 'app:update-trending-hashtags',
    description: 'Met à jour les hashtags tendance',
)]
class UpdateTrendingHashtagsCommand extends Command
{
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
        $io->title('Mise à jour des hashtags tendance');

        // Récupérer tous les hashtags utilisés récemment (dernières 24h)
        $now = new \DateTimeImmutable();
        $oneDayAgo = $now->modify('-1 day');

        $recentPosts = $this->postRepository->findRecentPostsWithHashtags($oneDayAgo);

        if (empty($recentPosts)) {
            $io->warning('Aucun post récent avec des hashtags trouvé.');
            return Command::SUCCESS;
        }

        // Calculer le score de tendance pour chaque hashtag
        $hashtags = [];
        foreach ($recentPosts as $post) {
            foreach ($post->getHashtags() as $hashtag) {
                $id = $hashtag->getId();

                if (!isset($hashtags[$id])) {
                    $hashtags[$id] = [
                        'hashtag' => $hashtag,
                        'recentUsage' => 0,
                        'likes' => 0,
                        'comments' => 0,
                        'shares' => 0
                    ];
                }

                // Comptabiliser l'utilisation récente
                $hashtags[$id]['recentUsage']++;

                // Ajouter les métriques d'engagement
                $hashtags[$id]['likes'] += $post->getLikesCount();
                $hashtags[$id]['comments'] += $post->getCommentsCounter();
                $hashtags[$id]['shares'] += $post->getSharesCounter();
            }
        }

        // Calculer un score de tendance pour chaque hashtag
        foreach ($hashtags as &$data) {
            $data['trendingScore'] =
                ($data['recentUsage'] * 2) +          // Plus de poids pour l'utilisation récente
                ($data['likes'] * 0.5) +              // Poids des likes
                ($data['comments'] * 1) +             // Plus de poids pour les commentaires
                ($data['shares'] * 1.5);              // Encore plus de poids pour les partages
        }

        // Trier les hashtags par score de tendance
        usort($hashtags, function ($a, $b) {
            return $b['trendingScore'] <=> $a['trendingScore'];
        });

        // Limiter aux 20 premiers hashtags tendance
        $trendingHashtags = array_slice($hashtags, 0, 20);

        $io->section('Hashtags tendance mis à jour');
        $io->table(
            ['Hashtag', 'Usage récent', 'Likes', 'Commentaires', 'Partages', 'Score'],
            array_map(function ($data) {
                return [
                    $data['hashtag']->getFormattedName(),
                    $data['recentUsage'],
                    $data['likes'],
                    $data['comments'],
                    $data['shares'],
                    round($data['trendingScore'], 2)
                ];
            }, $trendingHashtags)
        );

        // Stocker les IDs des hashtags tendance dans le cache
        $trendingHashtagIds = array_map(function ($data) {
            return $data['hashtag']->getId();
        }, $trendingHashtags);

        $cacheItem = $this->cache->getItem('trending_hashtags');
        $cacheItem->set($trendingHashtagIds);
        $cacheItem->expiresAfter(3600); // Expire après 1 heure
        $this->cache->save($cacheItem);

        $io->success('Les hashtags tendance ont été mis à jour et stockés en cache pour 1 heure !');

        return Command::SUCCESS;
    }
}
