<?php

namespace App\Entity;

use App\Repository\ApplicantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantRepository::class)]
class Applicant extends User
{
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $technicalSkills = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $softSkills = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvFilename = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $educationHistory = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $workExperience = null;

    #[ORM\ManyToMany(targetEntity: JobOffer::class)]
    private Collection $favoriteOffers;

    public function __construct()
    {
        parent::__construct();
        $this->favoriteOffers = new ArrayCollection();
        $this->setRoles(['ROLE_APPLICANT']);
        $this->educationHistory = [];
        $this->workExperience = [];
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

    public function getTechnicalSkills(): ?array
    {
        return $this->technicalSkills;
    }

    public function setTechnicalSkills(?array $technicalSkills): static
    {
        $this->technicalSkills = $technicalSkills;
        return $this;
    }

    public function getSoftSkills(): ?array
    {
        return $this->softSkills;
    }

    public function setSoftSkills(?array $softSkills): static
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

    public function getEducationHistory(): ?array
    {
        return $this->educationHistory;
    }

    public function setEducationHistory(?array $educationHistory): static
    {
        $this->educationHistory = $educationHistory;

        return $this;
    }

    public function getWorkExperience(): ?array
    {
        return $this->workExperience;
    }

    public function setWorkExperience(?array $workExperience): static
    {
        $this->workExperience = $workExperience;

        return $this;
    }

    /**
     * @return Collection<int, JobOffer>
     */
    public function getFavoriteOffers(): Collection
    {
        return $this->favoriteOffers;
    }

    public function addFavoriteOffer(JobOffer $jobOffer): static
    {
        if (!$this->favoriteOffers->contains($jobOffer)) {
            $this->favoriteOffers->add($jobOffer);
        }
        return $this;
    }

    public function removeFavoriteOffer(JobOffer $jobOffer): static
    {
        $this->favoriteOffers->removeElement($jobOffer);
        return $this;
    }
} 