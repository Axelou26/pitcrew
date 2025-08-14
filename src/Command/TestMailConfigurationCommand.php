<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-mail-config',
    description: 'Teste la configuration du service d\'envoi d\'emails',
)]
class TestMailConfigurationCommand extends Command
{
    public function __construct(
        private readonly EmailService $emailService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email de test');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $io->note(\sprintf('Tentative d\'envoi d\'un email de test à %s', $email));

        try {
            $this->emailService->sendTestEmail($email);
            $io->success('Email de test envoyé avec succès!');

            $io->info('Pour vérifier les emails envoyés en développement:');
            $io->listing([
                'Accédez à http://localhost:8025 pour voir les emails dans MailHog',
                'Vérifiez les logs pour plus d\'informations',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(\sprintf('Erreur lors de l\'envoi de l\'email: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
