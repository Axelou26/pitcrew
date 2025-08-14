<?php

declare(strict_types=1);

namespace App\Form\DTO;

class InterviewDTO
{
    private ?string $title                   = null;
    private ?\DateTimeInterface $scheduledAt = null;
    private ?string $notes                   = null;
    private ?int $applicantId                = null;
    private ?int $jobOfferId                 = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeInterface $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

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

    public function getApplicantId(): ?int
    {
        return $this->applicantId;
    }

    public function setApplicantId(?int $applicantId): self
    {
        $this->applicantId = $applicantId;

        return $this;
    }

    public function getJobOfferId(): ?int
    {
        return $this->jobOfferId;
    }

    public function setJobOfferId(?int $jobOfferId): self
    {
        $this->jobOfferId = $jobOfferId;

        return $this;
    }
}
