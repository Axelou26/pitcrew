<?php

namespace App\Command;

use App\Repository\RecruiterSubscriptionRepository;
use App\Service\StripeService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:renew-subscriptions',
    description: 'Renouvelle automatiquement les abonnements arrivant à expiration',
)]
class RenewSubscriptionsCommand extends Command
{
    private $recruiterSubscriptionRepository;
    private $entityManager;
    private $stripeService;
    private $emailService;

    public function __construct(
        RecruiterSubscriptionRepository $recruiterSubscriptionRepository,
        EntityManagerInterface $entityManager,
        StripeService $stripeService,
        EmailService $emailService
    ) {
        parent::__construct();
        $this->recruiterSubscriptionRepository = $recruiterSubscriptionRepository;
        $this->entityManager = $entityManager;
        $this->stripeService = $stripeService;
        $this->emailService = $emailService;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Cette commande vérifie les abonnements qui arrivent à expiration dans les prochaines 24 heures et les renouvelle automatiquement si l\'option de renouvellement automatique est activée.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Renouvellement automatique des abonnements');

        try {
            $io->info('Recherche des abonnements à renouveler...');

            // Récupérer les abonnements qui expirent dans les prochaines 24 heures
            $now = new \DateTime();
            $tomorrow = (new \DateTime())->modify('+1 day');

            $subscriptionsToRenew = $this->recruiterSubscriptionRepository->createQueryBuilder('rs')
                ->where('rs.isActive = :active')
                ->andWhere('rs.endDate BETWEEN :now AND :tomorrow')
                ->andWhere('rs.autoRenew = :autoRenew')
                ->andWhere('rs.cancelled = :cancelled')
                ->setParameter('active', true)
                ->setParameter('now', $now)
                ->setParameter('tomorrow', $tomorrow)
                ->setParameter('autoRenew', true)
                ->setParameter('cancelled', false)
                ->getQuery()
                ->getResult();

            $io->info(sprintf('Nombre d\'abonnements à renouveler : %d', count($subscriptionsToRenew)));

            $renewedCount = 0;
            $failedCount = 0;

            foreach ($subscriptionsToRenew as $subscription) {
                $io->text(sprintf(
                    'Traitement de l\'abonnement #%d pour %s',
                    $subscription->getId(),
                    $subscription->getRecruiter()->getFullName()
                ));

                try {
                    // Si l'abonnement a un ID Stripe, le renouvellement sera géré par Stripe
                    if ($subscription->getStripeSubscriptionId()) {
                        $io->text('Abonnement Stripe - Le renouvellement sera géré par Stripe');
                        continue;
                    }

                    // Pour les abonnements sans ID Stripe, nous devons les renouveler manuellement
                    $user = $subscription->getRecruiter();
                    $subscriptionType = $subscription->getSubscription();

                    // Créer une session de paiement Stripe pour le renouvellement
                    if ($subscriptionType->getPrice() > 0 && $user->getStripeCustomerId()) {
                        // Logique pour effectuer un paiement automatique via Stripe
                        // Cette partie dépend de l'implémentation spécifique de votre StripeService
                        $io->text('Tentative de renouvellement automatique...');

                        // Prolonger l'abonnement
                        $newEndDate = clone $subscription->getEndDate();
                        $newEndDate->modify('+' . $subscriptionType->getDuration() . ' days');
                        $subscription->setEndDate($newEndDate);

                        $this->entityManager->persist($subscription);
                        $this->entityManager->flush();

                        // Envoyer un email de confirmation de renouvellement
                        $this->emailService->sendSubscriptionConfirmation($user, $subscription);

                        $renewedCount++;
                        $io->text('<info>Abonnement renouvelé avec succès</info>');
                    } else {
                        $io->text('<comment>Impossible de renouveler automatiquement - Pas de moyen de paiement enregistré</comment>');

                        // Envoyer un rappel d'expiration
                        $this->emailService->sendSubscriptionExpirationReminder($user, $subscription);

                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $io->error(sprintf(
                        'Erreur lors du renouvellement de l\'abonnement #%d : %s',
                        $subscription->getId(),
                        $e->getMessage()
                    ));
                    $failedCount++;
                }
            }

            $io->success(sprintf(
                'Renouvellement terminé : %d abonnements renouvelés, %d échecs',
                $renewedCount,
                $failedCount
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Une erreur est survenue : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
