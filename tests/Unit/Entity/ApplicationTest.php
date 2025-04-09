<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Application;
use App\Entity\Applicant;
use App\Entity\JobOffer;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    private Application $application;
    private Applicant $applicant;
    private JobOffer $jobOffer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->application = new Application();
        $this->applicant = new Applicant();
        $this->jobOffer = new JobOffer();

        $this->applicant->setEmail('applicant@example.com');
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->application->getCreatedAt());
        $this->assertEquals('pending', $this->application->getStatus());
    }

    public function testApplicantAssociation(): void
    {
        $this->application->setApplicant($this->applicant);
        $this->assertSame($this->applicant, $this->application->getApplicant());
    }

    public function testJobOfferAssociation(): void
    {
        $this->application->setJobOffer($this->jobOffer);
        $this->assertSame($this->jobOffer, $this->application->getJobOffer());
    }

    public function testCoverLetter(): void
    {
        $coverLetter = "Lettre de motivation détaillée";
        $this->application->setCoverLetter($coverLetter);
        $this->assertEquals($coverLetter, $this->application->getCoverLetter());

        // Test avec une valeur null
        $this->application->setCoverLetter(null);
        $this->assertNull($this->application->getCoverLetter());
    }

    public function testCvFilename(): void
    {
        $filename = "cv_john_doe.pdf";
        $this->application->setCvFilename($filename);
        $this->assertEquals($filename, $this->application->getCvFilename());

        // Test avec une valeur null
        $this->application->setCvFilename(null);
        $this->assertNull($this->application->getCvFilename());
    }

    public function testRecommendationLetterFilename(): void
    {
        $filename = "recommendation.pdf";
        $this->application->setRecommendationLetterFilename($filename);
        $this->assertEquals($filename, $this->application->getRecommendationLetterFilename());

        // Test avec une valeur null
        $this->application->setRecommendationLetterFilename(null);
        $this->assertNull($this->application->getRecommendationLetterFilename());
    }

    public function testStatus(): void
    {
        // Test des statuts valides
        $validStatuses = ['pending', 'accepted', 'rejected'];

        foreach ($validStatuses as $status) {
            $this->application->setStatus($status);
            $this->assertEquals($status, $this->application->getStatus());
        }

        // Test avec un statut invalide
        $this->expectException(\InvalidArgumentException::class);
        $this->application->setStatus('invalid_status');
    }

    public function testCreatedAt(): void
    {
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->application->setCreatedAt($date);
        $this->assertEquals($date, $this->application->getCreatedAt());
    }

    public function testFluentInterface(): void
    {
        $returnedApplication = $this->application
            ->setApplicant($this->applicant)
            ->setJobOffer($this->jobOffer)
            ->setCoverLetter("Lettre de motivation")
            ->setCvFilename("cv.pdf")
            ->setRecommendationLetterFilename("recommendation.pdf")
            ->setStatus("pending")
            ->setCreatedAt(new \DateTimeImmutable());

        $this->assertSame($this->application, $returnedApplication);
    }
}
