<?php

namespace App\Entity;

use App\Repository\JobOfferRepository;
use App\Entity\Trait\JobOfferDetailsTrait;
use App\Entity\Trait\JobOfferMediaTrait;
use App\Entity\Trait\JobOfferContactTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use DateTimeImmutable;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: JobOfferRepository::class)]
#[ORM\Table(name: 'job_offer')]
#[ORM\HasLifecycleCallbacks]
class JobOffer
{
    use JobOfferDetailsTrait;
    use JobOfferMediaTrait;
    use JobOfferContactTrait;

    /**
     * @SuppressWarnings("PHPMD.ShortVariable")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'created_at')]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Recruiter::class, inversedBy: 'jobOffers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recruiter $recruiter = null;

    #[ORM\OneToMany(mappedBy: 'jobOffer', targetEntity: JobApplication::class, orphanRemoval: true)]
    private Collection $applications;

    #[ORM\OneToMany(mappedBy: 'jobOffer', targetEntity: Interview::class)]
    private Collection $interviews;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->applications = new ArrayCollection();
        $this->interviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
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

    /**
     * @return Collection<int, JobApplication>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(JobApplication $application): static
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setJobOffer($this);
        }
        return $this;
    }

    public function removeApplication(JobApplication $application): static
    {
        if ($this->applications->removeElement($application)) {
            if ($application->getJobOffer() === $this) {
                $application->setJobOffer(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Interview>
     */
    public function getInterviews(): Collection
    {
        return $this->interviews;
    }

    public function addInterview(Interview $interview): self
    {
        if (!$this->interviews->contains($interview)) {
            $this->interviews->add($interview);
            $interview->setJobOffer($this);
        }

        return $this;
    }

    public function removeInterview(Interview $interview): self
    {
        if ($this->interviews->removeElement($interview)) {
            if ($interview->getJobOffer() === $this) {
                $interview->setJobOffer(null);
            }
        }

        return $this;
    }
}
