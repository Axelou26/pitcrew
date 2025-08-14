-- Script d'initialisation de la base de données PitCrew
-- Ce script s'exécute automatiquement lors du premier démarrage du conteneur MySQL

-- Création de la base de données si elle n'existe pas
CREATE DATABASE IF NOT EXISTS pitcrew CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Utilisation de la base de données
USE pitcrew;

-- Configuration des variables de session pour la compatibilité
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- Affichage d'un message de confirmation
SELECT 'Base de données PitCrew initialisée avec succès !' AS message; 