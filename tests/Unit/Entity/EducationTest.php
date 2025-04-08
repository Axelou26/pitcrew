<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Education;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EducationTest extends TestCase
{
    private Education $education;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->education = new Education();
        $this->user = new User();
        $this->user->setEmail('user@example.com');
    }

    public function testBasicInformation(): void
    {
        $degree = 'Master en Informatique';
        $institution = 'Université Test';
        $location = 'Paris';
        $startDate = '09/2021';
        $endDate = '06/2023';
        $description = 'Description de la formation';

        $this->education
            ->setDegree($degree)
            ->setInstitution($institution)
            ->setLocation($location)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setDescription($description);

        $this->assertEquals($degree, $this->education->getDegree());
        $this->assertEquals($institution, $this->education->getInstitution());
        $this->assertEquals($location, $this->education->getLocation());
        $this->assertEquals($startDate, $this->education->getStartDate());
        $this->assertEquals($endDate, $this->education->getEndDate());
        $this->assertEquals($description, $this->education->getDescription());
    }

    public function testUserAssociation(): void
    {
        $this->education->setUser($this->user);
        $this->assertSame($this->user, $this->education->getUser());
    }

    public function testFluentInterface(): void
    {
        $returnedEducation = $this->education
            ->setDegree('Master')
            ->setInstitution('Université')
            ->setLocation('Paris')
            ->setStartDate('09/2021')
            ->setEndDate('06/2023')
            ->setDescription('Description')
            ->setUser($this->user);

        $this->assertSame($this->education, $returnedEducation);
    }
} 