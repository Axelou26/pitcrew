<?php

namespace App\Tests\Unit\Entity;

use App\Entity\JobOffer;
use App\Entity\Recruiter;
use App\Entity\JobApplication;
use App\Entity\Interview;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class JobOfferTest extends TestCase
{
    private JobOffer $jobOffer;
    private Recruiter $recruiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jobOffer = new JobOffer();
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
        $title = 'Développeur PHP Senior';
        $description = 'Description du poste';
        $company = 'Entreprise Test';
        $contractType = 'CDI';
        $location = 'Paris';
        $salary = 45000;

        $this->jobOffer
            ->setTitle($title)
            ->setDescription($description)
            ->setCompany($company)
            ->setContractType($contractType)
            ->setLocation($location)
            ->setSalary($salary);

        $this->assertEquals($title, $this->jobOffer->getTitle());
        $this->assertEquals($description, $this->jobOffer->getDescription());
        $this->assertEquals($company, $this->jobOffer->getCompany());
        $this->assertEquals($contractType, $this->jobOffer->getContractType());
        $this->assertEquals($location, $this->jobOffer->getLocation());
        $this->assertEquals($salary, $this->jobOffer->getSalary());
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
        $application = new JobApplication();

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
        $this->assertEquals($skills, $this->jobOffer->getRequiredSkills());
    }

    public function testExpiresAt(): void
    {
        $date = new \DateTime('2024-12-31');
        $this->jobOffer->setExpiresAt($date);
        $this->assertEquals($date, $this->jobOffer->getExpiresAt());

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

        $this->assertEquals($email, $this->jobOffer->getContactEmail());
        $this->assertEquals($phone, $this->jobOffer->getContactPhone());
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
        $interview = new Interview();

        // Test d'ajout
        $this->jobOffer->addInterview($interview);
        $this->assertTrue($this->jobOffer->getInterviews()->contains($interview));
        $this->assertSame($this->jobOffer, $interview->getJobOffer());

        // Test de suppression
        $this->jobOffer->removeInterview($interview);
        $this->assertFalse($this->jobOffer->getInterviews()->contains($interview));
    }

    public function testLogoAndImage(): void
    {
        $logoUrl = "https://example.com/logo.png";
        $this->jobOffer->setLogoUrl($logoUrl);
        $this->assertEquals($logoUrl, $this->jobOffer->getLogoUrl());

        $image = "job-image.jpg";
        $this->jobOffer->setImage($image);
        $this->assertEquals($image, $this->jobOffer->getImage());
    }
}
