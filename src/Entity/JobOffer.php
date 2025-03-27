<?php

namespace App\Entity;

use App\Repository\JobOfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: JobOfferRepository::class)]
class JobOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'entreprise est obligatoire')]
    private ?string $company = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de contrat est obligatoire')]
    private ?string $contractType = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La localisation est obligatoire')]
    private ?string $location = null;

    #[ORM\Column(nullable: true)]
    private ?int $salary = null;

    #[ORM\Column(type: Types::JSON)]
    private array $requiredSkills = [];

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'jobOffers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $recruiter = null;

    #[ORM\OneToMany(mappedBy: 'jobOffer', targetEntity: JobApplication::class, orphanRemoval: true)]
    private Collection $applications;

    #[ORM\Column]
    private ?bool $isRemote = false;

    #[ORM\Column]
    private ?bool $isPromoted = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'jobOffer', targetEntity: Interview::class)]
    private Collection $interviews;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->applications = new ArrayCollection();
        $this->requiredSkills = [];
        $this->isRemote = false;
        $this->isPromoted = false;
        $this->logoUrl = null;
        $this->image = null;
        $this->interviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getContractType(): ?string
    {
        return $this->contractType;
    }

    public function setContractType(string $contractType): static
    {
        $this->contractType = $contractType;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getSalary(): ?int
    {
        return $this->salary;
    }

    public function setSalary(?int $salary): static
    {
        $this->salary = $salary;
        return $this;
    }

    public function getRequiredSkills(): array
    {
        return $this->requiredSkills;
    }

    public function setRequiredSkills(array $requiredSkills): static
    {
        $this->requiredSkills = $requiredSkills;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRecruiter(): ?User
    {
        return $this->recruiter;
    }

    public function setRecruiter(?User $recruiter): static
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

    public function getIsRemote(): ?bool
    {
        return $this->isRemote;
    }

    public function setIsRemote(bool $isRemote): static
    {
        $this->isRemote = $isRemote;
        return $this;
    }

    public function getIsPromoted(): ?bool
    {
        return $this->isPromoted;
    }

    public function setIsPromoted(bool $isPromoted): static
    {
        $this->isPromoted = $isPromoted;
        return $this;
    }
    
    public function isPromoted(): ?bool
    {
        return $this->isPromoted;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;
        return $this;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(?string $logoUrl): static
    {
        $this->logoUrl = $logoUrl;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
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
            // set the owning side to null (unless already changed)
            if ($interview->getJobOffer() === $this) {
                $interview->setJobOffer(null);
            }
        }

        return $this;
    }
} 