<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApplicationRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Applicant $applicant = null;

    #[ORM\ManyToOne(inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?JobOffer $jobOffer = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $coverLetter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvFilename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recLetterFilename = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->status    = 'pending';
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicant(): ?Applicant
    {
        return $this->applicant;
    }

    public function setApplicant(?Applicant $applicant): static
    {
        $this->applicant = $applicant;

        return $this;
    }

    public function getJobOffer(): ?JobOffer
    {
        return $this->jobOffer;
    }

    public function setJobOffer(?JobOffer $jobOffer): static
    {
        $this->jobOffer = $jobOffer;

        return $this;
    }

    public function getCoverLetter(): ?string
    {
        return $this->coverLetter;
    }

    public function setCoverLetter(?string $coverLetter): static
    {
        $this->coverLetter = $coverLetter;

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

    public function getRecLetterFilename(): ?string
    {
        return $this->recLetterFilename;
    }

    public function setRecLetterFilename(?string $recLetterFilename): static
    {
        $this->recLetterFilename = $recLetterFilename;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, ['pending', 'accepted', 'rejected'], true)) {
            throw new InvalidArgumentException('Invalid status');
        }
        $this->status = $status;

        return $this;
    }

    // Méthodes manquantes ajoutées pour PHPStan

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;

        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume ?? null;
    }
}
