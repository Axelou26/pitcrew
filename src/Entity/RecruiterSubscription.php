<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RecruiterSubscriptionRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecruiterSubscriptionRepository::class)]
class RecruiterSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Recruiter::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Recruiter $recruiter = null;

    #[ORM\ManyToOne(targetEntity: Subscription::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Subscription $subscription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'string', length: 255)]
    private string $paymentStatus = 'pending';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $remainingJobOffers = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $cancelled = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $autoRenew = true;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecruiter(): ?Recruiter
    {
        return $this->recruiter;
    }

    public function setRecruiter(?Recruiter $recruiter): static
    {
        $this->recruiter = $recruiter;

        return $this;
    }

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): static
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getRemainingJobOffers(): ?int
    {
        return $this->remainingJobOffers;
    }

    public function setRemainingJobOffers(?int $remainingJobOffers): static
    {
        $this->remainingJobOffers = $remainingJobOffers;

        return $this;
    }

    public function isCancelled(): ?bool
    {
        return $this->cancelled;
    }

    public function setCancelled(bool $cancelled): static
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    /**
     * Vérifie si l'abonnement est toujours valide.
     */
    public function isValid(): bool
    {
        return $this->isActive && $this->endDate > new DateTimeImmutable();
    }

    /**
     * Vérifie si l'abonnement expire bientôt (dans les 7 jours).
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now  = new DateTimeImmutable();
        $diff = $this->endDate->diff($now);

        return $diff->days <= 7 && $this->endDate > $now;
    }

    /**
     * Décrémente le nombre d'offres d'emploi restantes.
     */
    public function decrementRemainingJobOffers(): static
    {
        if ($this->remainingJobOffers !== null && $this->remainingJobOffers > 0) {
            $this->remainingJobOffers--;
        }

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isAutoRenew(): bool
    {
        return $this->autoRenew;
    }

    public function setAutoRenew(bool $autoRenew): static
    {
        $this->autoRenew = $autoRenew;

        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): static
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;

        return $this;
    }
}
