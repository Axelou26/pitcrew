<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\JobApplication;
use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\PostComment;
use App\Entity\PostShare;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Crée une notification pour un utilisateur
     */
    public function createNotification(
        User $user,
        string $title,
        string $message,
        ?string $link = null,
        string $type = 'info'
    ): Notification {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setLink($link);
        $notification->setType($type);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Notifie un recruteur d'une nouvelle candidature
     * @throws RuntimeException Si les relations requises sont nulles
     */
    public function notifyNewApplication(JobApplication $application): void
    {
        $jobOffer = $application->getJobOffer();
        if (!$jobOffer) {
            throw new RuntimeException('JobOffer not found for application');
        }

        $recruiter = $jobOffer->getRecruiter();
        if (!$recruiter) {
            throw new RuntimeException('Recruiter not found for job offer');
        }

        $applicant = $application->getApplicant();
        if (!$applicant) {
            throw new RuntimeException('Applicant not found for application');
        }

        $title = 'Nouvelle candidature';
        $message = sprintf(
            '%s a postulé à votre offre "%s"',
            $applicant->getFullName(),
            $jobOffer->getTitle() ?? 'Sans titre'
        );

        $link = $this->urlGenerator->generate(
            'app_job_application_show',
            ['id' => $application->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->createNotification($recruiter, $title, $message, $link, 'application');
    }

    /**
     * Notifie un candidat du changement de statut de sa candidature
     * @throws RuntimeException Si les relations requises sont nulles
     */
    public function notifyApplicationStatusChange(JobApplication $application): void
    {
        $applicant = $application->getApplicant();
        if (!$applicant) {
            throw new RuntimeException('Applicant not found for application');
        }

        $jobOffer = $application->getJobOffer();
        if (!$jobOffer) {
            throw new RuntimeException('JobOffer not found for application');
        }

        $status = $application->getStatus();

        $statusLabels = [
            'pending' => 'en attente',
            'accepted' => 'acceptée',
            'rejected' => 'refusée',
            'interview' => 'entretien programmé'
        ];

        $statusTypes = [
            'pending' => 'info',
            'accepted' => 'success',
            'rejected' => 'danger',
            'interview' => 'warning'
        ];

        $title = 'Mise à jour de candidature';
        $message = sprintf(
            'Votre candidature pour l\'offre "%s" est maintenant %s',
            $jobOffer->getTitle() ?? 'Sans titre',
            $statusLabels[$status] ?? $status
        );

        $link = $this->urlGenerator->generate(
            'app_job_application_show',
            ['id' => $application->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->createNotification(
            $applicant,
            $title,
            $message,
            $link,
            $statusTypes[$status] ?? 'info'
        );
    }

    /**
     * Notifie un utilisateur lorsqu'il est mentionné dans un post
     * @throws RuntimeException Si l'auteur du post est null
     */
    public function notifyMention(Post $post, User $user): void
    {
        $author = $post->getAuthor();
        if (!$author) {
            throw new RuntimeException('Author not found for post');
        }

        if ($author === $user) {
            // Ne pas notifier l'auteur du post
            return;
        }

        try {
            $notification = new Notification();
            $notification->setUser($user);
            $notification->setType(Notification::TYPE_MENTION);
            $notification->setEntityType('post');
            $notification->setEntityId($post->getId());
            $notification->setActorId($author->getId());

            $notification->setTitle('Nouvelle mention');
            $notification->setMessage('Vous a mentionné dans une publication');
            $notification->setIsRead(false);

            $link = $this->urlGenerator->generate(
                'app_post_show',
                ['id' => $post->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $notification->setLink($link);

            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            $this->logger->info('Notification de mention créée', [
                'user_id' => $user->getId(),
                'post_id' => $post->getId()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la création d\'une notification de mention', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'post_id' => $post->getId()
            ]);
        }
    }

    /**
     * Notifie l'auteur d'un post qu'un utilisateur a aimé son post
     * @throws RuntimeException Si les relations requises sont nulles
     */
    public function notifyPostLike(PostLike $like): void
    {
        $post = $this->validatePostLike($like);
        $author = $post->getAuthor();
        $user = $like->getUser();

        if ($author === $user) {
            return;
        }

        try {
            $notification = $this->createPostLikeNotification($like);
            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            $this->logger->info('Notification de like créée', [
                'post_id' => $post->getId(),
                'reaction_type' => $like->getReactionType()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la création d\'une notification de like', [
                'error' => $e->getMessage(),
                'post_id' => $post->getId(),
                'user_id' => $user->getId()
            ]);
        }
    }

    private function validatePostLike(PostLike $like): Post
    {
        $post = $like->getPost();
        if (!$post) {
            throw new RuntimeException('Post not found for like');
        }

        $author = $post->getAuthor();
        if (!$author) {
            throw new RuntimeException('Author not found for post');
        }

        $user = $like->getUser();
        if (!$user) {
            throw new RuntimeException('User not found for like');
        }

        return $post;
    }

    private function createPostLikeNotification(PostLike $like): Notification
    {
        $post = $like->getPost();
        $notification = new Notification();
        $notification->setUser($post->getAuthor());
        $notification->setType(Notification::TYPE_LIKE);
        $notification->setEntityType('post');
        $notification->setEntityId($post->getId());
        $notification->setActorId($like->getUser()->getId());
        $notification->setTitle('Nouvelle réaction');
        $notification->setMessage($this->getReactionMessage($like->getReactionType()));
        $notification->setIsRead(false);
        $notification->setLink($this->generatePostLink($post));

        return $notification;
    }

    private function getReactionMessage(string $reactionType): string
    {
        return match ($reactionType) {
            PostLike::REACTION_LIKE => 'A aimé votre publication',
            PostLike::REACTION_CONGRATS => 'A félicité votre publication',
            PostLike::REACTION_INTERESTING => 'Trouve votre publication intéressante',
            PostLike::REACTION_SUPPORT => 'Soutient votre publication',
            PostLike::REACTION_ENCOURAGING => 'Encourage votre publication',
            default => 'A réagi à votre publication'
        };
    }

    private function generatePostLink(Post $post): string
    {
        return $this->urlGenerator->generate(
            'app_post_show',
            ['id' => $post->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Notifie l'auteur d'un post qu'un utilisateur a commenté son post
     *
     * @param PostComment $comment Le commentaire
     * @return void
     */
    public function notifyPostComment(PostComment $comment): void
    {
        $post = $comment->getPost();
        $author = $post->getAuthor();
        $commentAuthor = $comment->getAuthor();

        if ($author === $commentAuthor) {
            // Ne pas notifier l'auteur s'il commente son propre post
            return;
        }

        try {
            $notification = new Notification();
            $notification->setUser($author);
            $notification->setType(Notification::TYPE_COMMENT);
            $notification->setEntityType('post');
            $notification->setEntityId($post->getId());
            $notification->setActorId($commentAuthor->getId());

            // Définir le titre et le message
            $notification->setTitle('Nouveau commentaire');
            $notification->setMessage('A commenté votre publication');
            $notification->setIsRead(false);

            // Générer le lien vers le post
            $link = $this->urlGenerator->generate(
                'app_post_show',
                ['id' => $post->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $notification->setLink($link);

            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            $this->logger->info('Notification de commentaire créée', [
                'post_id' => $post->getId(),
                'comment_id' => $comment->getId()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la création d\'une notification de commentaire', [
                'error' => $e->getMessage(),
                'post_id' => $post->getId(),
                'comment_id' => $comment->getId()
            ]);
        }
    }

    /**
     * Notifie l'auteur d'un post qu'un utilisateur a partagé son post
     *
     * @param PostShare $share Le partage
     * @return void
     */
    public function notifyPostShare(PostShare $share): void
    {
        $post = $share->getPost();
        $author = $post->getAuthor();
        $shareUser = $share->getUser();

        if ($author === $shareUser) {
            // Ne pas notifier l'auteur s'il partage son propre post
            return;
        }

        try {
            $notification = new Notification();
            $notification->setUser($author);
            $notification->setType(Notification::TYPE_SHARE);
            $notification->setEntityType('post');
            $notification->setEntityId($post->getId());
            $notification->setActorId($shareUser->getId());

            // Définir le titre et le message
            $notification->setTitle('Nouveau partage');
            $notification->setMessage('A partagé votre publication');
            $notification->setIsRead(false);

            // Générer le lien vers le post
            $link = $this->urlGenerator->generate(
                'app_post_show',
                ['id' => $post->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $notification->setLink($link);

            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            $this->logger->info('Notification de partage créée', [
                'post_id' => $post->getId(),
                'share_id' => $share->getId()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la création d\'une notification de partage', [
                'error' => $e->getMessage(),
                'post_id' => $post->getId(),
                'share_id' => $share->getId()
            ]);
        }
    }

    /**
     * Notifie tous les utilisateurs mentionnés dans un post
     */
    public function notifyMentionedUsers(Post $post): void
    {
        try {
            $mentions = $post->extractMentions();
            if (empty($mentions)) {
                return;
            }

            $userRepository = $this->entityManager->getRepository(User::class);
            foreach ($mentions as $username) {
                $user = $userRepository->findOneBy(['username' => $username]);
                if ($user) {
                    $this->notifyMention($post, $user);
                }
            }

            $this->logger->info('Notifications envoyées aux utilisateurs mentionnés', [
                'post_id' => $post->getId(),
                'mentions_count' => count($mentions)
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de l\'envoi des notifications de mentions', [
                'error' => $e->getMessage(),
                'post_id' => $post->getId()
            ]);
        }
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsRead(User $user): void
    {
        $repository = $this->entityManager->getRepository(Notification::class);
        $repository->markAllAsRead($user);
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->setIsRead(true);
        $this->entityManager->flush();
    }
}
