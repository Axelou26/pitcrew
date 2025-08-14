<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\JobOffer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-job-offer',
    description: 'Creates a new job offer',
)]
class CreateJobOfferCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Trouver un utilisateur avec l'email recruiter@example.com
        $recruiter = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'recruiter@example.com']);

        if (!$recruiter) {
            $output->writeln('No recruiter found. Please create a recruiter user first.');

            return Command::FAILURE;
        }

        $jobOffer = new JobOffer();
        $jobOffer->setTitle('Développeur PHP Symfony');
        $jobOffer
            ->setDescription('Nous recherchons un développeur PHP Symfony expérimenté pour rejoindre notre équipe
                .');
        $jobOffer->setContractType('CDI');
        $jobOffer->setLocation('Paris');
        $jobOffer->setSalary(45000);
        $jobOffer->setRequiredSkills(['PHP', 'Symfony', 'MySQL', 'JavaScript']);
        $jobOffer->setIsActive(true);
        $jobOffer->setRecruiter($recruiter);

        $this->entityManager->persist($jobOffer);
        $this->entityManager->flush();

        $output->writeln('Job offer created successfully with ID: ' . $jobOffer->getId());

        return Command::SUCCESS;
    }
}
