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
     * Gère l'ajout ou la suppression d'un like sur un post.
     *
     * @param Post $post Le post concerné
     * @param User $user L'utilisateur qui like
     * @return bool true si le post est liké, false s'il est unliké
     */
    public function toggleLike(Post $post, User $user): bool
    {
        $existingLike = $this->entityManager->getRepository(PostLike::class)
            ->findOneBy(['post' => $post, 'user' => $user]);

        if ($existingLike) {
            $this->entityManager->remove($existingLike);
            $this->entityManager->flush();
            return false;
        }

        $like = new PostLike();
        $like->setPost($post);
        $like->setUser($user);

        $this->entityManager->persist($like);
        $this->entityManager->flush();

        // Notifier l'auteur du post
        if ($post->getAuthor() !== $user) {
            $this->notificationService->createLikeNotification($post, $user);
        }

        return true;
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
