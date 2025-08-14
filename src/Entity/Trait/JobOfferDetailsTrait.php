<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait JobOfferDetailsTrait
{
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column(name: 'contract_type', length: 50)]
    #[Assert\NotBlank(message: 'Le type de contrat est obligatoire')]
    private ?string $contractType = null;

    #[ORM\Column(nullable: true)]
    private ?int $salary = null;

    #[ORM\Column(type: 'json')]
    private array $requiredSkills = [];

    #[ORM\Column(type: 'json')]
    private array $softSkills = [];

    #[ORM\Column(name: 'expires_at', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(name: 'is_remote')]
    private ?bool $isRemote = false;

    #[ORM\Column(name: 'is_promoted')]
    private ?bool $isPromoted = false;

    #[ORM\Column(name: 'experience_level', length: 50)]
    private ?string $experienceLevel = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'entreprise est obligatoire')]
    private ?string $company = null;

    #[ORM\Column(name: 'is_active')]
    private ?bool $isActive = true;

    #[ORM\Column(name: 'is_published')]
    private ?bool $isPublished = false;

    #[ORM\Column(name: 'required_experience', nullable: true)]
    private ?int $requiredExperience = null;

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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

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

    public function getSalary(): ?int
    {
        return $this->salary;
    }

    public function setSalary(?int $salary): static
    {
        $this->salary = $salary;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getRequiredSkills(): array
    {
        return $this->requiredSkills;
    }

    /**
     * @param array<int, string> $requiredSkills
     */
    public function setRequiredSkills(array $requiredSkills): self
    {
        $this->requiredSkills = $requiredSkills;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getSoftSkills(): array
    {
        return $this->softSkills;
    }

    /**
     * @param array<int, string> $softSkills
     */
    public function setSoftSkills(array $softSkills): self
    {
        $this->softSkills = $softSkills;

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

    public function getExperienceLevel(): ?string
    {
        return $this->experienceLevel;
    }

    public function setExperienceLevel(string $experienceLevel): static
    {
        $this->experienceLevel = $experienceLevel;

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

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getRequiredExperience(): ?int
    {
        return $this->requiredExperience;
    }

    public function setRequiredExperience(?int $requiredExperience): static
    {
        $this->requiredExperience = $requiredExperience;

        return $this;
    }
}
