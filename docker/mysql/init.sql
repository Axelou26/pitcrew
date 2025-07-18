-- Script d'initialisation de la base de données PitCrew
-- Ce script s'exécute automatiquement lors du premier démarrage du conteneur MySQL

-- Création de la base de données si elle n'existe pas
CREATE DATABASE IF NOT EXISTS pitcrew CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Utilisation de la base de données
USE pitcrew;

-- Configuration des variables globales pour de meilleures performances
SET GLOBAL innodb_buffer_pool_size = 256M;
SET GLOBAL innodb_log_file_size = 64M;
SET GLOBAL innodb_flush_log_at_trx_commit = 2;
SET GLOBAL innodb_flush_method = 'O_DIRECT';
SET GLOBAL innodb_file_per_table = 1;
SET GLOBAL innodb_stats_on_metadata = 0;

-- Configuration des variables de session
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- Création d'un utilisateur dédié pour l'application (optionnel)
-- CREATE USER IF NOT EXISTS 'pitcrew_user'@'%' IDENTIFIED BY 'pitcrew_password';
-- GRANT ALL PRIVILEGES ON pitcrew.* TO 'pitcrew_user'@'%';
-- FLUSH PRIVILEGES;

-- Affichage d'un message de confirmation
SELECT 'Base de données PitCrew initialisée avec succès !' AS message; 