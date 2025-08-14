<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Applicant;
use App\Entity\JobOffer;
use App\Repository\JobOfferRepository;
use App\Repository\UserRepository;
use App\Service\MatchingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:matching:test',
    description: 'Test le système de matching entre candidats et offres d\'emploi'
)]
class MatchingTestCommand extends Command
{
    private MatchingService $matchingService;
    private UserRepository $userRepository;
    private JobOfferRepository $jobOfferRepository;

    public function __construct(
        MatchingService $matchingService,
        UserRepository $userRepository,
        JobOfferRepository $jobOfferRepository
    ) {
        parent::__construct();
        $this->matchingService    = $matchingService;
        $this->userRepository     = $userRepository;
        $this->jobOfferRepository = $jobOfferRepository;
    }

    protected function configure(): void
    {
        $this
            ->addOption('list-applicants', null, InputOption::VALUE_NONE, 'Liste tous les candidats')
            ->addOption('list-offers', null, InputOption::VALUE_NONE, 'Liste toutes les offres d\'emploi')
            ->addOption('applicant', null, InputOption::VALUE_REQUIRED, 'ID du candidat')
            ->addOption('job-offer', null, InputOption::VALUE_REQUIRED, 'ID de l\'offre d\'emploi')
            ->addOption(
                'find-candidates',
                null,
                InputOption::VALUE_REQUIRED,
                'Trouve les meilleurs candidats pour une offre (ID)'
            )
            ->addOption(
                'find-offers',
                null,
                InputOption::VALUE_REQUIRED,
                'Trouve les meilleures offres pour un candidat (ID)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);

        // Option: Liste tous les candidats
        if ($input->getOption('list-applicants')) {
            $this->listApplicants($ioStyle);

            return Command::SUCCESS;
        }

        // Option: Liste toutes les offres d'emploi
        if ($input->getOption('list-offers')) {
            $this->listJobOffers($ioStyle);

            return Command::SUCCESS;
        }

        // Option: Trouve les meilleurs candidats pour une offre
        $offerId = $input->getOption('find-candidates');
        if ($offerId) {
            $this->findBestCandidatesForJobOffer($ioStyle, (int) $offerId);

            return Command::SUCCESS;
        }

        // Option: Trouve les meilleures offres pour un candidat
        $applicantId = $input->getOption('find-offers');
        if ($applicantId) {
            $this->findBestOffersForCandidate($ioStyle, (int) $applicantId);

            return Command::SUCCESS;
        }

        // Option: Calcule le score de compatibilité entre un candidat et une offre
        $applicantId = $input->getOption('applicant');
        $jobOfferId  = $input->getOption('job-offer');

        if ($applicantId && $jobOfferId) {
            $this->showCompatibilityScore($ioStyle, (int) $applicantId, (int) $jobOfferId);

            return Command::SUCCESS;
        }

        // Si aucune option n'est fournie, afficher l'aide
        $ioStyle->comment('Aucune option spécifiée. Utilisez --help pour voir les options disponibles.');

        return Command::SUCCESS;
    }

    /**
     * Liste tous les candidats (utilisateurs avec le rôle ROLE_POSTULANT).
     */
    private function listApplicants(SymfonyStyle $ioStyle): void
    {
        $applicants = $this->userRepository->findByRole('ROLE_POSTULANT');

        if (empty($applicants)) {
            $ioStyle->warning('Aucun candidat trouvé dans la base de données.');

            return;
        }

        $rows = [];
        foreach ($applicants as $applicant) {
            if ($applicant instanceof Applicant) {
                $rows[] = [
                    $applicant->getId(),
                    $applicant->getFirstName() . ' ' . $applicant->getLastName(),
                    $applicant->getEmail(),
                    \count($applicant->getTechnicalSkills() ?? []) . ' compétences techniques',
                    $applicant->getCity() ?? 'Non spécifiée',
                ];
            }
        }

        $ioStyle->section('Liste des candidats');
        $ioStyle->table(
            ['ID', 'Nom', 'Email', 'Compétences', 'Ville'],
            $rows
        );
        $ioStyle->success(\sprintf('%d candidats trouvés.', \count($rows)));
    }

    /**
     * Liste toutes les offres d'emploi actives.
     */
    private function listJobOffers(SymfonyStyle $ioStyle): void
    {
        $jobOffers = $this->jobOfferRepository->findBy(['isActive' => true]);

        if (empty($jobOffers)) {
            $ioStyle->warning('Aucune offre d\'emploi active trouvée dans la base de données.');

            return;
        }

        $rows = [];
        foreach ($jobOffers as $jobOffer) {
            $rows[] = [
                $jobOffer->getId(),
                $jobOffer->getTitle(),
                $jobOffer->getCompany(),
                $jobOffer->getLocation() . ($jobOffer->getIsRemote() ? ' (Remote)' : ''),
                \count($jobOffer->getRequiredSkills()),
            ];
        }

        $ioStyle->section('Liste des offres d\'emploi actives');
        $ioStyle->table(
            ['ID', 'Titre', 'Entreprise', 'Localisation', 'Compétences requises'],
            $rows
        );
        $ioStyle->success(\sprintf('%d offres trouvées.', \count($rows)));
    }

    /**
     * Affiche le score de compatibilité entre un candidat et une offre d'emploi.
     */
    private function showCompatibilityScore(SymfonyStyle $ioStyle, int $applicantId, int $jobOfferId): void
    {
        $applicant = $this->findApplicant($ioStyle, $applicantId);
        if (!$applicant) {
            return;
        }

        $jobOffer = $this->findJobOffer($ioStyle, $jobOfferId);
        if (!$jobOffer) {
            return;
        }

        $score = $this->matchingService->calculateCompatibilityScore($applicant, $jobOffer);
        $this->displayCompatibilityScore($ioStyle, $applicant, $jobOffer, $score);
    }

    private function findApplicant(SymfonyStyle $ioStyle, int $applicantId): ?Applicant
    {
        $applicant = $this->userRepository->find($applicantId);
        if (!$applicant || !($applicant instanceof Applicant)) {
            $ioStyle->error('Candidat non trouvé ou l\'utilisateur n\'est pas un candidat.');

            return null;
        }

        return $applicant;
    }

    private function findJobOffer(SymfonyStyle $ioStyle, int $jobOfferId): ?JobOffer
    {
        $jobOffer = $this->jobOfferRepository->find($jobOfferId);
        if (!$jobOffer) {
            $ioStyle->error('Offre d\'emploi non trouvée.');

            return null;
        }

        return $jobOffer;
    }

    private function displayCompatibilityScore(
        SymfonyStyle $ioStyle,
        Applicant $applicant,
        JobOffer $jobOffer,
        array $score
    ): void {
        $ioStyle->section(\sprintf(
            'Score de compatibilité entre %s %s et %s',
            $applicant->getFirstName(),
            $applicant->getLastName(),
            $jobOffer->getTitle()
        ));

        $ioStyle->success(\sprintf('Score global: %d%%', $score['score']));
        $this->displayScoreDetails($ioStyle, $score['reasons']);
    }

    private function displayScoreDetails(SymfonyStyle $ioStyle, array $reasons): void
    {
        $ioStyle->section('Détails par catégorie:');
        foreach ($reasons as $reason) {
            $this->displayReasonDetails($ioStyle, $reason);
        }
    }

    private function displayReasonDetails(SymfonyStyle $ioStyle, array $reason): void
    {
        $ioStyle->writeln(\sprintf(
            '<info>%s</info>: %d/%d (%d%%)',
            $reason['category'],
            $reason['score'],
            $reason['maxScore'],
            $reason['maxScore'] > 0 ? round(($reason['score'] / $reason['maxScore']) * 100) : 0
        ));

        if (isset($reason['matches']) && !empty($reason['matches'])) {
            $ioStyle->writeln('  Correspondances: ' . implode(', ', $reason['matches']));
        }

        if (isset($reason['details']) && !empty($reason['details'])) {
            $ioStyle->writeln('  Détails: ' . implode(', ', $reason['details']));
        }

        $ioStyle->newLine();
    }

    /**
     * Trouve les meilleurs candidats pour une offre d'emploi.
     */
    private function findBestCandidatesForJobOffer(SymfonyStyle $ioStyle, int $jobOfferId): void
    {
        $jobOffer = $this->jobOfferRepository->find($jobOfferId);

        if (!$jobOffer) {
            $ioStyle->error('Offre d\'emploi non trouvée.');

            return;
        }

        $candidates = $this->matchingService->findBestCandidatesForJobOffer($jobOffer);

        if (empty($candidates)) {
            $ioStyle->warning('Aucun candidat compatible trouvé pour cette offre.');

            return;
        }

        $ioStyle->section(\sprintf('Meilleurs candidats pour "%s"', $jobOffer->getTitle()));

        $rows = [];
        foreach ($candidates as $index => $candidate) {
            $rows[] = [
                $index + 1,
                $candidate['applicant']->getId(),
                $candidate['applicant']->getFirstName() . ' ' . $candidate['applicant']->getLastName(),
                $candidate['score'] . '%',
                implode(', ', $this->getSkillsFromReasons($candidate['reasons'])),
            ];
        }

        $ioStyle->table(
            ['#', 'ID', 'Nom', 'Score', 'Compétences clés'],
            $rows
        );

        $ioStyle->success(\sprintf('%d candidats trouvés.', \count($candidates)));
    }

    /**
     * Trouve les meilleures offres pour un candidat.
     */
    private function findBestOffersForCandidate(SymfonyStyle $ioStyle, int $applicantId): void
    {
        $applicant = $this->userRepository->find($applicantId);

        if (!$applicant || !($applicant instanceof Applicant)) {
            $ioStyle->error('Candidat non trouvé ou l\'utilisateur n\'est pas un candidat.');

            return;
        }

        $offers = $this->matchingService->findBestJobOffersForCandidate($applicant);

        if (empty($offers)) {
            $ioStyle->warning('Aucune offre d\'emploi compatible trouvée pour ce candidat.');

            return;
        }

        $ioStyle->section(
            \sprintf('Meilleures offres pour %s %s', $applicant->getFirstName(), $applicant->getLastName())
        );

        $rows = [];
        foreach ($offers as $index => $offer) {
            $rows[] = [
                $index + 1,
                $offer['jobOffer']->getId(),
                $offer['jobOffer']->getTitle(),
                $offer['jobOffer']->getCompany(),
                $offer['score'] . '%',
                $offer['jobOffer']->getLocation() . ($offer['jobOffer']->getIsRemote() ? ' (Remote)' : ''),
            ];
        }

        $ioStyle->table(
            ['#', 'ID', 'Titre', 'Entreprise', 'Score', 'Localisation'],
            $rows
        );

        $ioStyle->success(\sprintf('%d offres trouvées.', \count($offers)));
    }

    /**
     * Extrait les compétences techniques des raisons de matching.
     */
    private function getSkillsFromReasons(array $reasons): array
    {
        $skills = [];

        foreach ($reasons as $reason) {
            if ($reason['category'] === 'Compétences techniques' && isset($reason['matches'])) {
                $skills = array_merge($skills, $reason['matches']);
            }
        }

        return \array_slice($skills, 0, 5); // Limiter à 5 compétences pour l'affichage
    }
}
