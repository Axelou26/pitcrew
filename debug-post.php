<?php
// Script de débogage pour l'entité Post

require_once __DIR__ . '/vendor/autoload.php';

use App\Entity\Post;
use App\Entity\User;
use App\Entity\PostComment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Définir les variables d'environnement nécessaires
$_SERVER['APP_ENV'] = 'dev';
$_SERVER['APP_DEBUG'] = 'true';

// Récupérer le Kernel Symfony
$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

// Récupérer l'EntityManager
$entityManager = $kernel->getContainer()->get('doctrine')->getManager();
$postRepository = $entityManager->getRepository(Post::class);

// Récupérer le post avec l'ID 31
try {
    $post = $postRepository->find(31);
    
    if ($post) {
        echo "Post trouvé: ID " . $post->getId() . "\n";
        echo "Titre: " . $post->getTitle() . "\n";
        echo "Contenu: " . $post->getContent() . "\n";
        echo "Auteur: " . ($post->getAuthor() ? $post->getAuthor()->getFullName() : "Non défini") . "\n";
        
        // Récupérer les commentaires
        $comments = $post->getComments();
        echo "Nombre de commentaires: " . count($comments) . "\n";
        
        foreach ($comments as $comment) {
            echo "  - Commentaire ID " . $comment->getId() . ": " . $comment->getContent() . "\n";
            echo "    Par: " . ($comment->getAuthor() ? $comment->getAuthor()->getFullName() : "Inconnu") . "\n";
            
            // Vérifier les réponses
            $replies = $comment->getReplies();
            echo "    Nombre de réponses: " . count($replies) . "\n";
            
            foreach ($replies as $reply) {
                echo "      - Réponse ID " . $reply->getId() . ": " . $reply->getContent() . "\n";
            }
        }
    } else {
        echo "Post avec ID 31 non trouvé\n";
    }
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
