@echo off
echo Configuration de la base de données de test...

REM Créer la base de données de test si elle n'existe pas
php bin/console doctrine:database:create --env=test --if-not-exists

REM Supprimer toutes les tables existantes
php bin/console doctrine:schema:drop --env=test --force --full-database

REM Créer le schéma de base de données
php bin/console doctrine:schema:create --env=test

REM Charger les fixtures de test si elles existent
if exist "src\DataFixtures" (
    php bin/console doctrine:fixtures:load --env=test --no-interaction
)

echo Base de données de test configurée avec succès! 