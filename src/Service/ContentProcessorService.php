<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\Hashtag;
use App\Entity\User;
use App\Repository\HashtagRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ContentProcessorService
{
    private HashtagRepository $hashtagRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        HashtagRepository $hashtagRepository,
        UserRepository $userRepository,
        NotificationService $notificationService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->hashtagRepository = $hashtagRepository;
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Traite le contenu d'un nouveau post
     */
    public function processNewPostContent(Post $post): void
    {
        $this->processContentInternal($post, false);
    }

    /**
     * Traite le contenu d'un post mis à jour
     */
    public function processUpdatedPostContent(Post $post): void
    {
        $this->processContentInternal($post, true);
    }

    /**
     * Logique interne de traitement du contenu
     */
    private function processContentInternal(Post $post, bool $isUpdate): void
    {
        if (!$post->getContent() || trim($post->getContent()) === '') {
            $this->logger->info('Contenu vide, rien à traiter', ['post_id' => $post->getId()]);
            return;
        }

        try {
            // Traiter les hashtags en premier (condition inversée pour éviter le else)
            if (!$isUpdate) {
                $this->processNewHashtags($post);
            }
            if ($isUpdate) {
                $this->processUpdatedHashtags($post);
            }

            // Traiter les mentions
            $this->processMentions($post);

            $this->logger->info('Contenu traité avec succès', [
                'post_id' => $post->getId(),
                'hashtags_count' => count($post->getHashtags()),
                'mentions_count' => count($post->getMentions() ?? [])
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du traitement du contenu: ' . $e->getMessage(), [
                'post_id' => $post->getId(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Traite les hashtags d'un nouveau post
     */
    private function processNewHashtags(Post $post): void
    {
        $hashtagNames = $post->extractHashtags();
        if (empty($hashtagNames)) {
            return;
        }
        foreach ($hashtagNames as $name) {
            $this->processHashtag($post, $name);
        }
    }

    /**
     * Traite les hashtags d'un post mis à jour
     */
    private function processUpdatedHashtags(Post $post): void
    {
        $this->removeExistingHashtags($post);
        $this->processNewHashtags($post); // Réutilise la logique d'ajout
    }

    private function removeExistingHashtags(Post $post): void
    {
        foreach ($post->getHashtags()->toArray() as $existingHashtag) {
            $post->removeHashtag($existingHashtag);

            if (method_exists($existingHashtag, 'decrementUsageCount')) {
                $existingHashtag->decrementUsageCount();
            }
        }
    }

    private function processHashtag(Post $post, string $name): void
    {
        try {
            $name = trim($name);
            if (empty($name)) {
                return;
            }

            $hashtag = $this->getOrCreateHashtag($name);
            $this->updateHashtagUsage($hashtag);
            $post->addHashtag($hashtag);
        } catch (\Throwable $e) {
            $this->logger->warning('Erreur lors du traitement du hashtag #' . $name . ': ' . $e->getMessage());
        }
    }

    private function getOrCreateHashtag(string $name): Hashtag
    {
        $hashtag = $this->hashtagRepository->findOneBy(['name' => $name]);

        if (!$hashtag) {
            $hashtag = new Hashtag();
            $hashtag->setName($name);
            $this->entityManager->persist($hashtag);
        }

        return $hashtag;
    }

    private function updateHashtagUsage(Hashtag $hashtag): void
    {
        if (method_exists($hashtag, 'incrementUsageCount')) {
            $hashtag->incrementUsageCount();
        }
    }

    /**
     * Traite les mentions d'un post
     *
     * @param Post $post Le post à traiter
     * @return void
     */
    private function processMentions(Post $post): void
    {
        // Extraire les mentions du contenu
        $mentionUsernames = $post->extractMentions();

        if (empty($mentionUsernames)) {
            return; // Pas de mentions à traiter
        }

        // Réinitialiser les mentions
        $post->setMentions([]);

        // Ajouter chaque mention
        foreach ($mentionUsernames as $fullName) {
            try {
                // Nettoyage du nom complet
                $fullName = trim($fullName);
                if (empty($fullName)) {
                    continue;
                }

                // Rechercher l'utilisateur mentionné par son prénom et nom
                $mentionedUser = $this->userRepository->findByFullName($fullName);

                if ($mentionedUser) {
                    // Ajouter l'ID de l'utilisateur à la liste des mentions
                    $post->addMention($mentionedUser);

                    // Si l'utilisateur mentionné n'est pas l'auteur, créer une notification
                    if ($mentionedUser !== $post->getAuthor()) {
                        try {
                            $this->notificationService->notifyMention($post, $mentionedUser);
                        } catch (\Throwable $e) {
                            $this->logger->warning('Erreur lors de l\'envoi de la notification: ' . $e->getMessage());
                            // Ne pas bloquer le processus si la notification échoue
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Erreur lors du traitement de la mention @' . $fullName . ': ' . $e->getMessage());
                // Continuer avec les autres mentions
                continue;
            }
        }
    }
}
