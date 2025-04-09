<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\PostLike;
use App\Entity\PostComment;
use App\Entity\PostShare;
use Doctrine\ORM\EntityManagerInterface;

class PostInteractionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService
    ) {
    }

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

        $this->notificationService->notifyPostLike($post, $user);

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
        $this->entityManager->flush();

        $this->notificationService->notifyPostComment($post, $author);

        return $comment;
    }

    public function sharePost(Post $post, User $user, ?string $comment = null): PostShare
    {
        $share = new PostShare();
        $share->setPost($post);
        $share->setUser($user);
        
        if ($comment) {
            $share->setComment($comment);
        }

        $this->entityManager->persist($share);
        $this->entityManager->flush();

        $this->notificationService->notifyPostShare($post, $user);

        return $share;
    }

    public function deleteComment(PostComment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }
} 