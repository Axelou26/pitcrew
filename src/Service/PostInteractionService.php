<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\PostLike;
use App\Entity\PostComment;
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
     */
    public function sharePost(Post $originalPost, User $sharingUser, ?string $comment = null): Post
    {
        $repost = new Post();
        $repost->setAuthor($sharingUser);
        $repost->setOriginalPost($originalPost);
        $repost->setContent($comment ?? '');
        $repost->setTitle($originalPost->getTitle() ?? '');

        $this->entityManager->persist($repost);
        $this->entityManager->flush();

        // Notifier l'auteur du post original qu'il a été repartagé
        $this->notificationService->notifyPostShare($repost);

        return $repost;
    }

    public function deleteComment(PostComment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }
}
