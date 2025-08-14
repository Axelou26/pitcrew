<?php

// Script pour nettoyer définitivement les doublons d'abonnements

// Charger l'autoloader de Symfony
require __DIR__ . '/vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = new Symfony\Component\Dotenv\Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

// Créer la connexion à la base de données - utiliser localhost pour l'exécution locale
$connectionParams = [
    'dbname' => 'blog',
    'user' => 'root',
    'password' => 'azerty-26',
    'host' => 'localhost', // Utiliser localhost au lieu de 'database' pour l'exécution locale
    'driver' => 'pdo_mysql',
];

echo "=== Nettoyage des abonnements ===\n\n";

try {
    $conn = Doctrine\DBAL\DriverManager::getConnection($connectionParams);
    echo "Connexion à la base de données réussie.\n\n";
    
    // 1. Supprimer tous les abonnements existants
    echo "Suppression de tous les abonnements existants...\n";
    
    // D'abord récupérer les informations sur les abonnements actuels des recruteurs
    $sql = "SELECT rs.id, rs.recruiter_id, s.name, rs.start_date, rs.end_date, rs.is_active, rs.remaining_job_offers 
            FROM recruiter_subscription rs 
            JOIN subscription s ON rs.subscription_id = s.id";
    $activeSubscriptions = $conn->fetchAllAssociative($sql);
    
    echo "Sauvegarde des " . count($activeSubscriptions) . " abonnements recruteurs existants...\n";
    
    // Suppression des abonnements recruteurs
    $conn->executeStatement("DELETE FROM recruiter_subscription");
    echo "Abonnements recruteurs supprimés.\n";
    
    // Suppression des abonnements
    $conn->executeStatement("DELETE FROM subscription");
    echo "Abonnements supprimés.\n";
    
    // Réinitialiser l'auto-increment
    $conn->executeStatement("ALTER TABLE subscription AUTO_INCREMENT = 1");
    echo "Auto-increment réinitialisé.\n\n";
    
    // 2. Créer les 3 abonnements standards
    echo "Création des abonnements standards...\n";
    
    $subscriptions = [
        'Basic' => [
            'price' => 0,
            'duration' => 30,
            'max_job_offers' => 3,
            'features' => '["post_job_offer", "basic_applications", "limited_messaging", "standard_profile"]'
        ],
        'Premium' => [
            'price' => 49,
            'duration' => 30,
            'max_job_offers' => null,
            'features' => '["post_job_offer", "unlimited_job_offers", "highlighted_offers", "full_cv_access", "unlimited_messaging", "basic_statistics", "enhanced_profile"]'
        ],
        'Business' => [
            'price' => 99,
            'duration' => 30,
            'max_job_offers' => null,
            'features' => '["post_job_offer", "unlimited_job_offers", "advanced_candidate_search", "automatic_recommendations", "detailed_statistics", "verified_badge", "priority_support"]'
        ]
    ];
    
    $subscriptionIds = [];
    
    foreach ($subscriptions as $name => $data) {
        $conn->executeStatement(
            "INSERT INTO subscription (name, price, duration, max_job_offers, features, is_active) VALUES (?, ?, ?, ?, ?, 1)",
            [$name, $data['price'], $data['duration'], $data['max_job_offers'], $data['features']]
        );
        
        $subscriptionIds[$name] = $conn->lastInsertId();
        echo "Abonnement {$name} créé avec l'ID {$subscriptionIds[$name]}.\n";
    }
    
    echo "\n";
    
    // 3. Recréer les abonnements recruteurs
    echo "Recréation des abonnements recruteurs...\n";
    
    foreach ($activeSubscriptions as $sub) {
        $name = ucfirst(strtolower($sub['name'])); // Normaliser le nom (Basic, Premium ou Business)
        
        if (!isset($subscriptionIds[$name])) {
            echo "AVERTISSEMENT: Type d'abonnement inconnu '{$sub['name']}' pour l'abonnement recruteur #{$sub['id']}. Assignation à Basic.\n";
            $name = 'Basic';
        }
        
        $newSubscriptionId = $subscriptionIds[$name];
        
        // Vérifier la structure de la table recruiter_subscription
        $tableColumns = $conn->fetchAllAssociative("DESCRIBE recruiter_subscription");
        $hasCreatedAt = false;
        $hasUpdatedAt = false;
        foreach ($tableColumns as $column) {
            if ($column['Field'] === 'created_at') $hasCreatedAt = true;
            if ($column['Field'] === 'updated_at') $hasUpdatedAt = true;
        }
        
        // Construire la requête SQL en fonction des colonnes existantes
        $fields = "recruiter_id, subscription_id, start_date, end_date, is_active, remaining_job_offers";
        $values = "?, ?, ?, ?, ?, ?";
        $params = [$sub['recruiter_id'], $newSubscriptionId, $sub['start_date'], $sub['end_date'], $sub['is_active'], $sub['remaining_job_offers']];
        
        if ($hasCreatedAt) {
            $fields .= ", created_at";
            $values .= ", NOW()";
        }
        
        if ($hasUpdatedAt) {
            $fields .= ", updated_at";
            $values .= ", NOW()";
        }
        
        $sql = "INSERT INTO recruiter_subscription ($fields) VALUES ($values)";
        $conn->executeStatement($sql, $params);
        
        echo "Abonnement recruteur #{$sub['id']} recréé avec le nouvel abonnement '{$name}'.\n";
    }
    
    echo "\nNettoyage terminé avec succès!\n";
    echo "3 abonnements standards ont été créés et " . count($activeSubscriptions) . " abonnements recruteurs ont été recréés.\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
