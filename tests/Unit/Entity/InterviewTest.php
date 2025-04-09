<?php

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
        $this->jobOffer = new JobOffer();
        $this->recruiter = new User();
        $this->applicant = new User();

        $this->recruiter->setEmail('recruiter@example.com');
        $this->applicant->setEmail('applicant@example.com');
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\DateTime::class, $this->interview->getScheduledAt());
        $this->assertEquals('scheduled', $this->interview->getStatus());
        $this->assertNull($this->interview->getNotes());
    }

    public function testJobOfferAssociation(): void
    {
        $this->interview->setJobOffer($this->jobOffer);
        $this->assertSame($this->jobOffer, $this->interview->getJobOffer());
    }

    public function testApplicantAssociation(): void
    {
        $this->interview->setApplicant($this->applicant);
        $this->assertSame($this->applicant, $this->interview->getApplicant());
    }

    public function testRecruiterAssociation(): void
    {
        $this->interview->setRecruiter($this->recruiter);
        $this->assertSame($this->recruiter, $this->interview->getRecruiter());
    }

    public function testTitle(): void
    {
        $title = "Entretien technique";
        $this->interview->setTitle($title);
        $this->assertEquals($title, $this->interview->getTitle());
    }

    public function testScheduledAt(): void
    {
        $scheduledAt = new \DateTime('2024-01-01 14:00:00');
        $this->interview->setScheduledAt($scheduledAt);
        $this->assertEquals($scheduledAt, $this->interview->getScheduledAt());
    }

    public function testEndedAt(): void
    {
        $endedAt = new \DateTime('2024-01-01 15:00:00');
        $this->interview->setEndedAt($endedAt);
        $this->assertEquals($endedAt, $this->interview->getEndedAt());

        // Test avec une valeur null
        $this->interview->setEndedAt(null);
        $this->assertNull($this->interview->getEndedAt());
    }

    public function testRoomId(): void
    {
        $roomId = "room-123";
        $this->interview->setRoomId($roomId);
        $this->assertEquals($roomId, $this->interview->getRoomId());

        // Test avec une valeur null
        $this->interview->setRoomId(null);
        $this->assertNull($this->interview->getRoomId());
    }

    public function testNotes(): void
    {
        $notes = "Notes sur l'entretien";
        $this->interview->setNotes($notes);
        $this->assertEquals($notes, $this->interview->getNotes());

        // Test avec une valeur null
        $this->interview->setNotes(null);
        $this->assertNull($this->interview->getNotes());
    }

    public function testStatus(): void
    {
        // Test des différents statuts
        $this->interview->setStatus('completed');
        $this->assertEquals('completed', $this->interview->getStatus());
        $this->assertTrue($this->interview->isCompleted());
        $this->assertFalse($this->interview->isScheduled());
        $this->assertFalse($this->interview->isCancelled());
        $this->assertFalse($this->interview->isActive());

        $this->interview->setStatus('scheduled');
        $this->assertEquals('scheduled', $this->interview->getStatus());
        $this->assertTrue($this->interview->isScheduled());
        $this->assertFalse($this->interview->isCompleted());
        $this->assertFalse($this->interview->isCancelled());
        $this->assertFalse($this->interview->isActive());

        $this->interview->setStatus('cancelled');
        $this->assertEquals('cancelled', $this->interview->getStatus());
        $this->assertTrue($this->interview->isCancelled());
        $this->assertFalse($this->interview->isScheduled());
        $this->assertFalse($this->interview->isCompleted());
        $this->assertFalse($this->interview->isActive());

        $this->interview->setStatus('active');
        $this->assertEquals('active', $this->interview->getStatus());
        $this->assertTrue($this->interview->isActive());
        $this->assertFalse($this->interview->isScheduled());
        $this->assertFalse($this->interview->isCompleted());
        $this->assertFalse($this->interview->isCancelled());
    }

    public function testMeetingUrl(): void
    {
        $url = "https://meet.example.com/interview-123";
        $this->interview->setMeetingUrl($url);
        $this->assertEquals($url, $this->interview->getMeetingUrl());

        // Test avec une valeur null
        $this->interview->setMeetingUrl(null);
        $this->assertNull($this->interview->getMeetingUrl());
    }

    public function testFluentInterface(): void
    {
        $returnedInterview = $this->interview
            ->setJobOffer($this->jobOffer)
            ->setApplicant($this->applicant)
            ->setRecruiter($this->recruiter)
            ->setTitle("Entretien technique")
            ->setScheduledAt(new \DateTime())
            ->setEndedAt(new \DateTime())
            ->setRoomId("room-123")
            ->setNotes("Notes")
            ->setStatus("scheduled")
            ->setMeetingUrl("https://meet.example.com");

        $this->assertSame($this->interview, $returnedInterview);
    }
}
