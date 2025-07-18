<?php

namespace App\Command;

use App\Entity\Friendship;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-friendships',
    description: 'Crée des amitiés entre les utilisateurs existants',
)]
class CreateFriendshipsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findAll();

        if (count($users) < 2) {
            $io->error('Il faut au moins 2 utilisateurs pour créer des amitiés');
            return Command::FAILURE;
        }

        $io->info(sprintf('Création d\'amitiés entre %d utilisateurs...', count($users)));

        // Nombre d'amitiés créées
        $friendshipsCreated = 0;

        // Pour chaque utilisateur, créer une amitié avec les autres utilisateurs
        $userCount = count($users);
        for ($i = 0; $i < $userCount; $i++) {
            for ($j = $i + 1; $j < $userCount; $j++) {
                $user1 = $users[$i];
                $user2 = $users[$j];

                // Vérifier si une amitié existe déjà entre ces utilisateurs
                $existingFriendship = $this->entityManager->getRepository(Friendship::class)
                    ->findBetweenUsers($user1, $user2);

                if ($existingFriendship === null) {
                    // Créer une nouvelle amitié
                    $friendship = Friendship::createAccepted($user1, $user2);
                    $this->entityManager->persist($friendship);
                    $friendshipsCreated++;

                    $io->writeln(sprintf(
                        'Amitié créée entre %s et %s',
                        $user1->getFullName(),
                        $user2->getFullName()
                    ));
                }
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d amitiés ont été créées avec succès', $friendshipsCreated));

        return Command::SUCCESS;
    }
}
