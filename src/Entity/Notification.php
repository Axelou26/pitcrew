<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NotificationRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Index(columns: ['user_id', 'is_read'], name: 'idx_notification_user_read')]
#[ORM\Index(columns: ['created_at'], name: 'idx_notification_created_at')]
#[ORM\Index(columns: ['type'], name: 'idx_notification_type')]
class Notification
{
    // Types de notifications
    public const TYPE_INFO           = 'info';
    public const TYPE_MENTION        = 'mention';
    public const TYPE_LIKE           = 'like';
    public const TYPE_COMMENT        = 'comment';
    public const TYPE_SHARE          = 'share';
    public const TYPE_FRIEND_REQUEST = 'friend_request';
    public const TYPE_APPLICATION    = 'application';

    /**
     * ID de la notification.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Indique si la notification a été lue.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    /**
     * Type de notification.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    /**
     * Utilisateur destinataire de la notification.
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Titre de la notification.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    /**
     * Message de la notification.
     */
    #[ORM\Column(type: 'text')]
    private string $message;

    /**
     * Date de création de la notification.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * Lien associé à la notification.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $link = null;

    /**
     * Type d'entité associée.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $entityType = null;

    /**
     * ID de l'entité associée.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $entityId = null;

    /**
     * ID de l'utilisateur qui a déclenché la notification.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $actorId = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->isRead    = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function isIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(?string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getActorId(): ?int
    {
        return $this->actorId;
    }

    public function setActorId(?int $actorId): static
    {
        $this->actorId = $actorId;

        return $this;
    }

    /**
     * Alias pour getLink() pour compatibilité avec les templates.
     */
    public function getTargetUrl(): ?string
    {
        return $this->link;
    }
}
