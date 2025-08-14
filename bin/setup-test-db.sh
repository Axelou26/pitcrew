#!/bin/bash

# Script pour configurer la base de données de test

echo "Configuration de la base de données de test..."

# Créer la base de données de test si elle n'existe pas
php bin/console doctrine:database:create --env=test --if-not-exists

# Supprimer toutes les tables existantes
php bin/console doctrine:schema:drop --env=test --force --full-database

# Créer le schéma de base de données
php bin/console doctrine:schema:create --env=test

# Charger les fixtures de test si elles existent
if [ -d "src/DataFixtures" ]; then
    php bin/console doctrine:fixtures:load --env=test --no-interaction
fi

echo "Base de données de test configurée avec succès!" 