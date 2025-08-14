<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FriendshipRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FriendshipRepository::class)]
#[ORM\Table(name: 'friendship')]
class Friendship
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';

    /**
     * Identifiant de l'amitié.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Utilisateur qui a envoyé la demande d'amitié.
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sentFriendships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requester = null;

    /**
     * Utilisateur qui a reçu la demande d'amitié.
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'receivedFriendships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $addressee = null;

    /**
     * Statut de la demande d'amitié.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $status = 'pending';

    /**
     * Date de création de la demande d'amitié.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * Date de dernière mise à jour de la demande d'amitié.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Crée une nouvelle amitié acceptée entre deux utilisateurs.
     */
    public static function createAccepted(User $user1, User $user2): self
    {
        $friendship = new self();
        $friendship->setRequester($user1);
        $friendship->setAddressee($user2);
        $friendship->setStatus(self::STATUS_ACCEPTED);
        $friendship->updatedAt = new DateTimeImmutable();

        return $friendship;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequester(): ?User
    {
        return $this->requester;
    }

    public function setRequester(?User $requester): self
    {
        $this->requester = $requester;

        return $this;
    }

    public function getAddressee(): ?User
    {
        return $this->addressee;
    }

    public function setAddressee(?User $addressee): self
    {
        $this->addressee = $addressee;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status    = $status;
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    // Méthodes manquantes ajoutées pour PHPStan

    public function getFriend(User $user): ?User
    {
        if ($this->requester === $user) {
            return $this->addressee;
        }
        if ($this->addressee === $user) {
            return $this->requester;
        }

        return null;
    }

    public function getOtherUser(User $user): ?User
    {
        return $this->getFriend($user);
    }

    public function getUser(User $user): ?User
    {
        return $this->getFriend($user);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function accept(): self
    {
        $this->status    = 'accepted';
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    public function reject(): self
    {
        $this->status    = 'rejected';
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }
}
