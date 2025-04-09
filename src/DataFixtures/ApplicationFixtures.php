<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Application;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use DateTimeImmutable;

class ApplicationFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            ApplicantFixtures::class,
            JobOfferFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Candidat 1 postule à 2 offres
        $this->createApplication(
            $manager,
            'applicant_0',
            'job_offer_0',
            'Passionné de sport automobile depuis mon plus jeune âge, j\'ai 5 ans d\'expérience en tant que ' .
            'mécanicien de compétition. J\'ai travaillé sur différents championnats (F4, F3) et je souhaite ' .
            'maintenant évoluer en F1.',
            new DateTimeImmutable('-5 days')
        );

        $this->createApplication(
            $manager,
            'applicant_0',
            'job_offer_3',
            'Ma formation en matériaux composites et mon expérience en F3 me permettent de maîtriser ' .
            'parfaitement les techniques de fabrication et de réparation des pièces en carbone.',
            new DateTimeImmutable('-2 days')
        );

        // Candidat 2 postule à 2 offres
        $this->createApplication(
            $manager,
            'applicant_1',
            'job_offer_1',
            'Ingénieur en mécanique spécialisé en aérodynamique, je souhaite mettre mes compétences ' .
            'en analyse de données et simulation CFD au service de votre équipe.',
            new DateTimeImmutable('-3 days')
        );

        $this->createApplication(
            $manager,
            'applicant_1',
            'job_offer_4',
            'Mon expérience en analyse de données de course et en développement Python serait un atout ' .
            'pour votre équipe de télémétrie.',
            new DateTimeImmutable('-1 day')
        );

        $manager->flush();
    }

    private function createApplication(
        ObjectManager $manager,
        string $applicantReference,
        string $jobOfferReference,
        string $message,
        DateTimeImmutable $date
    ): void {
        $application = new Application();
        $application->setApplicant($this->getReference($applicantReference))
            ->setJobOffer($this->getReference($jobOfferReference))
            ->setCoverLetter($message)
            ->setStatus('pending')
            ->setCreatedAt($date);

        $manager->persist($application);
    }
}
