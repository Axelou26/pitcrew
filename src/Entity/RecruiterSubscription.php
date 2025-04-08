<?php

namespace App\Entity;

use App\Repository\RecruiterSubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecruiterSubscriptionRepository::class)]
class RecruiterSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recruiter $recruiter = null;

    #[ORM\ManyToOne(inversedBy: 'recruiterSubscriptions', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Subscription $subscription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column(length: 50)]
    private ?string $paymentStatus = null;

    #[ORM\Column(nullable: true)]
    private ?int $remainingJobOffers = null;

    #[ORM\Column]
    private ?bool $cancelled = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $autoRenew = true;

    #[ORM\Column(length: 255, nullable: true)]
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): static
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
     * Vérifie si l'abonnement est toujours valide
     */
    public function isValid(): bool
    {
        return $this->isActive && $this->endDate > new \DateTime();
    }

    /**
     * Vérifie si l'abonnement expire bientôt (dans les 7 jours)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = new \DateTime();
        $diff = $this->endDate->diff($now);

        return $diff->days <= 7 && $this->endDate > $now;
    }

    /**
     * Décrémente le nombre d'offres d'emploi restantes
     */
    public function decrementRemainingJobOffers(): static
    {
        if ($this->remainingJobOffers !== null && $this->remainingJobOffers > 0) {
            $this->remainingJobOffers--;
        }

        return $this;
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
