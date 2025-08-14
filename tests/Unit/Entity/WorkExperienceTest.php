<?php

declare(strict_types = 1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\WorkExperience;
use PHPUnit\Framework\TestCase;

class WorkExperienceTest extends TestCase
{
    private WorkExperience $workExperience;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workExperience = new WorkExperience();
        $this->user           = new User();
        $this->user->setEmail('user@example.com');
    }

    public function testBasicInformation(): void
    {
        $title       = 'Développeur Full Stack';
        $company     = 'Entreprise Test';
        $location    = 'Paris';
        $startDate   = '09/2021';
        $endDate     = '06/2023';
        $description = 'Description du poste';

        $this->workExperience
            ->setTitle($title)
            ->setCompany($company)
            ->setLocation($location)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setDescription($description);

        $this->assertSame($title, $this->workExperience->getTitle());
        $this->assertSame($company, $this->workExperience->getCompany());
        $this->assertSame($location, $this->workExperience->getLocation());
        $this->assertSame($startDate, $this->workExperience->getStartDate());
        $this->assertSame($endDate, $this->workExperience->getEndDate());
        $this->assertSame($description, $this->workExperience->getDescription());
    }

    public function testUserAssociation(): void
    {
        $this->workExperience->setUser($this->user);
        $this->assertSame($this->user, $this->workExperience->getUser());
    }

    public function testFluentInterface(): void
    {
        $returnedWorkExperience = $this->workExperience
            ->setTitle('Développeur')
            ->setCompany('Entreprise')
            ->setLocation('Paris')
            ->setStartDate('09/2021')
            ->setEndDate('06/2023')
            ->setDescription('Description')
            ->setUser($this->user);

        $this->assertSame($this->workExperience, $returnedWorkExperience);
    }
}
