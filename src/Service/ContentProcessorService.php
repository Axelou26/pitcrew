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
     * Traite le contenu d'un post pour extraire les hashtags et les mentions
     *
     * @param Post $post Le post à traiter
     * @param bool $isUpdate S'il s'agit d'une mise à jour (true) ou d'une création (false)
     * @return void
     */
    public function processPostContent(Post $post, bool $isUpdate = false): void
    {
        // Vérifier si le contenu est vide
        if (!$post->getContent() || trim($post->getContent()) === '') {
            $this->logger->info('Contenu vide, rien à traiter', [
                'post_id' => $post->getId()
            ]);
            return; // Ne rien faire si le contenu est vide
        }

        try {
            // Traiter les hashtags
            $this->processHashtags($post, $isUpdate);

            // Traiter les mentions
            $this->processMentions($post);

            $this->logger->info('Contenu traité avec succès', [
                'post_id' => $post->getId(),
                'hashtags_count' => count($post->getHashtags()),
                'mentions_count' => count($post->getMentions() ?? [])
            ]);
        } catch (\Throwable $e) {
            // Logger l'erreur mais ne pas la laisser remonter
            // pour éviter de bloquer la création du post
            $this->logger->error('Erreur lors du traitement du contenu: ' . $e->getMessage(), [
                'post_id' => $post->getId(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Traite les hashtags d'un post
     *
     * @param Post $post Le post à traiter
     * @param bool $isUpdate S'il s'agit d'une mise à jour
     * @return void
     */
    private function processHashtags(Post $post, bool $isUpdate = false): void
    {
        // Supprimer les anciens hashtags si c'est une mise à jour
        if ($isUpdate) {
            foreach ($post->getHashtags()->toArray() as $existingHashtag) {
                $post->removeHashtag($existingHashtag);

                // Si la méthode decrementUsageCount existe
                if (method_exists($existingHashtag, 'decrementUsageCount')) {
                    $existingHashtag->decrementUsageCount();
                }
            }
        }

        // Extraire les hashtags du contenu
        $hashtagNames = $post->extractHashtags();

        if (empty($hashtagNames)) {
            return; // Pas de hashtags à traiter
        }

        // Ajouter chaque hashtag
        foreach ($hashtagNames as $name) {
            try {
                // Nettoyage du nom de hashtag
                $name = trim($name);
                if (empty($name)) {
                    continue;
                }

                // Rechercher si le hashtag existe déjà
                $hashtag = $this->hashtagRepository->findOneBy(['name' => $name]);

                // S'il n'existe pas, le créer
                if (!$hashtag) {
                    $hashtag = new Hashtag();
                    $hashtag->setName($name);
                    $this->entityManager->persist($hashtag);
                }

                // Incrémenter le compteur d'utilisation si la méthode existe
                if (method_exists($hashtag, 'incrementUsageCount')) {
                    $hashtag->incrementUsageCount();
                }

                // Associer le hashtag au post
                $post->addHashtag($hashtag);
            } catch (\Throwable $e) {
                $this->logger->warning('Erreur lors du traitement du hashtag #' . $name . ': ' . $e->getMessage());
                // Continuer avec les autres hashtags
                continue;
            }
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
        foreach ($mentionUsernames as $username) {
            try {
                // Nettoyage du nom d'utilisateur
                $username = trim($username);
                if (empty($username)) {
                    continue;
                }

                // Rechercher l'utilisateur mentionné avec la méthode spéciale qui gère les noms d'utilisateur
                $mentionedUser = $this->userRepository->findByUsername($username);

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
                $this->logger->warning('Erreur lors du traitement de la mention @' . $username . ': ' . $e->getMessage());
                // Continuer avec les autres mentions
                continue;
            }
        }
    }
}
