<?php

declare(strict_types = 1);

// Configuration de la connexion à la base de données
$dbHost = 'database';   // l'adresse de votre serveur MySQL (container docker)
$dbName = 'pitcrew';    // le nom de votre base de données
$dbUser = 'root';       // votre nom d'utilisateur MySQL
$dbPass = 'azerty-26';           // votre mot de passe MySQL

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connexion à la base de données réussie.\n";

    // 1. Compter les hashtags utilisés dans les posts
    $stmt = $pdo->prepare('
        SELECT h.id, h.name, COUNT(ph.post_id) as usage_count
        FROM hashtag h
        LEFT JOIN post_hashtag ph ON h.id = ph.hashtag_id
        GROUP BY h.id, h.name
        ORDER BY usage_count DESC
    ');
    $stmt->execute();
    $hashtags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo 'Hashtags trouvés: ' . \count($hashtags) . "\n";

    // 2. Mettre à jour les compteurs d'utilisation dans la table hashtag
    $updateStmt = $pdo->prepare('UPDATE hashtag SET usage_count = :count WHERE id = :id');

    foreach ($hashtags as $hashtag) {
        $updateStmt->execute([
            'count' => $hashtag['usage_count'],
            'id'    => $hashtag['id'],
        ]);
        echo "Hashtag #{$hashtag['name']} mis à jour avec {$hashtag['usage_count']} utilisation(s).\n";
    }

    // Vérifier les hashtags après la mise à jour
    $checkStmt       = $pdo->query('SELECT id, name, usage_count FROM hashtag ORDER BY usage_count DESC');
    $updatedHashtags = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nÉtat des hashtags après mise à jour :\n";
    echo "------------------------------------\n";
    foreach ($updatedHashtags as $tag) {
        echo "#{$tag['name']} : {$tag['usage_count']} utilisation(s)\n";
    }
    echo "------------------------------------\n";

    echo "Mise à jour des hashtags terminée avec succès.\n";
} catch (PDOException $e) {
    echo 'Erreur de connexion à la base de données: ' . $e->getMessage() . "\n";
    exit(1);
}
