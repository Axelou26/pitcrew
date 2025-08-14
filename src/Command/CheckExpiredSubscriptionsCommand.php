<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\SubscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-expired-subscriptions',
    description: 'Vérifie et met à jour les abonnements expirés',
)]
class CheckExpiredSubscriptionsCommand extends Command
{
    private $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Cette commande vérifie les abonnements expirés et met à jour leur statut
                        . Elle est conçue pour être exécutée quotidiennement via un cron job.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Vérification des abonnements expirés');

        try {
            $ioStyle->info('Début de la vérification...');
            $this->subscriptionService->checkExpiredSubscriptions();
            $ioStyle->success('Les abonnements expirés ont été mis à jour avec succès.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $ioStyle
                ->error('Une erreur est survenue lors de la vérification des abonnements expirés : ' . $e
                    ->getMessage());

            return Command::FAILURE;
        }
    }
}
