# 🏎️ PITCREW - Plateforme de Recrutement Sport Automobile

**PITCREW** est une plateforme web innovante spécialisée dans le recrutement du secteur du sport automobile. Elle facilite la mise en relation entre les professionnels du secteur (recruteurs et candidats) en offrant des fonctionnalités avancées de matching intelligent, de gestion de candidatures et de communication intégrée.

## 🚀 Vue d'ensemble

### Mission
Révolutionner le processus de recrutement dans le sport automobile en créant un écosystème numérique complet qui répond aux besoins spécifiques de ce secteur passionnant.

### Technologies

**Stack Technique :**
- **Backend** : Symfony 7.0 avec PHP 8.2+
- **Base de données** : MySQL 8.0 avec Doctrine ORM
- **Cache** : Redis (Predis) pour sessions et données temporaires
- **Frontend** : Twig, JavaScript ES6+, CSS responsive, Vite 5.0
- **Paiements** : Stripe pour les abonnements
- **Vidéoconférence** : Jitsi Meet pour les entretiens
- **Monitoring** : Prometheus, Grafana, AlertManager
- **Tests** : PHPUnit 10.0, PHPStan, PHP CS Fixer, PHPMD
- **CI/CD** : GitHub Actions avec environnements multiples

### Architecture

Le projet suit une **architecture hexagonale** avec les principes **Domain-Driven Design (DDD)** :

- **Couche de Présentation** : Contrôleurs Symfony + Templates Twig
- **Couche Service** : Logique métier et orchestration
- **Couche Données** : Entités Doctrine + Repositories
- **Couche Infrastructure** : Services externes et configuration

### Entités Principales

- **`User`** : Classe de base avec héritage (Single Table Inheritance)
  - **`Applicant`** : Profil candidat avec compétences, expériences, documents
  - **`Recruiter`** : Profil recruteur avec informations entreprise
- **`JobOffer`** : Offres d'emploi avec traits modulaires
- **`Application`** : Candidatures avec statuts et documents
- **`Interview`** : Entretiens avec intégration Jitsi Meet
- **`Post`** : Système de réseau social intégré
- **`Conversation`** : Messagerie privée
- **`Notification`** : Système de notifications temps réel

## 🚀 Installation et Déploiement

### Prérequis Système
- **PHP** : 8.2 ou supérieur
- **MySQL** : 8.0 ou supérieur
- **Redis** : 6.0 ou supérieur
- **Composer** : 2.0 ou supérieur
- **Node.js** : 18+ (pour Vite et les assets frontend)
- **Docker** : 20.10+ et Docker Compose 2.0+

### Installation Locale

1. **Cloner le projet :**
```bash
git clone [URL_DU_REPO]
cd pitcrew
```

2. **Installer les dépendances :**
```bash
composer install
npm install
```

3. **Configurer la base de données dans `.env` :**
```env
DATABASE_URL="mysql://[user]:[password]@127.0.0.1:3306/pitcrew?serverVersion=8.0"
REDIS_URL="redis://127.0.0.1:6379"
```

4. **Créer la base de données et appliquer les migrations :**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

5. **Lancer le serveur de développement :**
```bash
# Symfony CLI
symfony serve

# Ou avec Vite pour les assets
npm run dev
```

## 🐳 Déploiement avec Docker (Recommandé)

### 🚀 Démarrage Rapide

```bash
# Démarrer l'environnement de développement
./manage-environments.sh dev

# Ou utiliser les scripts individuels
./docker-start-dev.sh
```

### 🌍 Environnements Disponibles

- **🔧 Développement** : Port 8888 - `./manage-environments.sh dev`
- **🔧 Pré-production** : Port 8889 - `./manage-environments.sh preprod`
- **🔧 Production** : Ports 80/443 - `./manage-environments.sh prod`

### 📋 Services Docker

| Service | Port | URL | Description |
|---------|------|-----|-------------|
| **Application** | 8888 | http://localhost:8888 | Nginx + PHP-FPM |
| **PhpMyAdmin** | 8080 | http://localhost:8080 | Gestion BDD |
| **MailHog** | 8025 | http://localhost:8025 | Serveur mail test |
| **Redis** | 6379 | - | Cache et sessions |
| **MySQL** | 33306 | - | Base de données |

### 🛠️ Commandes Docker Utiles

```bash
# Gestion des environnements
./manage-environments.sh status    # Statut des services
./manage-environments.sh logs      # Logs en temps réel
./manage-environments.sh clean     # Nettoyer tout

# Commandes Symfony dans Docker
docker-compose exec app php bin/console cache:clear
docker-compose exec app composer install
docker-compose exec app npm install
```

## 🧪 Tests et Qualité

### 🧪 Exécution des Tests

```bash
# Tests complets avec base de données de test
composer test:all

# Tests par catégorie
composer test:unit          # Tests unitaires
composer test:integration   # Tests d'intégration
composer test:functional    # Tests fonctionnels

# Tests avec couverture de code
composer test:coverage      # Génère rapport HTML dans coverage/
```

### 🔍 Qualité du Code

```bash
# Vérification complète de la qualité
composer quality:check

# Outils individuels
composer phpstan           # Analyse statique PHPStan
composer php-cs-fixer      # Standards de code PSR-12
composer phpmd             # Détection de problèmes de design
```

### 🧹 Maintenance et Nettoyage

```bash
# Vérification des fichiers orphelins et doublons
composer cleanup:check

# Commandes Symfony personnalisées
php bin/console app:check-orphaned-files
php bin/console app:check-duplicates
php bin/console app:check-expired-subscriptions

# Scripts de nettoyage
./bin/cleanup.sh           # Linux/Mac
bin\cleanup.bat            # Windows
```

## 📚 Documentation Complète

### 📖 Guides Principaux

- **🐳 Docker** : [`DOCKER_README.md`](DOCKER_README.md) - Configuration Docker complète
- **🌍 Environnements** : [`ENVIRONNEMENTS.md`](ENVIRONNEMENTS.md) - Gestion des environnements
- **🔧 Maintenance** : [`refactoring.md`](refactoring.md) - Guide de maintenance

### 📋 Structure du Projet

```
pitcrew/
├── 📁 src/                    # Code source Symfony
│   ├── 📁 Controller/        # Contrôleurs de l'application
│   ├── 📁 Entity/           # Entités Doctrine
│   ├── 📁 Service/          # Services métier
│   ├── 📁 Repository/       # Repositories Doctrine
│   └── 📁 Form/             # Formulaires Symfony
├── 📁 templates/             # Templates Twig
├── 📁 assets/               # Assets frontend (Vite)
├── 📁 docker/               # Configuration Docker
├── 📁 bin/                  # Scripts utilitaires
├── 📁 tests/                # Tests PHPUnit
└── 📁 config/               # Configuration Symfony
```

## 🔄 Workflow de Développement

### 🌿 Stratégie de Branches

```bash
# 1. Développement
git checkout -b feature/nouvelle-fonctionnalite
# ... développer et tester ...
git push origin feature/nouvelle-fonctionnalite
# Créer PR vers dev

# 2. Pré-production
git checkout -b pré-prod/merge-dev
git push origin pré-prod/merge-dev
# Créer PR vers pré-prod

# 3. Production
git checkout production
git merge pré-prod
git push origin production
# Déploiement automatique via GitHub Actions
```

### 🚀 CI/CD avec GitHub Actions

Le projet inclut des workflows automatisés :
- **Développement** : Déclenché par les PR vers `dev`
- **Pré-production** : Déclenché par les push sur `pré-prod`
- **Production** : Déclenché par les push sur `production`

### 📊 Monitoring et Métriques

```bash
# Health checks
curl http://localhost:8888/health     # Développement
curl http://localhost:8889/health     # Pré-production

# Métriques Prometheus
curl http://localhost:8888/metrics    # Métriques de l'application

# Logs en temps réel
./manage-environments.sh logs
```

## 🛠️ Outils de Développement

### 🔧 Scripts Utilitaires

```bash
# Configuration automatique GitHub
./bin/setup-github-environments.sh

# Optimisation des performances
./bin/optimize.sh              # Linux/Mac
bin\optimize.bat               # Windows

# Tests de performance
./bin/run_tests.sh             # Linux/Mac
bin\run-tests-simple.bat       # Windows
```

### 📦 Gestion des Dépendances

```bash
# Mise à jour des dépendances
composer update
npm update

# Vérification de sécurité
composer audit
npm audit
```

## 🤝 Contribution

### Standards de Code

- Suivre les standards **PSR-12**
- Ajouter des tests pour les nouvelles fonctionnalités
- Documenter les changements importants
- Vérifier la qualité du code avant commit

### Processus de Contribution

1. **Fork le projet**
2. **Créer une branche de fonctionnalité**
3. **Développer et tester localement**
4. **Commiter avec des messages conventionnels**
5. **Créer une Pull Request vers `dev`**

### Messages de Commit

```bash
feat: nouvelle fonctionnalité
fix: correction de bug
docs: mise à jour documentation
style: formatage du code
refactor: refactorisation
test: ajout de tests
chore: tâches de maintenance
```

## 🔒 Sécurité

### Bonnes Pratiques

- ✅ Variables d'environnement sécurisées
- ✅ Protection CSRF activée
- ✅ Validation des entrées utilisateur
- ✅ Headers de sécurité configurés
- ✅ Sessions sécurisées
- ✅ Limites de taux (rate limiting)

### Configuration de Production

```bash
# Configuration SSL/TLS
./manage-environments.sh setup-prod

# Variables d'environnement sécurisées
cp env.prod.example .env.prod
# Modifier .env.prod avec vos vraies valeurs
```

## 📈 Performance et Optimisation

### 🚀 Optimisations Intégrées

- **OpCache** activé avec 128MB de mémoire
- **Redis** pour le cache et les sessions
- **Compression Gzip** sur Nginx
- **Cache des assets statiques** (1 an)
- **Pool PHP-FPM** optimisé

### 📊 Métriques de Performance

```bash
# Tests de performance
./tests/performance/HomepagePerformanceTest.php

# Monitoring en temps réel
# Prometheus + Grafana configurés
```

## 🆘 Support et Dépannage

### 🔍 Problèmes Courants

#### Ports déjà utilisés
```bash
# Vérifier les ports utilisés
netstat -tulpn | grep :8888

# Changer les ports dans docker-compose.yml
```

#### Cache corrompu
```bash
# Vider le cache
php bin/console cache:clear
rm -rf var/cache/*
```

#### Base de données inaccessible
```bash
# Vérifier le statut
docker-compose ps database

# Redémarrer
docker-compose restart database
```

### 📞 Obtenir de l'Aide

1. **Vérifier les logs** : `./manage-environments.sh logs`
2. **Consulter la documentation** : Voir les fichiers `.md`
3. **Vérifier la configuration** : Variables d'environnement
4. **Exécuter les tests** : `composer test:all`

## 📄 Licence

Ce projet est sous licence propriétaire. Tous droits réservés.

---

**🎉 Merci d'utiliser PitCrew ! Pour toute question, consultez la documentation ou créez une issue sur GitHub.**

**🚀 Démarrage rapide : `./manage-environments.sh dev`** 