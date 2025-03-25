<?php

namespace App\Entity;

use App\Repository\JobApplicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: JobApplicationRepository::class)]
class JobApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $applicant = null;

    #[ORM\ManyToOne(inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?JobOffer $jobOffer = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La lettre de motivation est obligatoire')]
    private ?string $coverLetter = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le CV est obligatoire')]
    #[Assert\NotNull(message: 'Le CV est obligatoire')]
    private ?string $resume = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'pending';

    #[ORM\Column(type: Types::JSON)]
    private array $documents = [];

    #[ORM\Column(nullable: true)]
    private ?string $resume_s3_key = null;

    #[ORM\Column(nullable: true)]
    private ?string $resume_url = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $documents_s3_keys = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $documents_urls = null;

    #[ORM\OneToMany(mappedBy: 'jobApplication', targetEntity: Message::class, orphanRemoval: true)]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->documents = [];
        $this->documents_s3_keys = [];
        $this->documents_urls = [];
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicant(): ?User
    {
        return $this->applicant;
    }

    public function setApplicant(?User $applicant): static
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

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function setDocuments(array $documents): static
    {
        $this->documents = $documents;
        return $this;
    }

    public function addDocument(string $document): static
    {
        if (!in_array($document, $this->documents)) {
            $this->documents[] = $document;
        }
        return $this;
    }

    public function removeDocument(string $document): static
    {
        $key = array_search($document, $this->documents);
        if ($key !== false) {
            unset($this->documents[$key]);
            $this->documents = array_values($this->documents);
        }
        return $this;
    }

    public function getResumeS3Key(): ?string
    {
        return $this->resume_s3_key;
    }

    public function setResumeS3Key(?string $resume_s3_key): static
    {
        $this->resume_s3_key = $resume_s3_key;
        return $this;
    }

    public function getResumeUrl(): ?string
    {
        return $this->resume_url;
    }

    public function setResumeUrl(?string $resume_url): static
    {
        $this->resume_url = $resume_url;
        return $this;
    }

    public function getDocumentsS3Keys(): ?array
    {
        return $this->documents_s3_keys;
    }

    public function setDocumentsS3Keys(?array $documents_s3_keys): static
    {
        $this->documents_s3_keys = $documents_s3_keys;
        return $this;
    }

    public function getDocumentsUrls(): ?array
    {
        return $this->documents_urls;
    }

    public function setDocumentsUrls(?array $documents_urls): static
    {
        $this->documents_urls = $documents_urls;
        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setJobApplication($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getJobApplication() === $this) {
                $message->setJobApplication(null);
            }
        }

        return $this;
    }
} 