<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
    /**
     * @SuppressWarnings("PHPMD.ShortVariable")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\Column(type: 'json')]
    private array $features = [];

    #[ORM\Column(nullable: true)]
    private ?int $maxJobOffers = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'subscription', targetEntity: RecruiterSubscription::class)]
    private Collection $recruiterSubs;

    public function __construct()
    {
        $this->recruiterSubs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function setFeatures(array $features): static
    {
        $this->features = $features;

        return $this;
    }

    public function getMaxJobOffers(): ?int
    {
        return $this->maxJobOffers;
    }

    public function setMaxJobOffers(?int $maxJobOffers): static
    {
        $this->maxJobOffers = $maxJobOffers;

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

    /**
     * @return Collection<int, RecruiterSubscription>
     */
    public function getRecruiterSubscriptions(): Collection
    {
        return $this->recruiterSubs;
    }

    public function addRecruiterSubscription(RecruiterSubscription $recruiterSub): static
    {
        if (!$this->recruiterSubs->contains($recruiterSub)) {
            $this->recruiterSubs->add($recruiterSub);
            $recruiterSub->setSubscription($this);
        }

        return $this;
    }

    public function removeRecruiterSubscription(RecruiterSubscription $recruiterSub): static
    {
        if ($this->recruiterSubs->removeElement($recruiterSub)) {
            // set the owning side to null (unless already changed)
            if ($recruiterSub->getSubscription() === $this) {
                $recruiterSub->setSubscription(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
