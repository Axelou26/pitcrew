<?php

declare(strict_types = 1);

namespace App\Tests\Unit\Entity;

use App\Entity\Application;
use App\Entity\Interview;
use App\Entity\JobOffer;
use App\Entity\Recruiter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class JobOfferTest extends TestCase
{
    private JobOffer $jobOffer;
    private Recruiter $recruiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jobOffer  = new JobOffer();
        $this->recruiter = new Recruiter();

        $this->recruiter->setEmail('recruiter@example.com');
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->jobOffer->getCreatedAt());
        $this->assertInstanceOf(Collection::class, $this->jobOffer->getApplications());
        $this->assertCount(0, $this->jobOffer->getApplications());
        $this->assertFalse($this->jobOffer->getIsRemote());
        $this->assertFalse($this->jobOffer->getIsPromoted());
    }

    public function testBasicInformation(): void
    {
        $title        = 'Développeur PHP Senior';
        $description  = 'Description du poste';
        $company      = 'Entreprise Test';
        $contractType = 'CDI';
        $location     = 'Paris';
        $salary       = 45000;

        $this->jobOffer
            ->setTitle($title)
            ->setDescription($description)
            ->setCompany($company)
            ->setContractType($contractType)
            ->setLocation($location)
            ->setSalary($salary);

        $this->assertSame($title, $this->jobOffer->getTitle());
        $this->assertSame($description, $this->jobOffer->getDescription());
        $this->assertSame($company, $this->jobOffer->getCompany());
        $this->assertSame($contractType, $this->jobOffer->getContractType());
        $this->assertSame($location, $this->jobOffer->getLocation());
        $this->assertSame($salary, $this->jobOffer->getSalary());
    }

    public function testRecruiterAssociation(): void
    {
        $this->jobOffer->setRecruiter($this->recruiter);
        $this->assertSame($this->recruiter, $this->jobOffer->getRecruiter());

        // Test avec une valeur null
        $this->jobOffer->setRecruiter(null);
        $this->assertNull($this->jobOffer->getRecruiter());
    }

    public function testApplications(): void
    {
        $application = new Application();

        // Test d'ajout d'une candidature
        $this->jobOffer->addApplication($application);
        $this->assertTrue($this->jobOffer->getApplications()->contains($application));
        $this->assertSame($this->jobOffer, $application->getJobOffer());

        // Test de suppression d'une candidature
        $this->jobOffer->removeApplication($application);
        $this->assertFalse($this->jobOffer->getApplications()->contains($application));
    }

    public function testRequiredSkills(): void
    {
        $skills = ['PHP', 'Symfony', 'MySQL'];
        $this->jobOffer->setRequiredSkills($skills);
        $this->assertSame($skills, $this->jobOffer->getRequiredSkills());
    }

    public function testExpiresAt(): void
    {
        $date = new \DateTimeImmutable('2024-12-31');
        $this->jobOffer->setExpiresAt($date);
        $this->assertSame($date, $this->jobOffer->getExpiresAt());

        // Test avec une valeur null
        $this->jobOffer->setExpiresAt(null);
        $this->assertNull($this->jobOffer->getExpiresAt());
    }

    public function testIsActive(): void
    {
        $this->assertTrue($this->jobOffer->getIsActive());

        $this->jobOffer->setIsActive(false);
        $this->assertFalse($this->jobOffer->getIsActive());
    }

    public function testRemoteAndPromoted(): void
    {
        $this->assertFalse($this->jobOffer->getIsRemote());
        $this->assertFalse($this->jobOffer->getIsPromoted());

        $this->jobOffer->setIsRemote(true);
        $this->jobOffer->setIsPromoted(true);

        $this->assertTrue($this->jobOffer->getIsRemote());
        $this->assertTrue($this->jobOffer->getIsPromoted());
    }

    public function testContactInformation(): void
    {
        $email = 'contact@example.com';
        $phone = '0123456789';

        $this->jobOffer->setContactEmail($email);
        $this->jobOffer->setContactPhone($phone);

        $this->assertSame($email, $this->jobOffer->getContactEmail());
        $this->assertSame($phone, $this->jobOffer->getContactPhone());
    }

    public function testFluentInterface(): void
    {
        $returnedJobOffer = $this->jobOffer
            ->setTitle('Test Title')
            ->setDescription('Test Description')
            ->setCompany('Test Company')
            ->setContractType('CDI')
            ->setLocation('Paris')
            ->setSalary(45000)
            ->setIsActive(true)
            ->setIsRemote(false)
            ->setIsPromoted(false)
            ->setContactEmail('contact@example.com')
            ->setContactPhone('0123456789');

        $this->assertSame($this->jobOffer, $returnedJobOffer);
    }

    public function testInterviews(): void
    {
        $jobOffer = new JobOffer();

        // Créer un mock pour Interview avec setJobOffer qui retourne self
        $interview = $this->createMock(Interview::class);
        $interview->expects($this->once())
            ->method('setJobOffer')
            ->with($jobOffer)
            ->willReturnSelf();

        // Utiliser la réflexion pour accéder à la collection privée
        $reflection = new \ReflectionClass(JobOffer::class);
        $property   = $reflection->getProperty('interviews');
        $property->setAccessible(true);
        $collection = new ArrayCollection();
        $property->setValue($jobOffer, $collection);

        // Tester l'ajout
        $jobOffer->addInterview($interview);
        $this->assertTrue($collection->contains($interview));

        // Tester la suppression
        $jobOffer->removeInterview($interview);
        $this->assertFalse($collection->contains($interview));
    }

    public function testLogoAndImage(): void
    {
        $logoUrl = 'https://example.com/logo.png';
        $this->jobOffer->setLogoUrl($logoUrl);
        $this->assertSame($logoUrl, $this->jobOffer->getLogoUrl());

        $image = 'job-image.jpg';
        $this->jobOffer->setImage($image);
        $this->assertSame($image, $this->jobOffer->getImage());
    }
}
