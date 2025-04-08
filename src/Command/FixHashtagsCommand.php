<?php

namespace App\Command;

use App\Entity\Hashtag;
use App\Repository\HashtagRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-hashtags',
    description: 'Corrige les compteurs d\'utilisation des hashtags',
)]
class FixHashtagsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private HashtagRepository $hashtagRepository;
    private PostRepository $postRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        HashtagRepository $hashtagRepository,
        PostRepository $postRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->hashtagRepository = $hashtagRepository;
        $this->postRepository = $postRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Mise à jour des compteurs d\'utilisation des hashtags');

        // Récupérer tous les hashtags
        $hashtags = $this->hashtagRepository->findAll();

        if (empty($hashtags)) {
            $io->warning('Aucun hashtag trouvé dans la base de données.');
            return Command::SUCCESS;
        }

        $io->progressStart(count($hashtags));

        foreach ($hashtags as $hashtag) {
            try {
                // Compter le nombre de posts qui utilisent ce hashtag
                $count = $this->postRepository->countByHashtag($hashtag);

                // Mettre à jour le compteur
                $hashtag->setUsageCount($count);

                // Mettre à jour la date de dernière utilisation
                if ($count > 0) {
                    $hashtag->setLastUsedAt(new \DateTimeImmutable());
                }

                $io->progressAdvance();
            } catch (\Exception $e) {
                $io
                    ->error('Erreur lors de la mise à jour du hashtag #' . $hashtag
                    ->getName() . ': ' . $e
                    ->getMessage());
            }
        }

        // Sauvegarder les modifications
        $this->entityManager->flush();

        $io->progressFinish();
        $io->success('Les compteurs des hashtags ont été mis à jour avec succès !');

        return Command::SUCCESS;
    }
}
