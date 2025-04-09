<?php

namespace App\Form\DTO;

use DateTimeInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class JobOfferDTO
{
    private ?string $title = null;
    private ?string $description = null;
    private ?string $company = null;
    private ?UploadedFile $logoFile = null;
    private ?UploadedFile $imageFile = null;
    private ?string $contractType = null;
    private ?string $location = null;
    private bool $isRemote = false;
    private ?float $salary = null;
    private array $requiredSkills = [];
    private ?DateTimeInterface $expiresAt = null;
    private ?string $contactEmail = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function getLogoFile(): ?UploadedFile
    {
        return $this->logoFile;
    }

    public function setLogoFile(?UploadedFile $logoFile): self
    {
        $this->logoFile = $logoFile;
        return $this;
    }

    public function getImageFile(): ?UploadedFile
    {
        return $this->imageFile;
    }

    public function setImageFile(?UploadedFile $imageFile): self
    {
        $this->imageFile = $imageFile;
        return $this;
    }

    public function getContractType(): ?string
    {
        return $this->contractType;
    }

    public function setContractType(?string $contractType): self
    {
        $this->contractType = $contractType;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function isRemote(): bool
    {
        return $this->isRemote;
    }

    public function setIsRemote(bool $isRemote): self
    {
        $this->isRemote = $isRemote;
        return $this;
    }

    public function getSalary(): ?float
    {
        return $this->salary;
    }

    public function setSalary(?float $salary): self
    {
        $this->salary = $salary;
        return $this;
    }

    public function getRequiredSkills(): array
    {
        return $this->requiredSkills;
    }

    public function setRequiredSkills(array $requiredSkills): self
    {
        $this->requiredSkills = $requiredSkills;
        return $this;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }
} 