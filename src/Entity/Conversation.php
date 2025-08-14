<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ConversationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $participant1 = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $participant2 = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: Message::class, cascade: ['persist', 'remove'])]
    private Collection $messages;

    #[ORM\Column]
    private ?DateTimeImmutable $lastMessageAt = null;

    #[ORM\ManyToOne]
    private ?JobApplication $jobApplication = null;

    public function __construct()
    {
        $this->messages      = new ArrayCollection();
        $this->createdAt     = new DateTimeImmutable();
        $this->updatedAt     = new DateTimeImmutable();
        $this->lastMessageAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParticipant1(): ?User
    {
        return $this->participant1;
    }

    public function setParticipant1(?User $participant1): static
    {
        $this->participant1 = $participant1;

        return $this;
    }

    public function getParticipant2(): ?User
    {
        return $this->participant2;
    }

    public function setParticipant2(?User $participant2): static
    {
        $this->participant2 = $participant2;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        // Ensure messages is never null
        if (!isset($this->messages)) {
            $this->messages = new ArrayCollection();
        }

        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        // Ensure messages is initialized
        if (!isset($this->messages)) {
            $this->messages = new ArrayCollection();
        }

        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
            $this->lastMessageAt = new DateTimeImmutable();
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        // Ensure messages is initialized
        if (!isset($this->messages)) {
            $this->messages = new ArrayCollection();
        }

        if ($this->messages->removeElement($message)) {
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }

    public function getLastMessageAt(): ?DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(DateTimeImmutable $lastMessageAt): static
    {
        $this->lastMessageAt = $lastMessageAt;

        return $this;
    }

    public function getJobApplication(): ?JobApplication
    {
        return $this->jobApplication;
    }

    public function setJobApplication(?JobApplication $jobApplication): static
    {
        $this->jobApplication = $jobApplication;

        return $this;
    }

    // Méthodes manquantes ajoutées pour PHPStan

    public function addParticipant(User $participant): static
    {
        if ($this->participant1 === null) {
            $this->participant1 = $participant;
        } elseif ($this->participant2 === null) {
            $this->participant2 = $participant;
        }

        return $this;
    }

    public function getOtherParticipant(User $user): ?User
    {
        if ($this->participant1 === $user) {
            return $this->participant2;
        }
        if ($this->participant2 === $user) {
            return $this->participant1;
        }

        return null;
    }

    public function hasUnreadMessages(User $user): bool
    {
        if (!isset($this->messages)) {
            return false;
        }

        foreach ($this->messages as $message) {
            if ($message->getRecipient() === $user && !$message->isRead()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the last message in the conversation.
     */
    public function getLastMessage(): ?Message
    {
        // Ensure messages is initialized
        if (!isset($this->messages) || $this->messages->isEmpty()) {
            return null;
        }

        $criteria = Criteria::create()
            ->orderBy(['createdAt' => Criteria::DESC])
            ->setMaxResults(1);

        return $this->messages->matching($criteria)->first();
    }
}
