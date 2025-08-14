<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\JobApplicationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobApplicationRepository::class)]
class JobApplication
{
    /**
     * Identifiant de la candidature.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * Statut de la candidature.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $status = 'pending';

    /**
     * Candidat qui a postulé.
     */
    #[ORM\ManyToOne(targetEntity: Applicant::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Applicant $applicant = null;

    #[ORM\ManyToOne(targetEntity: JobOffer::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?JobOffer $jobOffer = null;

    /**
     * Lettre de motivation.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $coverLetter = null;

    /**
     * CV du candidat.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $resume = null;

    /**
     * Date de création de la candidature.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * Documents supplémentaires.
     *
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json')]
    private array $documents = [];

    #[ORM\Column(nullable: true)]
    private ?string $resumeS3Key = null;

    #[ORM\Column(nullable: true)]
    private ?string $resumeUrl = null;

    /**
     * Clés S3 des documents.
     *
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $documentsS3Keys = null;

    /**
     * URLs des documents.
     *
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $documentsUrls = null;

    /**
     * Messages associés à la candidature.
     *
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'jobApplication', orphanRemoval: true)]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt       = new DateTimeImmutable();
        $this->documents       = [];
        $this->documentsS3Keys = [];
        $this->documentsUrls   = [];
        $this->messages        = new ArrayCollection();
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

    public function getResume(): ?string
    {
        return $this->resume ?? null;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
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

    public function addDocument(string $document): static
    {
        if (!in_array($document, $this->documents, true)) {
            $this->documents[] = $document;
        }

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

    public function removeDocument(string $document): static
    {
        $key = array_search($document, $this->documents, true);
        if ($key !== false) {
            unset($this->documents[$key]);
            $this->documents = array_values($this->documents);
        }

        return $this;
    }

    public function getResumeS3Key(): ?string
    {
        return $this->resumeS3Key;
    }

    public function setResumeS3Key(?string $resumeS3Key): static
    {
        $this->resumeS3Key = $resumeS3Key;

        return $this;
    }

    public function getResumeUrl(): ?string
    {
        return $this->resumeUrl;
    }

    public function setResumeUrl(?string $resumeUrl): static
    {
        $this->resumeUrl = $resumeUrl;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getDocumentsS3Keys(): array
    {
        return $this->documentsS3Keys ?? [];
    }

    /**
     * @param array<int, string> $documentsS3Keys
     */
    public function setDocumentsS3Keys(?array $documentsS3Keys): static
    {
        $this->documentsS3Keys = $documentsS3Keys;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getDocumentsUrls(): array
    {
        return $this->documentsUrls ?? [];
    }

    /**
     * @param array<int, string> $documentsUrls
     */
    public function setDocumentsUrls(array $documentsUrls): self
    {
        $this->documentsUrls = $documentsUrls;

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
