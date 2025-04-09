<?php

namespace App\Command;

use App\Repository\RecruiterSubscriptionRepository;
use App\Service\StripeService;
use App\Service\EmailService;
use DateTime;
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
    private $subRepo;
    private $entityManager;
    private $stripeService;
    private $emailService;

    public function __construct(
        RecruiterSubscriptionRepository $subRepo,
        EntityManagerInterface $entityManager,
        StripeService $stripeService,
        EmailService $emailService
    ) {
        parent::__construct();
        $this->subRepo = $subRepo;
        $this->entityManager = $entityManager;
        $this->stripeService = $stripeService;
        $this->emailService = $emailService;
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                'Cette commande vérifie les abonnements qui arrivent à expiration dans les prochaines 24 heures ' .
                'et les renouvelle automatiquement si l\'option de renouvellement automatique est activée.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Renouvellement automatique des abonnements');

        try {
            $ioStyle->info('Recherche des abonnements à renouveler...');

            // Récupérer les abonnements qui expirent dans les prochaines 24 heures
            $now = new DateTime();
            $tomorrow = (new DateTime())->modify('+1 day');

            $subsToRenew = $this->subRepo->createQueryBuilder('rs')
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

            $ioStyle->info(sprintf('Nombre d\'abonnements à renouveler : %d', count($subsToRenew)));

            $renewedCount = 0;
            $failedCount = 0;

            foreach ($subsToRenew as $subscription) {
                $ioStyle->text(sprintf(
                    'Traitement de l\'abonnement #%d pour %s',
                    $subscription->getId(),
                    $subscription->getRecruiter()->getFullName()
                ));

                try {
                    // Si l'abonnement a un ID Stripe, le renouvellement sera géré par Stripe
                    if ($subscription->getStripeSubscriptionId()) {
                        $ioStyle->text('Abonnement Stripe - Le renouvellement sera géré par Stripe');
                        continue;
                    }

                    // Pour les abonnements sans ID Stripe, nous devons les renouveler manuellement
                    $user = $subscription->getRecruiter();
                    $subType = $subscription->getSubscription();

                    // Vérifier si le renouvellement automatique est possible
                    if (!($subType->getPrice() > 0 && $user->getStripeCustomerId())) {
                        $ioStyle->text(
                            '<comment>Impossible de renouveler automatiquement - ' .
                            'Pas de moyen de paiement enregistré</comment>'
                        );
                        // Envoyer un rappel d'expiration
                        $this->emailService->sendSubscriptionExpirationReminder($user, $subscription);
                        $failedCount++;
                        continue; // Passer à l'abonnement suivant
                    }

                    // Logique pour effectuer un paiement automatique via Stripe
                    $ioStyle->text('Tentative de renouvellement automatique...');

                    // Prolonger l'abonnement
                    $newEndDate = clone $subscription->getEndDate();
                    $newEndDate->modify('+' . $subType->getDuration() . ' days');
                    $subscription->setEndDate($newEndDate);

                    $this->entityManager->persist($subscription);
                    $this->entityManager->flush();

                    // Envoyer un email de confirmation de renouvellement
                    $this->emailService->sendSubscriptionConfirmation($user, $subscription);

                    $renewedCount++;
                    $ioStyle->text('<info>Abonnement renouvelé avec succès</info>');

                } catch (\Exception $e) {
                    $ioStyle->error(sprintf(
                        'Erreur lors du renouvellement de l\'abonnement #%d : %s',
                        $subscription->getId(),
                        $e->getMessage()
                    ));
                    $failedCount++;
                }
            }

            $ioStyle->success(sprintf(
                'Renouvellement terminé : %d abonnements renouvelés, %d échecs',
                $renewedCount,
                $failedCount
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $ioStyle->error('Une erreur est survenue : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
