<?php

declare(strict_types = 1);

namespace App\Tests\Unit\Entity;

use App\Entity\Interview;
use App\Entity\JobOffer;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class InterviewTest extends TestCase
{
    private Interview $interview;
    private JobOffer $jobOffer;
    private User $recruiter;
    private User $applicant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interview = new Interview();
        $this->jobOffer  = new JobOffer();
        $this->recruiter = new User();
        $this->applicant = new User();

        $this->recruiter->setEmail('recruiter@example.com');
        $this->applicant->setEmail('applicant@example.com');
    }

    public function testConstructor(): void
    {
        $interview = new Interview();
        $interview->setScheduledAt(new \DateTimeImmutable());

        $this->assertInstanceOf(\DateTimeInterface::class, $interview->getScheduledAt());
        $this->assertSame('scheduled', $interview->getStatus());
    }

    public function testJobOfferAssociation(): void
    {
        $this->interview->setJobOffer($this->jobOffer);
        $this->assertSame($this->jobOffer, $this->interview->getJobOffer());
    }

    public function testApplicantAssociation(): void
    {
        $interview = new Interview();
        $applicant = $this->createMock(User::class);

        $interview->setApplicant($applicant);
        $this->assertSame($applicant, $interview->getApplicant());
    }

    public function testRecruiterAssociation(): void
    {
        $interview = new Interview();
        $recruiter = $this->createMock(User::class);

        $interview->setRecruiter($recruiter);
        $this->assertSame($recruiter, $interview->getRecruiter());
    }

    public function testTitle(): void
    {
        $title = 'Entretien technique';
        $this->interview->setTitle($title);
        $this->assertSame($title, $this->interview->getTitle());
    }

    public function testScheduledAt(): void
    {
        $scheduledAt = new \DateTimeImmutable('2024-01-01 14:00:00');
        $this->interview->setScheduledAt($scheduledAt);
        $this->assertSame($scheduledAt, $this->interview->getScheduledAt());
    }

    public function testEndedAt(): void
    {
        $endedAt = new \DateTimeImmutable('2024-01-01 15:00:00');
        $this->interview->setEndedAt($endedAt);
        $this->assertSame($endedAt, $this->interview->getEndedAt());

        // Test avec une valeur null
        $this->interview->setEndedAt(null);
        $this->assertNull($this->interview->getEndedAt());
    }

    public function testRoomId(): void
    {
        $roomId = 'room-123';
        $this->interview->setRoomId($roomId);
        $this->assertSame($roomId, $this->interview->getRoomId());

        // Test avec une valeur null
        $this->interview->setRoomId(null);
        $this->assertNull($this->interview->getRoomId());
    }

    public function testNotes(): void
    {
        $notes = "Notes sur l'entretien";
        $this->interview->setNotes($notes);
        $this->assertSame($notes, $this->interview->getNotes());

        // Test avec une valeur null
        $this->interview->setNotes(null);
        $this->assertNull($this->interview->getNotes());
    }

    public function testStatus(): void
    {
        // Test des différents statuts
        $this->interview->setStatus('completed');
        $this->assertSame('completed', $this->interview->getStatus());
        $this->assertTrue($this->interview->isCompleted());
        $this->assertFalse($this->interview->isScheduled());
        $this->assertFalse($this->interview->isCancelled());
        $this->assertFalse($this->interview->isActive());

        $this->interview->setStatus('scheduled');
        $this->assertSame('scheduled', $this->interview->getStatus());
        $this->assertTrue($this->interview->isScheduled());
        $this->assertFalse($this->interview->isCompleted());
        $this->assertFalse($this->interview->isCancelled());
        $this->assertFalse($this->interview->isActive());

        $this->interview->setStatus('cancelled');
        $this->assertSame('cancelled', $this->interview->getStatus());
        $this->assertTrue($this->interview->isCancelled());
        $this->assertFalse($this->interview->isScheduled());
        $this->assertFalse($this->interview->isCompleted());
        $this->assertFalse($this->interview->isActive());

        $this->interview->setStatus('active');
        $this->assertSame('active', $this->interview->getStatus());
        $this->assertTrue($this->interview->isActive());
        $this->assertFalse($this->interview->isScheduled());
        $this->assertFalse($this->interview->isCompleted());
        $this->assertFalse($this->interview->isCancelled());
    }

    public function testMeetingUrl(): void
    {
        $url = 'https://meet.example.com/interview-123';
        $this->interview->setMeetingUrl($url);
        $this->assertSame($url, $this->interview->getMeetingUrl());

        // Test avec une valeur null
        $this->interview->setMeetingUrl(null);
        $this->assertNull($this->interview->getMeetingUrl());
    }

    public function testFluentInterface(): void
    {
        $interview   = new Interview();
        $applicant   = $this->createMock(User::class);
        $recruiter   = $this->createMock(User::class);
        $jobOffer    = $this->createMock(JobOffer::class);
        $scheduledAt = new \DateTimeImmutable();

        $result = $interview
            ->setApplicant($applicant)
            ->setRecruiter($recruiter)
            ->setJobOffer($jobOffer)
            ->setTitle('Entretien test')
            ->setScheduledAt($scheduledAt)
            ->setStatus('active');

        $this->assertSame($interview, $result);
    }
}
