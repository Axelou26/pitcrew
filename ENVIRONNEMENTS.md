# 🚀 Gestion des Environnements PitCrew

Ce document explique comment utiliser les différents environnements de l'application PitCrew.

## 📋 Environnements Disponibles

### 🔧 **Développement** (dev)
- **Ports** : 8888 (app), 8025 (mail), 8080 (phpMyAdmin), 6379 (Redis), 33306 (MySQL)
- **Base de données** : `pitcrew`
- **Cache** : Redis avec optimisations de développement
- **Profiler** : Activé pour le debugging
- **Logs** : Détaillés

### 🔧 **Pré-production** (preprod)
- **Ports** : 8889 (app), 8026 (mail), 8081 (phpMyAdmin), 6380 (Redis), 33307 (MySQL)
- **Base de données** : `pitcrew_preprod`
- **Cache** : Redis avec optimisations intermédiaires
- **Profiler** : Activé pour le debugging
- **Logs** : Détaillés

### 🔧 **Production** (prod)
- **Ports** : 80/443 (app avec SSL)
- **Base de données** : `pitcrew_prod`
- **Cache** : Redis avec optimisations maximales
- **Profiler** : Désactivé
- **Logs** : Optimisés
- **SSL** : Activé

## 🚀 Commandes Rapides

### Script Principal
```bash
# Démarrer un environnement
./manage-environments.sh dev      # Développement
./manage-environments.sh preprod  # Pré-production
./manage-environments.sh prod     # Production

# Gestion
./manage-environments.sh stop     # Arrêter tous les environnements
./manage-environments.sh status   # Voir le statut
./manage-environments.sh clean    # Nettoyer tout
./manage-environments.sh setup-prod # Configurer la production
```

### Scripts Individuels
```bash
# Développement
./docker-start-dev.sh

# Pré-production
./docker-start-preprod.sh

# Production
./docker-start-prod.sh
```

## 🔧 Configuration

### Variables d'Environnement

#### Développement & Pré-production
Les variables sont définies directement dans les fichiers `docker-compose.yml` et `docker-compose.preprod.yml`.

#### Production
1. Copiez le fichier d'exemple :
   ```bash
   cp env.prod.example .env.prod
   ```

2. Modifiez `.env.prod` avec vos vraies valeurs :
   ```bash
   APP_SECRET=votre-secret-production-tres-securise
   DATABASE_URL=mysql://user:password@database:3306/pitcrew_prod
   MYSQL_USER=votre-user
   MYSQL_PASSWORD=votre-password-securise
   MYSQL_ROOT_PASSWORD=votre-root-password
   ```

### Certificats SSL (Production)
```bash
# Configuration automatique
./manage-environments.sh setup-prod

# Ou manuellement
mkdir -p docker/nginx/ssl
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout docker/nginx/ssl/key.pem \
    -out docker/nginx/ssl/cert.pem \
    -subj "/C=FR/ST=France/L=Paris/O=PitCrew/CN=votre-domaine.com"
```

## 📊 Monitoring

### Logs
```bash
# Logs en temps réel
./manage-environments.sh logs         # Développement
./manage-environments.sh logs-preprod # Pré-production
./manage-environments.sh logs-prod    # Production
```

### Statut des Services
```bash
./manage-environments.sh status
```

## 🔄 Workflow de Développement

### 1. Développement Local
```bash
./manage-environments.sh dev
# Travaillez sur http://localhost:8888
```

### 2. Tests en Pré-production
```bash
./manage-environments.sh preprod
# Testez sur http://localhost:8889
```

### 3. Déploiement en Production
```bash
# Configuration initiale (une seule fois)
./manage-environments.sh setup-prod

# Démarrage
./manage-environments.sh prod
# Application disponible sur https://localhost
```

## 🛠️ Maintenance

### Nettoyage Complet
```bash
./manage-environments.sh clean
```

### Mise à Jour des Images
```bash
docker-compose pull
docker-compose -f docker-compose.preprod.yml pull
docker-compose -f docker-compose.prod.yml pull
```

### Sauvegarde des Données
```bash
# Sauvegarde de la base de données
docker exec pitcrew_database_1 mysqldump -u root -p pitcrew > backup_dev.sql
docker exec pitcrew_database_1 mysqldump -u root -p pitcrew_preprod > backup_preprod.sql
docker exec pitcrew_database_1 mysqldump -u root -p pitcrew_prod > backup_prod.sql
```

## 🔒 Sécurité

### Production
- ✅ SSL/TLS activé
- ✅ Headers de sécurité configurés
- ✅ Variables d'environnement sécurisées
- ✅ Limites de ressources Docker
- ✅ Logs optimisés

### Recommandations
- Changez tous les mots de passe par défaut
- Utilisez des certificats SSL valides en production
- Configurez un firewall
- Surveillez les logs régulièrement

## 🆘 Dépannage

### Problèmes Courants

#### Ports déjà utilisés
```bash
# Vérifier les ports utilisés
netstat -tulpn | grep :8888
netstat -tulpn | grep :8889

# Arrêter les services conflictuels
sudo systemctl stop apache2  # Si Apache utilise le port 80
```

#### Conteneurs qui ne démarrent pas
```bash
# Vérifier les logs
docker-compose logs app

# Redémarrer
docker-compose restart app
```

#### Base de données inaccessible
```bash
# Vérifier la santé
docker-compose ps database

# Redémarrer la base
docker-compose restart database
```

## 📞 Support

Pour toute question ou problème :
1. Vérifiez les logs : `./manage-environments.sh logs`
2. Consultez ce document
3. Vérifiez la configuration des variables d'environnement 