<?php

namespace App\Command;

use App\Entity\Applicant;
use App\Entity\JobOffer;
use App\Service\MatchingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:refresh-matching',
    description: 'Rafraîchit les scores de matching entre candidats et offres d\'emploi',
)]
class RefreshMatchingCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private MatchingService $matchingService;

    public function __construct(
        EntityManagerInterface $entityManager,
        MatchingService $matchingService
    ) {
        $this->entityManager = $entityManager;
        $this->matchingService = $matchingService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this


                    
                       ->addOption('job-offer', null, InputOption::VALUE_REQUIRED, 'ID de l\'offre d\'emploi spécifique à traiter')
            ->addOption('applicant', null, InputOption::VALUE_REQUIRED, 'ID du candidat spécifique à traiter')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre d\'éléments à traiter', 10)
            ->addOption('dump', null, InputOption::VALUE_NONE, 'Affiche des informations détaillées sur le matching');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Rafraîchissement des scores de matching');

        $limit = (int)$input->getOption('limit');
        $jobOfferId = $input->getOption('job-offer');
        $applicantId = $input->getOption('applicant');
        $dump = $input->getOption('dump');

        if ($jobOfferId) {
            $this->processSpecificJobOffer($io, (int)$jobOfferId, $limit, $dump);
        } elseif ($applicantId) {
            $this->processSpecificApplicant($io, (int)$applicantId, $limit, $dump);
        } else {
            $this->processAllMatches($io, $limit, $dump);
        }

        $io->success('Les scores de matching ont été rafraîchis avec succès.');

        return Command::SUCCESS;
    }

    private function processSpecificJobOffer(SymfonyStyle $io, int $jobOfferId, int $limit, bool $dump): void
    {
        $jobOffer = $this->entityManager->getRepository(JobOffer::class)->find($jobOfferId);

        if (!$jobOffer) {
            $io->error(sprintf('Offre d\'emploi #%d introuvable.', $jobOfferId));
            return;
        }

        $io->section(sprintf('Traitement de l\'offre: %s (ID: %d)', $jobOffer->getTitle(), $jobOffer->getId()));

        $candidates = $this->matchingService->findBestCandidatesForJobOffer($jobOffer, $limit);

        if (empty($candidates)) {
            $io->warning('Aucun candidat trouvé pour cette offre.');
            return;
        }

        $this->displayCandidateResults($io, $candidates, $dump);
    }

    private function processSpecificApplicant(SymfonyStyle $io, int $applicantId, int $limit, bool $dump): void
    {
        $applicant = $this->entityManager->getRepository(Applicant::class)->find($applicantId);

        if (!$applicant) {
            $io->error(sprintf('Candidat #%d introuvable.', $applicantId));
            return;
        }

        $io->section(sprintf(
            'Traitement du candidat: %s %s (ID: %d)',
            $applicant->getFirstName(),
            $applicant->getLastName(),
            $applicant->getId()
        ));

        $offers = $this->matchingService->findBestJobOffersForCandidate($applicant, $limit);

        if (empty($offers)) {
            $io->warning('Aucune offre d\'emploi trouvée pour ce candidat.');
            return;
        }

        $this->displayJobOfferResults($io, $offers, $dump);
    }

    private function processAllMatches(SymfonyStyle $io, int $limit, bool $dump): void
    {
        $io->section('Traitement de tous les candidats et offres d\'emploi actifs');

        $candidates = $this->entityManager->getRepository(Applicant::class)->findAll();
        $offers = $this->entityManager->getRepository(JobOffer::class)->findBy(['isActive' => true]);

        $io
            ->writeln(sprintf('Trouvé %d candidat(s) et %d offre(s) d\'emploi active(s)
                .', count($candidates), count($offers)));

        $progressBar = $io->createProgressBar(count($candidates));
        $progressBar->start();

        foreach ($candidates as $applicant) {
            // Pour chaque candidat, calculer les offres qui correspondent le mieux
            $this->matchingService->findBestJobOffersForCandidate($applicant, 5);
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Afficher quelques exemples aléatoires
        if (count($candidates) > 0 && count($offers) > 0) {
            $randomCandidate = $candidates[array_rand($candidates)];
            $io->section('Exemple de résultats pour un candidat aléatoire');
            $sampleOffers = $this->matchingService->findBestJobOffersForCandidate($randomCandidate, 5);
            $this->displayJobOfferResults($io, $sampleOffers, $dump);

            $randomOffer = $offers[array_rand($offers)];
            $io->section('Exemple de résultats pour une offre aléatoire');
            $sampleCandidates = $this->matchingService->findBestCandidatesForJobOffer($randomOffer, 5);
            $this->displayCandidateResults($io, $sampleCandidates, $dump);
        }
    }

    private function displayCandidateResults(SymfonyStyle $io, array $candidates, bool $dump): void
    {
        $rows = [];
        foreach ($candidates as $index => $candidate) {
            $rows[] = [
                $index + 1,
                $candidate['applicant']->getId(),
                $candidate['applicant']->getFirstName() . ' ' . $candidate['applicant']->getLastName(),
                $candidate['score'] . '%',
                $this->getSkillMatches($candidate['reasons'])
            ];

            if ($dump) {
                $this->dumpReasonDetails($io, $candidate['reasons']);
            }
        }

        $io->table(
            ['#', 'ID', 'Nom', 'Score', 'Compétences correspondantes'],
            $rows
        );
    }

    private function displayJobOfferResults(SymfonyStyle $io, array $offers, bool $dump): void
    {
        $rows = [];
        foreach ($offers as $index => $offer) {
            $rows[] = [
                $index + 1,
                $offer['jobOffer']->getId(),
                $offer['jobOffer']->getTitle(),
                $offer['score'] . '%',
                $offer['jobOffer']->getLocation()
            ];

            if ($dump) {
                $this->dumpReasonDetails($io, $offer['reasons']);
            }
        }

        $io->table(
            ['#', 'ID', 'Titre', 'Score', 'Localisation'],
            $rows
        );
    }

    private function getSkillMatches(array $reasons): string
    {
        $skillMatches = [];

        foreach ($reasons as $reason) {
            if ($reason['category'] === 'Compétences techniques' && !empty($reason['matches'])) {
                $skillMatches = array_merge($skillMatches, array_slice($reason['matches'], 0, 3));
            }
        }

        return implode(', ', array_slice($skillMatches, 0, 5));
    }

    private function dumpReasonDetails(SymfonyStyle $io, array $reasons): void
    {
        $io->writeln('<info>Détails du score:</info>');

        foreach ($reasons as $reason) {
            $io->writeln(sprintf(
                '- <comment>%s</comment>: %d/%d (%d%%)',
                $reason['category'],
                $reason['score'],
                $reason['maxScore'],
                $reason['maxScore'] > 0 ? round(($reason['score'] / $reason['maxScore']) * 100) : 0
            ));

            if (isset($reason['matches']) && !empty($reason['matches'])) {
                $io->writeln('  Correspondances: ' . implode(', ', $reason['matches']));
            }

            if (isset($reason['details']) && !empty($reason['details'])) {
                $io->writeln('  Détails: ' . implode("\n  - ", $reason['details']));
            }
        }

        $io->newLine();
    }
}
