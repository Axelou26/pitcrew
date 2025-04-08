<?php

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

class NotificationService
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
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
     */
    public function notifyNewApplication(JobApplication $application): void
    {
        $recruiter = $application->getJobOffer()->getRecruiter();
        $applicant = $application->getApplicant();
        $jobOffer = $application->getJobOffer();

        $title = 'Nouvelle candidature';
        $message = sprintf(
            '%s a postulé à votre offre "%s"',
            $applicant->getFullName(),
            $jobOffer->getTitle()
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
     */
    public function notifyApplicationStatusChange(JobApplication $application): void
    {
        $applicant = $application->getApplicant();
        $jobOffer = $application->getJobOffer();
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
            $jobOffer->getTitle(),
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
     *
     * @param Post $post Le post contenant la mention
     * @param User $user L'utilisateur mentionné
     * @return void
     */
    public function notifyMention(Post $post, User $user): void
    {
        if ($post->getAuthor() === $user) {
            // Ne pas notifier l'auteur du post
            return;
        }

        try {
            $notification = new Notification();
            $notification->setUser($user);
            $notification->setType(Notification::TYPE_MENTION);
            $notification->setEntityType('post');
            $notification->setEntityId($post->getId());
            $notification->setActorId($post->getAuthor()->getId());

            // Définir le titre et le message
            $notification->setTitle('Nouvelle mention');
            $notification->setMessage('Vous a mentionné dans une publication');
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
     *
     * @param PostLike $like Le like
     * @return void
     */
    public function notifyPostLike(PostLike $like): void
    {
        $post = $like->getPost();
        $author = $post->getAuthor();
        $user = $like->getUser();

        if ($author === $user) {
            // Ne pas notifier l'auteur s'il aime son propre post
            return;
        }

        try {
            $notification = new Notification();
            $notification->setUser($author);
            $notification->setType(Notification::TYPE_LIKE);
            $notification->setEntityType('post');
            $notification->setEntityId($post->getId());
            $notification->setActorId($user->getId());

            // Titre standard
            $notification->setTitle('Nouvelle réaction');

            // Message personnalisé en fonction du type de réaction
            $reactionType = $like->getReactionType();
            $message = 'A réagi à votre publication';

            switch ($reactionType) {
                case PostLike::REACTION_LIKE:
                    $message = 'A aimé votre publication';
                    break;
                case PostLike::REACTION_CONGRATS:
                    $message = 'A félicité votre publication';
                    break;
                case PostLike::REACTION_INTERESTING:
                    $message = 'Trouve votre publication intéressante';
                    break;
                case PostLike::REACTION_SUPPORT:
                    $message = 'Soutient votre publication';
                    break;
                case PostLike::REACTION_ENCOURAGING:
                    $message = 'Encourage votre publication';
                    break;
            }

            $notification->setMessage($message);
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

            $this->logger->info('Notification de like créée', [
                'post_id' => $post->getId(),
                'reaction_type' => $reactionType
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la création d\'une notification de like', [
                'error' => $e->getMessage(),
                'post_id' => $post->getId()
            ]);
        }
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
