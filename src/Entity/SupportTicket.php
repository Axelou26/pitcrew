<?php

namespace App\Entity;

use App\Repository\SupportTicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: SupportTicketRepository::class)]
class SupportTicket
{
    /**
     * @SuppressWarnings("PHPMD.ShortVariable")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'supportTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 50)]
    private ?string $priority = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $replies = [];

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->status = 'new';
        $this->priority = 'normal';
        $this->replies = [];
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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getReplies(): ?array
    {
        return $this->replies;
    }

    public function setReplies(?array $replies): static
    {
        $this->replies = $replies;

        return $this;
    }

    public function addReply(array $reply): static
    {
        $this->replies[] = $reply;

        return $this;
    }

    /**
     * Vérifie si le ticket est prioritaire
     */
    public function isPriority(): bool
    {
        return $this->priority === 'high';
    }

    /**
     * Renvoie la classe CSS correspondant au statut du ticket
     */
    public function getStatusClass(): string
    {
        return match ($this->status) {
            'new' => 'info',
            'in_progress' => 'primary',
            'waiting_for_user' => 'warning',
            'waiting_for_support' => 'secondary',
            'resolved' => 'success',
            'closed' => 'dark',
            default => 'light'
        };
    }

    /**
     * Renvoie la classe CSS correspondant à la priorité du ticket
     */
    public function getPriorityClass(): string
    {
        return match ($this->priority) {
            'high' => 'danger',
            'normal' => 'primary',
            'low' => 'secondary',
            default => 'light'
        };
    }

    /**
     * Renvoie le libellé du statut du ticket
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'new' => 'Nouveau',
            'in_progress' => 'En cours',
            'waiting_for_user' => 'En attente de votre réponse',
            'waiting_for_support' => 'En attente de réponse du support',
            'resolved' => 'Résolu',
            'closed' => 'Fermé',
            default => 'Inconnu'
        };
    }

    /**
     * Renvoie le libellé de la priorité du ticket
     */
    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            'high' => 'Prioritaire',
            'normal' => 'Normal',
            'low' => 'Faible',
            default => 'Inconnu'
        };
    }
}
