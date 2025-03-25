<?php

namespace App\Entity;

use App\Repository\FriendshipRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FriendshipRepository::class)]
#[ORM\Table(name: 'friendship')]
class Friendship
{
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sentFriendRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requester = null;
    
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'receivedFriendRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $addressee = null;
    
    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_PENDING;
    
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;
    
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;
    
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
    
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
    
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
    
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }
    
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }
    
    public function accept(): self
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
    
    public function decline(): self
    {
        $this->status = self::STATUS_DECLINED;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
} 