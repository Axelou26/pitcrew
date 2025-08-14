# 🚀 Configuration des Environnements GitHub Actions

Ce document explique comment configurer les environnements de déploiement sur GitHub pour votre application PitCrew.

## 📋 Environnements Configurés

### 🔧 **Développement** (development)
- **Déclencheur** : Pull Requests sur `dev`
- **URL** : http://localhost:8888
- **Base de données** : `pitcrew_dev`
- **Cache** : Redis avec optimisations de développement
- **Profiler** : Activé
- **Logs** : Détaillés

### 🔧 **Pré-production** (pré-production)
- **Déclencheur** : Push sur `pré-prod` ou `preprod`
- **URL** : http://localhost:8889
- **Base de données** : `pitcrew_preprod`
- **Cache** : Redis avec optimisations intermédiaires
- **Profiler** : Activé
- **Logs** : Détaillés

### 🔧 **Production** (production)
- **Déclencheur** : Push sur `production` ou `master`
- **URL** : https://pitcrew.com
- **Base de données** : `pitcrew_prod`
- **Cache** : Redis avec optimisations maximales
- **Profiler** : Désactivé
- **Logs** : Optimisés
- **SSL** : Activé

## 🔧 Configuration des Secrets GitHub

### Secrets Requis

#### Docker Hub
```
DOCKER_HUB_USERNAME=votre-username-dockerhub
DOCKER_HUB_ACCESS_TOKEN=votre-token-dockerhub
```

#### Environnement Développement
```
APP_SECRET_DEV=votre-secret-dev
DATABASE_URL_DEV=mysql://user:password@database:3306/pitcrew_dev
```

#### Environnement Pré-production
```
APP_SECRET_PREPROD=votre-secret-preprod
DATABASE_URL_PREPROD=mysql://user:password@database:3306/pitcrew_preprod
```

#### Environnement Production
```
APP_SECRET_PROD=votre-secret-prod-tres-securise
DATABASE_URL_PROD=mysql://user:password@database:3306/pitcrew_prod
```

#### Notifications
```
SLACK_WEBHOOK=https://hooks.slack.com/services/votre-webhook
```

## 🚀 Workflows GitHub Actions

### 1. Déploiement Développement (`deploy-dev.yml`)
- **Déclencheur** : Pull Requests sur `dev`
- **Actions** :
  - Tests et qualité du code
  - Build de l'image Docker avec tag `dev`
  - Déploiement sur l'environnement de développement
  - Notification Slack

### 2. Déploiement Pré-production (`deploy-preprod.yml`)
- **Déclencheur** : Push sur `pré-prod` ou `preprod`
- **Actions** :
  - Tests et qualité du code
  - Build de l'image Docker avec tag `preprod`
  - Déploiement sur l'environnement de pré-production
  - Notification Slack

### 3. Déploiement Production (`deploy.yml`)
- **Déclencheur** : Push sur `production` ou `master`
- **Actions** :
  - Tests et qualité du code
  - Build de l'image Docker avec tag `prod` et `latest`
  - Déploiement sur l'environnement de production
  - Notification Slack

## 🔄 Workflow de Développement

### 1. Développement Local
```bash
# Créer une branche de développement
git checkout -b feature/nouvelle-fonctionnalite

# Développer et tester localement
./manage-environments.sh dev

# Commiter et pousser
git add .
git commit -m "feat: nouvelle fonctionnalité"
git push origin feature/nouvelle-fonctionnalite

# Créer une Pull Request vers dev
# Le workflow deploy-dev.yml se déclenche automatiquement
```

### 2. Tests en Pré-production
```bash
# Une fois la PR mergée sur dev, créer une PR vers pré-prod
git checkout dev
git pull origin dev
git checkout -b pré-prod/merge-dev
git push origin pré-prod/merge-dev

# Créer une Pull Request vers pré-prod
# Le workflow deploy-preprod.yml se déclenche automatiquement
```

### 3. Déploiement en Production
```bash
# Une fois les tests en pré-production validés
git checkout pré-prod
git pull origin pré-prod
git checkout production
git merge pré-prod
git push origin production

# Le workflow deploy.yml se déclenche automatiquement
```

## 🛠️ Configuration des Environnements GitHub

### 1. Aller dans les Settings du Repository
```
Settings > Environments
```

### 2. Créer les Environnements

#### Development
- **Name** : `development`
- **URL** : `http://localhost:8888`
- **Protection rules** : Aucune (pour les PR)

#### Pre-production
- **Name** : `pre-production`
- **URL** : `http://localhost:8889`
- **Protection rules** : 
  - Required reviewers: 1
  - Wait timer: 0 minutes

#### Production
- **Name** : `production`
- **URL** : `https://pitcrew.com`
- **Protection rules** :
  - Required reviewers: 2
  - Wait timer: 5 minutes
  - Deployment branches: `production`, `master`

### 3. Ajouter les Secrets

Pour chaque environnement, ajouter les secrets correspondants dans la section "Environment secrets".

## 📊 Monitoring et Notifications

### Slack Notifications
- 🟢 **Développement** : Déploiement réussi
- 🟡 **Pré-production** : Déploiement réussi
- 🟢 **Production** : Déploiement réussi
- 🔴 **Tous** : Échec de déploiement

### Health Checks
Chaque environnement vérifie :
- `/health` endpoint
- `/api/health` endpoint
- Base de données accessible
- Cache Redis fonctionnel

## 🔒 Sécurité

### Production
- ✅ Environnement protégé avec reviewers requis
- ✅ Wait timer de 5 minutes
- ✅ Secrets chiffrés
- ✅ SSL/TLS obligatoire
- ✅ Logs de sécurité

### Recommandations
- Changez tous les mots de passe par défaut
- Utilisez des certificats SSL valides
- Surveillez les logs de déploiement
- Testez les rollbacks régulièrement

## 🆘 Dépannage

### Problèmes Courants

#### Workflow ne se déclenche pas
1. Vérifier les branches dans le trigger
2. Vérifier les permissions du repository
3. Vérifier les secrets configurés

#### Déploiement échoue
1. Vérifier les logs du workflow
2. Vérifier la connectivité Docker Hub
3. Vérifier les variables d'environnement

#### Health check échoue
1. Vérifier que l'application démarre
2. Vérifier la base de données
3. Vérifier les endpoints de santé

## 📞 Support

Pour toute question ou problème :
1. Vérifier les logs du workflow GitHub Actions
2. Consulter ce document
3. Vérifier la configuration des secrets
4. Contacter l'équipe DevOps 