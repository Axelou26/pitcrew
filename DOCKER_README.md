# 🐳 Configuration Docker pour PitCrew

## 🚀 Démarrage Rapide

### Prérequis
- Docker et Docker Compose installés
- Au moins 4GB de RAM disponible
- Ports 8888, 8080, 8025, 6379, 33306 disponibles

### Démarrage de l'application
```bash
# Démarrer tous les services
./docker-start.sh start

# Ou avec docker-compose directement
docker-compose up -d
```

## 📋 Services Disponibles

### 🌐 Application Web
- **URL** : http://localhost:8888
- **Service** : Nginx + PHP-FPM
- **Port** : 8888

### 🗄️ Base de Données
- **Service** : MySQL 8.0
- **Port** : 33306
- **Base de données** : pitcrew
- **Utilisateur** : root
- **Mot de passe** : azerty-26

### 🎛️ PhpMyAdmin
- **URL** : http://localhost:8080
- **Port** : 8080
- **Hôte** : database
- **Utilisateur** : root
- **Mot de passe** : azerty-26

### 📧 MailHog (Serveur Mail de Test)
- **URL** : http://localhost:8025
- **Port SMTP** : 1025
- **Port Web** : 8025

### 🔴 Redis (Cache)
- **Port** : 6379
- **Utilisation** : Cache Symfony, Sessions

## 🛠️ Commandes Utiles

### Script de Démarrage
```bash
# Afficher l'aide
./docker-start.sh help

# Démarrer les services
./docker-start.sh start

# Arrêter les services
./docker-start.sh stop

# Redémarrer les services
./docker-start.sh restart

# Afficher les logs
./docker-start.sh logs

# Ouvrir un shell dans le conteneur
./docker-start.sh shell

# Reconstruire les images
./docker-start.sh build

# Nettoyer tout
./docker-start.sh clean

# Afficher le statut
./docker-start.sh status
```

### Commandes Docker Compose Directes
```bash
# Démarrer en arrière-plan
docker-compose up -d

# Démarrer avec logs
docker-compose up

# Arrêter
docker-compose down

# Reconstruire
docker-compose build --no-cache

# Afficher les logs
docker-compose logs -f

# Shell dans le conteneur app
docker-compose exec app bash

# Shell dans la base de données
docker-compose exec database mysql -u root -pazerty-26 pitcrew
```

## 🔧 Commandes Symfony

### Dans le Conteneur App
```bash
# Ouvrir un shell
docker-compose exec app bash

# Installer les dépendances
composer install
npm install

# Vider le cache
php bin/console cache:clear

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger les fixtures
php bin/console doctrine:fixtures:load

# Créer un utilisateur admin
php bin/console app:create-admin

# Exécuter les tests
php bin/phpunit
```

## 📊 Optimisations Intégrées

### 🚀 Performance
- **OpCache** activé avec 128MB de mémoire
- **Redis** pour le cache et les sessions
- **Compression Gzip** activée sur Nginx
- **Cache des assets statiques** (1 an)
- **Pool PHP-FPM** optimisé (50 processus max)

### 🔒 Sécurité
- **Headers de sécurité** configurés
- **Protection des fichiers sensibles**
- **Fonctions PHP dangereuses** désactivées
- **Sessions sécurisées** configurées

### 📈 Monitoring
- **Healthchecks** pour tous les services
- **Logs centralisés** pour chaque service
- **Restart automatique** en cas d'échec

## 🗂️ Structure des Volumes

```
volumes:
  mysql-data:     # Données MySQL persistantes
  redis-data:     # Données Redis persistantes
  symfony-cache:  # Cache Symfony
  symfony-logs:   # Logs Symfony
  nginx-logs:     # Logs Nginx
  node-modules:   # Modules Node.js
```

## 🔍 Dépannage

### Problèmes Courants

#### 1. Ports déjà utilisés
```bash
# Vérifier les ports utilisés
netstat -tulpn | grep :8888

# Changer les ports dans docker-compose.yml
ports:
  - "8889:80"  # Au lieu de 8888:80
```

#### 2. Permissions sur les fichiers
```bash
# Corriger les permissions
docker-compose exec app chown -R www-data:www-data var
docker-compose exec app chmod -R 777 var
```

#### 3. Cache corrompu
```bash
# Vider le cache
docker-compose exec app php bin/console cache:clear
docker-compose exec app rm -rf var/cache/*
```

#### 4. Base de données inaccessible
```bash
# Vérifier le statut de la base
docker-compose ps database

# Redémarrer la base
docker-compose restart database

# Vérifier les logs
docker-compose logs database
```

### Logs et Debugging
```bash
# Logs de tous les services
docker-compose logs -f

# Logs d'un service spécifique
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f database

# Logs PHP
docker-compose exec app tail -f var/log/dev.log
```

## 🧹 Nettoyage

### Nettoyage Complet
```bash
# Arrêter et supprimer tout
docker-compose down -v
docker system prune -f
docker volume prune -f
```

### Nettoyage Sélectif
```bash
# Supprimer seulement les conteneurs
docker-compose down

# Supprimer les volumes
docker volume rm pitcrew_mysql-data pitcrew_redis-data

# Nettoyer les images non utilisées
docker image prune -f
```

## 📝 Configuration Avancée

### Variables d'Environnement
```yaml
environment:
  APP_ENV: dev
  APP_SECRET: "votre-secret-ici"
  DATABASE_URL: "mysql://user:pass@host:port/db"
  REDIS_URL: "redis://redis:6379"
```

### Optimisations PHP
```ini
; php.ini optimisations
memory_limit = 512M
opcache.enable = 1
opcache.memory_consumption = 128
```

### Optimisations Nginx
```nginx
# Compression et cache
gzip on;
gzip_comp_level = 6;
expires 1y;
```

## 🎯 Avantages de cette Configuration

### ✅ Performance
- **Multi-stage builds** pour des images plus petites
- **OpCache** pour accélérer PHP
- **Redis** pour le cache et les sessions
- **Compression Gzip** pour réduire la bande passante

### ✅ Sécurité
- **Headers de sécurité** configurés
- **Fonctions dangereuses** désactivées
- **Protection des fichiers sensibles**
- **Sessions sécurisées**

### ✅ Développement
- **Hot reload** pour les assets
- **Logs détaillés** pour le debugging
- **PhpMyAdmin** pour gérer la base
- **MailHog** pour tester les emails

### ✅ Production Ready
- **Healthchecks** pour la surveillance
- **Restart automatique** en cas d'échec
- **Volumes persistants** pour les données
- **Configuration optimisée** pour les performances

---

**🎉 Votre environnement Docker est maintenant optimisé et prêt pour le développement !** 