<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApplicantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantRepository::class)]
class Applicant extends User
{
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private array $technicalSkills = [];

    /**
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private array $softSkills = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvFilename = null;

    /**
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private array $educationHistory = [];

    /**
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private array $workExperience = [];



    /**
     * @var Collection<int, JobApplication>
     */
    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: JobApplication::class, orphanRemoval: true)]
    private Collection $jobApplications;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $isActive = true;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $experienceLevel = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $availability = null;

    public function __construct()
    {
        parent::__construct();
        $this->jobApplications = new ArrayCollection();
        $this->setRoles(['ROLE_POSTULANT']);
        $this->technicalSkills  = [];
        $this->softSkills       = [];
        $this->educationHistory = [];
        $this->workExperience   = [];
        $this->isActive         = true;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getTechnicalSkills(): array
    {
        return $this->technicalSkills ?? [];
    }

    /**
     * @param array<int, string> $technicalSkills
     */
    public function setTechnicalSkills(array $technicalSkills): self
    {
        $this->technicalSkills = $technicalSkills;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getSoftSkills(): array
    {
        return $this->softSkills ?? [];
    }

    /**
     * @param array<int, string> $softSkills
     */
    public function setSoftSkills(array $softSkills): self
    {
        $this->softSkills = $softSkills;

        return $this;
    }

    public function getCvFilename(): ?string
    {
        return $this->cvFilename;
    }

    public function setCvFilename(?string $cvFilename): static
    {
        $this->cvFilename = $cvFilename;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getEducationHistory(): array
    {
        return $this->educationHistory ?? [];
    }

    /**
     * @param array<int, string> $educationHistory
     */
    public function setEducationHistory(array $educationHistory): self
    {
        $this->educationHistory = $educationHistory;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getWorkExperience(): array
    {
        return $this->workExperience ?? [];
    }

    /**
     * @param array<int, string> $workExperience
     */
    public function setWorkExperience(array $workExperience): self
    {
        $this->workExperience = $workExperience;

        return $this;
    }



    /**
     * @return Collection<int, JobApplication>
     */
    public function getJobApplications(): Collection
    {
        return $this->jobApplications;
    }

    public function addJobApplication(JobApplication $jobApplication): self
    {
        if (!$this->jobApplications->contains($jobApplication)) {
            $this->jobApplications->add($jobApplication);
            $jobApplication->setApplicant($this);
        }

        return $this;
    }

    public function removeJobApplication(JobApplication $jobApplication): self
    {
        if ($this->jobApplications->removeElement($jobApplication)) {
            // set the owning side to null (unless already changed)
            if ($jobApplication->getApplicant() === $this) {
                $jobApplication->setApplicant(null);
            }
        }

        return $this;
    }

    // Méthodes manquantes ajoutées pour PHPStan

    public function getLocation(): ?string
    {
        return $this->location ?? null;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getExperienceLevel(): ?string
    {
        return $this->experienceLevel;
    }

    public function setExperienceLevel(?string $experienceLevel): static
    {
        $this->experienceLevel = $experienceLevel;

        return $this;
    }

    public function getAvailability(): ?string
    {
        return $this->availability;
    }

    public function setAvailability(?string $availability): static
    {
        $this->availability = $availability;

        return $this;
    }
}
