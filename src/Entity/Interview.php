<?php

namespace App\Entity;

use App\Repository\InterviewRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InterviewRepository::class)]
class Interview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'interviewsAsRecruiter')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $recruiter = null;

    #[ORM\ManyToOne(inversedBy: 'interviewsAsApplicant')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $applicant = null;

    #[ORM\ManyToOne(inversedBy: 'interviews')]
    private ?JobOffer $jobOffer = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $scheduledAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $roomId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'scheduled';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $meetingUrl = null;

    public function __construct()
    {
        $this->scheduledAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecruiter(): ?User
    {
        return $this->recruiter;
    }

    public function setRecruiter(?User $recruiter): self
    {
        $this->recruiter = $recruiter;

        return $this;
    }

    public function getApplicant(): ?User
    {
        return $this->applicant;
    }

    public function setApplicant(?User $applicant): self
    {
        $this->applicant = $applicant;

        return $this;
    }

    public function getJobOffer(): ?JobOffer
    {
        return $this->jobOffer;
    }

    public function setJobOffer(?JobOffer $jobOffer): self
    {
        $this->jobOffer = $jobOffer;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeInterface $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeInterface
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeInterface $endedAt): self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getRoomId(): ?string
    {
        return $this->roomId;
    }

    public function setRoomId(?string $roomId): self
    {
        $this->roomId = $roomId;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMeetingUrl(): ?string
    {
        return $this->meetingUrl;
    }

    public function setMeetingUrl(?string $meetingUrl): self
    {
        $this->meetingUrl = $meetingUrl;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
} 