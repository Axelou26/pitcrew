# PITCREW - Plateforme de Recrutement Sport Automobile

PITCREW est une application web innovante destinée à faciliter la mise en relation entre les professionnels du sport automobile.

## Structure du Projet

Le projet est construit avec Symfony 6.4 et utilise les composants suivants :

### Entités

- `User` : Classe de base abstraite pour les utilisateurs
  - `Recruiter` : Profil recruteur
  - `Applicant` : Profil postulant
- `JobOffer` : Offres d'emploi
- `Application` : Candidatures

### Fonctionnalités Principales

1. **Système d'Authentification**
   - Inscription
   - Connexion
   - Réinitialisation de mot de passe

2. **Gestion des Profils**
   - Profil Recruteur (entreprise)
   - Profil Postulant (candidat)
   - Upload de documents (CV, lettres de recommandation)

3. **Gestion des Offres d'Emploi**
   - Création d'offres
   - Recherche et filtrage
   - Système de favoris

4. **Gestion des Candidatures**
   - Soumission de candidatures
   - Suivi des candidatures
   - Système de statuts (en attente, acceptée, refusée)

## Installation

1. Cloner le projet :
```bash
git clone [URL_DU_REPO]
```

2. Installer les dépendances :
```bash
composer install
```

3. Configurer la base de données dans `.env` :
```
DATABASE_URL="mysql://[user]:[password]@127.0.0.1:3306/pitcrew?serverVersion=8.0"
```

4. Créer la base de données et appliquer les migrations :
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Lancer le serveur de développement :
```bash
symfony serve
```

## Configuration Requise

- PHP 8.1 ou supérieur
- MySQL 8.0 ou supérieur
- Composer
- Symfony CLI

## Contribution

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Créer une Pull Request 