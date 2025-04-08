<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Recruiter;
use App\Entity\Applicant;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class RecruiterTest extends TestCase
{
    private Recruiter $recruiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recruiter = new Recruiter();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Collection::class, $this->recruiter->getFavoriteApplicants());
        $this->assertCount(0, $this->recruiter->getFavoriteApplicants());
        $this->assertContains('ROLE_RECRUTEUR', $this->recruiter->getRoles());
    }

    public function testCompanyName(): void
    {
        $companyName = "SpeedTech Racing";
        $this->recruiter->setCompanyName($companyName);
        $this->assertEquals($companyName, $this->recruiter->getCompanyName());
    }

    public function testCompanyDescription(): void
    {
        $description = "Une entreprise innovante dans le domaine de la F1";
        $this->recruiter->setCompanyDescription($description);
        $this->assertEquals($description, $this->recruiter->getCompanyDescription());

        // Test avec une valeur null
        $this->recruiter->setCompanyDescription(null);
        $this->assertNull($this->recruiter->getCompanyDescription());
    }

    public function testFavoriteApplicants(): void
    {
        $applicant = new Applicant();
        $applicant->setEmail('applicant@example.com');
        
        // Test d'ajout d'un candidat aux favoris
        $this->recruiter->addFavoriteApplicant($applicant);
        $this->assertTrue($this->recruiter->getFavoriteApplicants()->contains($applicant));
        
        // Test de suppression d'un candidat des favoris
        $this->recruiter->removeFavoriteApplicant($applicant);
        $this->assertFalse($this->recruiter->getFavoriteApplicants()->contains($applicant));
    }

    public function testInheritedProperties(): void
    {
        // Test des propriétés héritées de User
        $email = 'recruiter@example.com';
        $firstName = 'Thomas';
        $lastName = 'Dubois';
        $city = 'Paris';
        $bio = 'Directeur des ressources humaines';
        $jobTitle = 'DRH';

        $this->recruiter->setEmail($email);
        $this->recruiter->setFirstName($firstName);
        $this->recruiter->setLastName($lastName);
        $this->recruiter->setCity($city);
        $this->recruiter->setBio($bio);
        $this->recruiter->setJobTitle($jobTitle);

        $this->assertEquals($email, $this->recruiter->getEmail());
        $this->assertEquals($firstName, $this->recruiter->getFirstName());
        $this->assertEquals($lastName, $this->recruiter->getLastName());
        $this->assertEquals($city, $this->recruiter->getCity());
        $this->assertEquals($bio, $this->recruiter->getBio());
        $this->assertEquals($jobTitle, $this->recruiter->getJobTitle());
    }

    public function testFluentInterface(): void
    {
        $returnedRecruiter = $this->recruiter
            ->setCompanyName('SpeedTech Racing')
            ->setCompanyDescription('Une entreprise innovante')
            ->setEmail('recruiter@example.com')
            ->setFirstName('Thomas')
            ->setLastName('Dubois')
            ->setCity('Paris')
            ->setBio('DRH expérimenté')
            ->setJobTitle('DRH');

        $this->assertSame($this->recruiter, $returnedRecruiter);
    }
} 