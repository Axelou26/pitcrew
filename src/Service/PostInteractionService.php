<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\PostLike;
use App\Entity\PostComment;
use App\Entity\PostShare;
use App\Entity\PostReaction;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class PostInteractionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService
    ) {
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Gère l'ajout, la suppression ou la modification d'une réaction sur un post.
     *
     * @param Post $post Le post concerné.
     * @param User $user L'utilisateur qui réagit.
     * @param string $reactionType Le type de réaction choisi (doit être une clé de PostLike::REACTIONS).
     * @return string|null Le type de réaction final, ou null si la réaction est supprimée.
     * @throws InvalidArgumentException Si le reactionType est invalide.
     */
    public function toggleLike(Post $post, User $user, string $reactionType): ?string
    {
        if (!array_key_exists($reactionType, PostLike::REACTIONS)) {
            throw new InvalidArgumentException('Type de réaction invalide : ' . $reactionType);
        }

        $existingLike = $this->entityManager->getRepository(PostLike::class)
            ->findOneBy(['post' => $post, 'user' => $user]);

        if ($existingLike) {
            // Si c'est la même réaction, on la supprime
            if ($existingLike->getReactionType() === $reactionType) {
                $this->entityManager->remove($existingLike);
                $this->entityManager->flush();
                return null;
            }
            
            // Si c'est une réaction différente, on met à jour
            $existingLike->setReactionType($reactionType);
            $this->entityManager->flush();
            return $reactionType;
        }

        // Nouvelle réaction
        $like = new PostLike();
        $like->setPost($post);
        $like->setUser($user);
        $like->setReactionType($reactionType);
        $this->entityManager->persist($like);
        $this->entityManager->flush();

        // Notifier uniquement pour une nouvelle réaction
        $this->notificationService->notifyPostLike($like);

        return $reactionType;
    }

    public function addComment(
        Post $post,
        User $author,
        string $content,
        ?PostComment $parentComment = null
    ): PostComment {
        $comment = new PostComment();
        $comment->setPost($post);
        $comment->setAuthor($author);
        $comment->setContent($content);

        if ($parentComment) {
            $comment->setParent($parentComment);
        }

        $this->entityManager->persist($comment);
        $post->addComment($comment);
        $this->entityManager->flush();

        // Notifier avec le commentaire créé au lieu du post
        $this->notificationService->notifyPostComment($comment);

        return $comment;
    }

    /**
     * Crée un nouveau post qui repartage un post existant.
     *
     * @param Post $originalPost Le post à repartager.
     * @param User $sharingUser L'utilisateur qui repartage.
     * @param string|null $comment Le commentaire ajouté lors du repartage (devient le contenu du nouveau post).
     * @return Post Le nouveau post créé (le repartage).
     */
    public function sharePost(Post $originalPost, User $sharingUser, ?string $comment = null): Post
    {
        // Créer un nouveau Post pour le repartage
        $repost = new Post();
        $repost->setAuthor($sharingUser);       // L'auteur est celui qui partage
        $repost->setOriginalPost($originalPost); // Lier au post original
        
        // Le commentaire de partage devient le contenu du nouveau post
        // Si pas de commentaire, on pourrait mettre un contenu par défaut ou laisser vide
        $repost->setContent($comment ?? ''); // Utiliser le commentaire ou une chaîne vide
        // Le titre pourrait être vide ou généré, laissons le vide pour l'instant
        // L'image n'est pas copiée par défaut

        $this->entityManager->persist($repost);

        // Incrémenter le compteur de partages sur le post original (si on garde le compteur)
        // Note: Si on supprime PostShare, cette logique de compteur devra changer
        // Pour l'instant, on peut le laisser ou le commenter.
        // $originalPost->setSharesCounter($originalPost->getSharesCounter() + 1);
        // $this->entityManager->persist($originalPost); 
        // Alternative propre: le compteur sera $originalPost->getReposts()->count() dans l'entité ou le template

        $this->entityManager->flush(); // Sauvegarde le nouveau post (et potentiellement la mise à jour du compteur)

        // Notifier l'auteur du post original qu'il a été repartagé
        // Adapter la notification si nécessaire pour prendre le nouveau $repost comme contexte
        // $this->notificationService->notifyPostShare($originalPost, $sharingUser); // Ancienne notification
        // Idéalement, créer une nouvelle méthode de notification pour les repartages
        // $this->notificationService->notifyPostRepost($repost, $sharingUser);

        return $repost; // Retourner le nouveau post créé
    }

    public function deleteComment(PostComment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }
}
