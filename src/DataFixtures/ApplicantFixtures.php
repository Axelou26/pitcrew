<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Applicant;
use App\Entity\Education;
use App\Entity\WorkExperience;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use DateTime;

class ApplicantFixtures extends Fixture
{
    private const PASSWORD = 'password';
    public const APPLICANT_REFERENCE_PREFIX = 'applicant_';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getApplicantData() as $index => $data) {
            $applicant = new Applicant();
            $applicant->setEmail($data['email'])
                ->setFirstName($data['firstName'])
                ->setLastName($data['lastName'])
                ->setPassword($this->passwordHasher->hashPassword($applicant, self::PASSWORD))
                ->setJobTitle($data['jobTitle'])
                ->setDescription($data['description'])
                ->setTechnicalSkills($data['technicalSkills'])
                ->setSoftSkills($data['softSkills'])
                ->setCity($data['city'])
                ->setBio($data['bio']);

            $this->addWorkExperience($applicant, $data['experience']);
            $this->addEducation($applicant, $data['education']);

            $manager->persist($applicant);
            $this->addReference(self::APPLICANT_REFERENCE_PREFIX . $index, $applicant);
        }

        $manager->flush();
    }

    private function addWorkExperience(Applicant $applicant, string $experienceText): void
    {
        $experiences = explode("\n", $experienceText);
        foreach ($experiences as $exp) {
            if (preg_match('/(\d{4})-(\d{4})\s*:\s*(.+)/', $exp, $matches)) {
                $experience = new WorkExperience();
                $experience->setStartDate(new DateTime($matches[1] . '-01-01'))
                    ->setEndDate(new DateTime($matches[2] . '-12-31'))
                    ->setTitle($matches[3])
                    ->setApplicant($applicant);

                $applicant->addWorkExperience($experience);
            }
        }
    }

    private function addEducation(Applicant $applicant, string $educationText): void
    {
        $educations = explode("\n", $educationText);
        foreach ($educations as $edu) {
            if (preg_match('/(\d{4})-(\d{4})\s*:\s*(.+)/', $edu, $matches)) {
                $education = new Education();
                $education->setStartDate(new DateTime($matches[1] . '-01-01'))
                    ->setEndDate(new DateTime($matches[2] . '-12-31'))
                    ->setDegree($matches[3])
                    ->setApplicant($applicant);

                $applicant->addEducation($education);
            }
        }
    }

    private function getApplicantData(): array
    {
        return [
            [
                'email' => 'candidat1@example.com',
                'firstName' => 'Pierre',
                'lastName' => 'Durand',
                'jobTitle' => 'Développeur Full Stack',
                'description' => 'Développeur passionné avec 5 ans d\'expérience',
                'technicalSkills' => ['PHP', 'Symfony', 'JavaScript', 'React'],
                'softSkills' => ['Communication', 'Travail d\'équipe', 'Autonomie'],
                'city' => 'Paris',
                'bio' => 'Passionné par le développement web',
                'experience' => "2018-2023 : Développeur Full Stack chez Tech Corp\n2016-2018 : Développeur PHP chez Web Agency",
                'education' => "2014-2016 : Master en Informatique\n2011-2014 : Licence en Informatique"
            ],
            [
                'email' => 'candidat2@example.com',
                'firstName' => 'Sophie',
                'lastName' => 'Dubois',
                'jobTitle' => 'UX Designer',
                'description' => 'Designer créative avec 3 ans d\'expérience',
                'technicalSkills' => ['Figma', 'Adobe XD', 'Sketch', 'InVision'],
                'softSkills' => ['Créativité', 'Empathie', 'Organisation'],
                'city' => 'Lyon',
                'bio' => 'Passionnée par l\'expérience utilisateur',
                'experience' => "2020-2023 : UX Designer chez Design Studio\n2019-2020 : UI Designer chez Creative Agency",
                'education' => "2017-2019 : Master en Design Numérique\n2014-2017 : Licence en Arts Appliqués"
            ],
            // Ajoutez d'autres candidats selon vos besoins
        ];
    }
} 