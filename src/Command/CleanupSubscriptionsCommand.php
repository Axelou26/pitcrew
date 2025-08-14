<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-subscriptions',
    description: 'Nettoie les abonnements dupliqués et ne garde que 3 abonnements : Basic, Premium, Business'
)]
class CleanupSubscriptionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Nettoyage des abonnements dupliqués');

        // Supprimer tous les abonnements existants
        $existingSubscriptions = $this->entityManager->getRepository(Subscription::class)->findAll();
        $count                 = count($existingSubscriptions);

        if ($count > 0) {
            $io->note("Suppression de {$count} abonnements existants...");

            foreach ($existingSubscriptions as $subscription) {
                $this->entityManager->remove($subscription);
            }
            $this->entityManager->flush();
        }

        // Créer les 3 abonnements corrects
        $subscriptions = [
            [
                'name'         => 'Basic',
                'price'        => 0,
                'duration'     => 30,
                'maxJobOffers' => 3,
                'features'     => [
                    'post_job_offer',
                    'basic_applications',
                    'limited_messaging',
                    'standard_profile',
                ],
            ],
            [
                'name'         => 'Premium',
                'price'        => 49,
                'duration'     => 30,
                'maxJobOffers' => null,
                'features'     => [
                    'post_job_offer',
                    'unlimited_job_offers',
                    'highlighted_offers',
                    'full_cv_access',
                    'unlimited_messaging',
                    'basic_statistics',
                    'enhanced_profile',
                ],
            ],
            [
                'name'         => 'Business',
                'price'        => 99,
                'duration'     => 30,
                'maxJobOffers' => null,
                'features'     => [
                    'post_job_offer',
                    'unlimited_job_offers',
                    'advanced_candidate_search',
                    'automatic_recommendations',
                    'detailed_statistics',
                    'verified_badge',
                    'priority_support',
                ],
            ],
        ];

        $io->note('Création des 3 abonnements corrects...');

        foreach ($subscriptions as $subscriptionData) {
            $subscription = new Subscription();
            $subscription->setName($subscriptionData['name']);
            $subscription->setPrice($subscriptionData['price']);
            $subscription->setDuration($subscriptionData['duration']);
            $subscription->setMaxJobOffers($subscriptionData['maxJobOffers']);
            $subscription->setFeatures($subscriptionData['features']);
            $subscription->setIsActive(true);

            $this->entityManager->persist($subscription);

            $io->text("✓ Créé : {$subscriptionData['name']} - {$subscriptionData['price']}€");
        }

        $this->entityManager->flush();

        $io->success('Nettoyage terminé ! 3 abonnements créés : Basic, Premium, Business');

        return Command::SUCCESS;
    }
}
