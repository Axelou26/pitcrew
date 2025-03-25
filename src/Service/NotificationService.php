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

class NotificationService
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
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
     * Notifie l'auteur d'un post quand quelqu'un aime son post
     */
    public function notifyPostLike(PostLike $postLike): void
    {
        $post = $postLike->getPost();
        $postAuthor = $post->getAuthor();
        $liker = $postLike->getUser();
        
        // Ne pas notifier si l'auteur aime son propre post
        if ($postAuthor->getId() === $liker->getId()) {
            return;
        }
        
        // Préparer les textes en fonction du type de réaction
        $reactionLabels = [
            PostLike::REACTION_LIKE => 'aimé',
            PostLike::REACTION_CONGRATS => 'félicité pour',
            PostLike::REACTION_INTERESTING => 'trouvé intéressant',
            PostLike::REACTION_SUPPORT => 'soutenu',
            PostLike::REACTION_ENCOURAGING => 'encouragé pour'
        ];
        
        $reactionType = $postLike->getReactionType();
        $reactionText = $reactionLabels[$reactionType] ?? 'aimé';
        
        $title = 'Nouvelle réaction';
        $message = sprintf(
            '%s a %s votre post "%s"',
            $liker->getFullName(),
            $reactionText,
            substr($post->getContent(), 0, 30) . (strlen($post->getContent()) > 30 ? '...' : '')
        );
        
        $link = $this->urlGenerator->generate(
            'app_post_show',
            ['id' => $post->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->createNotification($postAuthor, $title, $message, $link, 'reaction');
    }

    /**
     * Notifie l'auteur d'un post quand quelqu'un commente son post
     */
    public function notifyPostComment(PostComment $comment): void
    {
        $post = $comment->getPost();
        $postAuthor = $post->getAuthor();
        $commenter = $comment->getAuthor();
        
        // Ne pas notifier si l'auteur commente son propre post
        if ($postAuthor->getId() === $commenter->getId()) {
            return;
        }
        
        $title = 'Nouveau commentaire';
        $message = sprintf(
            '%s a commenté votre post "%s"',
            $commenter->getFullName(),
            substr($post->getContent(), 0, 30) . (strlen($post->getContent()) > 30 ? '...' : '')
        );
        
        $link = $this->urlGenerator->generate(
            'app_post_show',
            ['id' => $post->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        ) . '#comment-' . $comment->getId();

        $this->createNotification($postAuthor, $title, $message, $link, 'comment');
        
        // Si c'est une réponse à un commentaire, notifier aussi l'auteur du commentaire parent
        if ($comment->getParent() && $comment->getParent()->getAuthor()->getId() !== $commenter->getId()) {
            $parentAuthor = $comment->getParent()->getAuthor();
            
            // Ne pas notifier deux fois l'auteur du post s'il a aussi écrit le commentaire parent
            if ($parentAuthor->getId() !== $postAuthor->getId()) {
                $title = 'Nouvelle réponse';
                $message = sprintf(
                    '%s a répondu à votre commentaire sur le post "%s"',
                    $commenter->getFullName(),
                    substr($post->getContent(), 0, 30) . (strlen($post->getContent()) > 30 ? '...' : '')
                );
                
                $this->createNotification($parentAuthor, $title, $message, $link, 'comment');
            }
        }
    }

    /**
     * Notifie l'auteur d'un post quand quelqu'un partage son post
     */
    public function notifyPostShare(PostShare $share): void
    {
        $post = $share->getPost();
        $postAuthor = $post->getAuthor();
        $sharer = $share->getUser();
        
        // Ne pas notifier si l'auteur partage son propre post
        if ($postAuthor->getId() === $sharer->getId()) {
            return;
        }
        
        $title = 'Nouveau partage';
        $message = sprintf(
            '%s a partagé votre post "%s"',
            $sharer->getFullName(),
            substr($post->getContent(), 0, 30) . (strlen($post->getContent()) > 30 ? '...' : '')
        );
        
        $link = $this->urlGenerator->generate(
            'app_post_show',
            ['id' => $post->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->createNotification($postAuthor, $title, $message, $link, 'share');
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