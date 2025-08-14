<?php

declare(strict_types = 1);

namespace App\Tests\Unit\Entity;

use App\Entity\Applicant;
use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Entity\Message;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class JobApplicationTest extends TestCase
{
    private JobApplication $jobApplication;
    private User $applicant;
    private JobOffer $jobOffer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jobApplication = new JobApplication();
        $this->applicant      = new User();
        $this->jobOffer       = new JobOffer();

        $this->applicant->setEmail('applicant@example.com');
        $this->jobOffer->setTitle('Test Job Offer');
    }

    public function testBasicInformation(): void
    {
        $coverLetter = 'Lettre de motivation test';
        $this->jobApplication->setCoverLetter($coverLetter);
        $this->assertSame($coverLetter, $this->jobApplication->getCoverLetter());

        $resume = 'cv.pdf';
        $this->jobApplication->setResume($resume);
        $this->assertSame($resume, $this->jobApplication->getResume());
    }

    public function testApplicationAssociations(): void
    {
        $jobApplication = new JobApplication();
        $applicant      = $this->createMock(Applicant::class);
        $jobOffer       = $this->createMock(JobOffer::class);

        $jobApplication->setApplicant($applicant);
        $jobApplication->setJobOffer($jobOffer);

        $this->assertSame($applicant, $jobApplication->getApplicant());
        $this->assertSame($jobOffer, $jobApplication->getJobOffer());
    }

    public function testApplicationStatus(): void
    {
        // Test du statut par défaut
        $this->assertSame('pending', $this->jobApplication->getStatus());

        // Test de changement de statut
        $this->jobApplication->setStatus('accepted');
        $this->assertSame('accepted', $this->jobApplication->getStatus());

        $this->jobApplication->setStatus('rejected');
        $this->assertSame('rejected', $this->jobApplication->getStatus());
    }

    public function testCreatedAt(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->jobApplication->getCreatedAt());
    }

    public function testDocuments(): void
    {
        // Test d'ajout de documents
        $document1 = 'portfolio.pdf';
        $document2 = 'certifications.pdf';

        $this->jobApplication->addDocument($document1);
        $this->jobApplication->addDocument($document2);

        $documents = $this->jobApplication->getDocuments();
        $this->assertCount(2, $documents);
        $this->assertContains($document1, $documents);
        $this->assertContains($document2, $documents);

        // Test de suppression d'un document
        $this->jobApplication->removeDocument($document1);
        $documents = $this->jobApplication->getDocuments();
        $this->assertCount(1, $documents);
        $this->assertNotContains($document1, $documents);
        $this->assertContains($document2, $documents);

        // Test de définition directe des documents
        $newDocuments = ['doc1.pdf', 'doc2.pdf'];
        $this->jobApplication->setDocuments($newDocuments);
        $this->assertSame($newDocuments, $this->jobApplication->getDocuments());
    }

    public function testS3Integration(): void
    {
        // Test des clés S3 pour le CV
        $resumeS3Key = 'resumes/user123/cv.pdf';
        $this->jobApplication->setResumeS3Key($resumeS3Key);
        $this->assertSame($resumeS3Key, $this->jobApplication->getResumeS3Key());

        // Test des URLs pour le CV
        $resumeUrl = 'https://s3.example.com/resumes/user123/cv.pdf';
        $this->jobApplication->setResumeUrl($resumeUrl);
        $this->assertSame($resumeUrl, $this->jobApplication->getResumeUrl());

        // Test des clés S3 pour les documents
        $documentsS3Keys = ['docs/user123/portfolio.pdf', 'docs/user123/certifications.pdf'];
        $this->jobApplication->setDocumentsS3Keys($documentsS3Keys);
        $this->assertSame($documentsS3Keys, $this->jobApplication->getDocumentsS3Keys());

        // Test des URLs pour les documents
        $documentsUrls = [
            'https://s3.example.com/docs/user123/portfolio.pdf',
            'https://s3.example.com/docs/user123/certifications.pdf',
        ];
        $this->jobApplication->setDocumentsUrls($documentsUrls);
        $this->assertSame($documentsUrls, $this->jobApplication->getDocumentsUrls());
    }

    public function testMessagesCollection(): void
    {
        $message = new Message();

        // Test d'ajout d'un message
        $this->jobApplication->addMessage($message);
        $this->assertCount(1, $this->jobApplication->getMessages());
        $this->assertTrue($this->jobApplication->getMessages()->contains($message));

        // Test de suppression d'un message
        $this->jobApplication->removeMessage($message);
        $this->assertCount(0, $this->jobApplication->getMessages());
        $this->assertFalse($this->jobApplication->getMessages()->contains($message));
    }

    public function testConstructor(): void
    {
        $jobApplication = new JobApplication();
        $this->assertInstanceOf(\DateTimeImmutable::class, $jobApplication->getCreatedAt());
        $this->assertSame('pending', $jobApplication->getStatus());
        $this->assertEmpty($jobApplication->getDocuments());
        $this->assertEmpty($jobApplication->getDocumentsS3Keys());
        $this->assertEmpty($jobApplication->getDocumentsUrls());
        $this->assertCount(0, $jobApplication->getMessages());
    }
}
