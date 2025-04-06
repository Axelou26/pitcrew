# Guide de Déploiement PitCrew

Ce document décrit le processus de déploiement de l'application PitCrew.

## Prérequis

- PHP 8.2 ou supérieur
- Composer
- Node.js et NPM
- MySQL 8.0
- Git
- Accès SSH au serveur de production
- Clés SSH configurées pour GitHub et le serveur de production

## Configuration Initiale

1. Configurer les secrets GitHub :
   - `DEPLOY_SSH_KEY`: Votre clé SSH privée pour le déploiement
   - `DATABASE_URL`: URL de connexion à la base de données de production
   - `APP_SECRET`: Clé secrète Symfony pour la production

2. Sur le serveur de production :
   ```bash
   # Créer le répertoire de déploiement
   mkdir -p /var/www/pitcrew
   
   # Configurer les permissions
   chown -R www-data:www-data /var/www/pitcrew
   ```

3. Configurer le fichier `.env.local` sur le serveur de production avec les variables d'environnement appropriées.

## Pipeline CI/CD

Notre pipeline CI/CD est configuré avec GitHub Actions et comprend :

### Phase de Test (Automatique)
- Exécution des tests unitaires et fonctionnels
- Analyse statique du code avec PHPStan
- Génération des rapports de couverture de code
- Vérification de la qualité du code

### Phase de Déploiement (Automatique sur main)
1. Construction des assets
2. Installation des dépendances de production
3. Déploiement via Deployer

## Déploiement Manuel

Si nécessaire, vous pouvez déployer manuellement avec :

```bash
dep deploy production
```

## Surveillance du Déploiement

1. Vérifier les logs GitHub Actions
2. Surveiller les logs du serveur :
   ```bash
   tail -f /var/www/pitcrew/shared/var/log/prod.log
   ```

## Rollback

En cas de problème, effectuer un rollback :

```bash
dep rollback production
```

## Monitoring

- Surveillance des performances : New Relic
- Logs : ELK Stack
- Uptime : UptimeRobot
- Erreurs : Sentry

## Maintenance

### Base de données
- Les migrations sont exécutées automatiquement pendant le déploiement
- Backup quotidien configuré via cron

### Cache
- Le cache est automatiquement vidé et réchauffé pendant le déploiement
- Pour vider manuellement : `dep cache:clear production`

## Sécurité

- Les fichiers sensibles sont exclus du dépôt
- Les variables d'environnement sont gérées via GitHub Secrets
- HTTPS forcé en production
- Headers de sécurité configurés dans le serveur web 