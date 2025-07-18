<?php

namespace App\Command;

use App\Entity\Post;
use App\Entity\JobOffer;
use App\Entity\Application;
use App\Entity\JobApplication;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use DateTimeImmutable;

#[AsCommand(
    name: 'app:fix-null-created-at',
    description: 'Corrige les entités qui ont une valeur null pour createdAt'
)]
class FixNullCreatedAtCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Correction des valeurs null pour createdAt');

        $now = new DateTimeImmutable();
        $fixedCount = 0;

        // Corriger les Posts
        $posts = $this->entityManager->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p.createdAt IS NULL')
            ->getQuery()
            ->getResult();

        foreach ($posts as $post) {
            $post->setCreatedAt($now);
            $fixedCount++;
        }

        // Corriger les JobOffers
        $jobOffers = $this->entityManager->getRepository(JobOffer::class)
            ->createQueryBuilder('j')
            ->where('j.createdAt IS NULL')
            ->getQuery()
            ->getResult();

        foreach ($jobOffers as $jobOffer) {
            $jobOffer->setCreatedAt($now);
            $fixedCount++;
        }

        // Corriger les Applications
        $applications = $this->entityManager->getRepository(Application::class)
            ->createQueryBuilder('a')
            ->where('a.createdAt IS NULL')
            ->getQuery()
            ->getResult();

        foreach ($applications as $application) {
            $application->setCreatedAt($now);
            $fixedCount++;
        }

        // Corriger les JobApplications
        $jobApplications = $this->entityManager->getRepository(JobApplication::class)
            ->createQueryBuilder('ja')
            ->where('ja.createdAt IS NULL')
            ->getQuery()
            ->getResult();

        foreach ($jobApplications as $jobApplication) {
            $jobApplication->setCreatedAt($now);
            $fixedCount++;
        }

        // Corriger les Notifications
        $notifications = $this->entityManager->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->where('n.createdAt IS NULL')
            ->getQuery()
            ->getResult();

        foreach ($notifications as $notification) {
            $notification->setCreatedAt($now);
            $fixedCount++;
        }

        if ($fixedCount === 0) {
            $io->info('Aucune entité avec createdAt null n\'a été trouvée.');
            return Command::SUCCESS;
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d entités ont été corrigées avec une date de création valide.', $fixedCount));

        return Command::SUCCESS;
    }
}
