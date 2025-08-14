<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Application;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

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
        // Candidatures acceptées
        $this->createApplication(
            $manager,
            'applicant_0',
            'job_offer_0',
            'Passionné de sport automobile depuis mon plus jeune âge, j\'ai 5 ans d\'expérience en tant que ' .
            'mécanicien de compétition. J\'ai travaillé sur différents championnats (F4, F3) et je souhaite ' .
            'maintenant évoluer en F1.',
            new DateTimeImmutable('-30 days'),
            'accepted'
        );

        $this->createApplication(
            $manager,
            'applicant_1',
            'job_offer_1',
            'Ingénieur en mécanique spécialisé en aérodynamique, je souhaite mettre mes compétences ' .
            'en analyse de données et simulation CFD au service de votre équipe.',
            new DateTimeImmutable('-25 days'),
            'accepted'
        );

        $this->createApplication(
            $manager,
            'applicant_2',
            'job_offer_4',
            'Mon expertise en analyse de données et en développement de solutions de télémétrie serait ' .
            'un atout majeur pour optimiser les performances de vos véhicules.',
            new DateTimeImmutable('-20 days'),
            'accepted'
        );

        // Candidatures en attente
        $this->createApplication(
            $manager,
            'applicant_3',
            'job_offer_2',
            'Fort de mon expérience en gestion d\'équipe et en stratégie de course, je souhaite rejoindre ' .
            'votre équipe pour contribuer à vos succès futurs.',
            new DateTimeImmutable('-5 days'),
            'pending'
        );

        $this->createApplication(
            $manager,
            'applicant_4',
            'job_offer_3',
            'Spécialiste des matériaux composites avec une solide expérience en F1, je suis convaincu ' .
            'de pouvoir apporter mon expertise à votre département composite.',
            new DateTimeImmutable('-4 days'),
            'pending'
        );

        $this->createApplication(
            $manager,
            'applicant_5',
            'job_offer_5',
            'Passionné par l\'innovation en sport automobile, je souhaite mettre mes compétences en ' .
            'développement moteur au service de votre équipe.',
            new DateTimeImmutable('-3 days'),
            'pending'
        );

        // Candidatures refusées
        $this->createApplication(
            $manager,
            'applicant_6',
            'job_offer_6',
            'Mon expérience en marketing digital et ma connaissance du sport automobile seraient ' .
            'des atouts pour développer votre stratégie marketing.',
            new DateTimeImmutable('-15 days'),
            'rejected'
        );

        $this->createApplication(
            $manager,
            'applicant_7',
            'job_offer_7',
            'Expert en développement de pneumatiques de compétition, je souhaite rejoindre votre ' .
            'équipe R&D pour participer aux innovations futures.',
            new DateTimeImmutable('-12 days'),
            'rejected'
        );

        // Candidatures en attente (remplacé interviewing par pending)
        $this->createApplication(
            $manager,
            'applicant_8',
            'job_offer_8',
            'Ingénieur électronique expérimenté, je suis passionné par le développement de systèmes ' .
            'embarqués pour la compétition automobile.',
            new DateTimeImmutable('-8 days'),
            'pending'
        );

        $this->createApplication(
            $manager,
            'applicant_9',
            'job_offer_9',
            'Spécialiste en aérodynamique avec une expérience en F1 et en GT, je souhaite apporter ' .
            'mon expertise à votre département aéro.',
            new DateTimeImmutable('-7 days'),
            'pending'
        );

        // Candidatures supplémentaires en attente
        $this->createApplication(
            $manager,
            'applicant_0',
            'job_offer_3',
            'Ma formation en matériaux composites et mon expérience en F3 me permettent de maîtriser ' .
            'parfaitement les techniques de fabrication et de réparation des pièces en carbone.',
            new DateTimeImmutable('-2 days'),
            'pending'
        );

        $this->createApplication(
            $manager,
            'applicant_1',
            'job_offer_4',
            'Mon expérience en analyse de données de course et en développement Python serait un atout ' .
            'pour votre équipe de télémétrie.',
            new DateTimeImmutable('-1 day'),
            'pending'
        );

        $manager->flush();
    }

    private function createApplication(
        ObjectManager $manager,
        string $applicantReference,
        string $jobOfferReference,
        string $message,
        DateTimeImmutable $date,
        string $status = 'pending'
    ): void {
        $application = new Application();
        $application->setApplicant($this->getReference($applicantReference))
            ->setJobOffer($this->getReference($jobOfferReference))
            ->setCoverLetter($message)
            ->setStatus($status)
            ->setCreatedAt($date);

        $manager->persist($application);
    }
}
