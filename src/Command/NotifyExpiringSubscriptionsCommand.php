<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\RecruiterSubscriptionRepository;
use App\Service\EmailService;
use App\Service\NotificationService;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notify-expiring-subscriptions',
    description: 'Notifie les utilisateurs dont l\'abonnement expire bientôt',
)]
class NotifyExpiringSubscriptionsCommand extends Command
{
    private $subRepo;
    private $notificationService;
    private $emailService;

    public function __construct(
        RecruiterSubscriptionRepository $subscriptionRepo,
        NotificationService $notificationService,
        EmailService $emailService
    ) {
        parent::__construct();
        $this->subRepo             = $subscriptionRepo;
        $this->notificationService = $notificationService;
        $this->emailService        = $emailService;
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                'Cette commande envoie des notifications aux utilisateurs dont l\'abonnement expire ' .
                'dans les 7 prochains jours. Elle est conçue pour être exécutée quotidiennement via un cron job.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Notification des abonnements expirant bientôt');

        try {
            $expiringSubs = $this->subRepo->findExpiringSubscriptions();

            if (empty($expiringSubs)) {
                $ioStyle->info('Aucun abonnement n\'expire dans les 7 prochains jours.');

                return Command::SUCCESS;
            }

            $ioStyle
                ->info(\sprintf('Envoi de notifications pour %d abonnements expirant bientôt
                    ...', \count($expiringSubs)));

            foreach ($expiringSubs as $subscription) {
                $user     = $subscription->getRecruiter();
                $daysLeft = (new DateTimeImmutable())->diff($subscription->getEndDate())->days;

                // Créer une notification dans l'application
                $this->notificationService->createNotification(
                    $user,
                    'Votre abonnement ' . $subscription
                        ->getSubscription()
                        ->getName() . ' expire dans ' . $daysLeft . ' jours',
                    'Renouvelez votre abonnement pour continuer à profiter de tous les avantages.',
                    'subscription_expiring',
                    'app_subscription_manage'
                );

                // Envoyer un email de rappel
                $this->emailService->sendSubscriptionExpirationReminder($user, $subscription);

                $ioStyle->text(\sprintf(
                    'Notification envoyée à %s pour l\'abonnement %s expirant dans %d jours',
                    $user->getEmail(),
                    $subscription->getSubscription()->getName(),
                    $daysLeft
                ));
            }

            $ioStyle->success('Toutes les notifications ont été envoyées avec succès.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $ioStyle->error('Une erreur est survenue lors de l\'envoi des notifications : ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
