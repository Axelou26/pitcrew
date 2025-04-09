<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\JobOffer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use DateTimeImmutable;

class JobOfferFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            RecruiterFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getJobOfferData() as $index => $data) {
            $jobOffer = new JobOffer();
            $jobOffer->setTitle($data['title'])
                ->setDescription($data['description'])
                ->setRequiredSkills($data['requiredSkills'])
                ->setLocation($data['location'])
                ->setIsRemote($data['isRemote'])
                ->setSalaryRange($data['salaryRange'])
                ->setRecruiter($this->getReference(RecruiterFixtures::RECRUITER_REFERENCE_PREFIX . ($index % 2)))
                ->setCreatedAt(new DateTimeImmutable())
                ->setIsActive(true);

            $manager->persist($jobOffer);
        }

        $manager->flush();
    }

    private function getJobOfferData(): array
    {
        return [
            [
                'title' => 'Développeur Full Stack PHP/Symfony',
                'description' => 'Nous recherchons un développeur Full Stack expérimenté pour rejoindre notre équipe',
                'requiredSkills' => ['PHP', 'Symfony', 'JavaScript', 'React', 'MySQL'],
                'location' => 'Paris',
                'isRemote' => true,
                'salaryRange' => '45k€-60k€'
            ],
            [
                'title' => 'UX/UI Designer Senior',
                'description' => 'Nous recherchons un designer expérimenté pour concevoir nos interfaces utilisateur',
                'requiredSkills' => ['Figma', 'Adobe XD', 'Sketch', 'Design System'],
                'location' => 'Lyon',
                'isRemote' => false,
                'salaryRange' => '40k€-55k€'
            ],
            [
                'title' => 'DevOps Engineer',
                'description' => 'Nous recherchons un ingénieur DevOps pour gérer notre infrastructure cloud',
                'requiredSkills' => ['AWS', 'Docker', 'Kubernetes', 'CI/CD'],
                'location' => 'Paris',
                'isRemote' => true,
                'salaryRange' => '50k€-70k€'
            ],
            // Ajoutez d'autres offres d'emploi selon vos besoins
        ];
    }
} 