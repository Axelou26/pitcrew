# 🌿 Stratégie de Branches pour les Environnements

Ce document explique la stratégie de branches utilisée pour gérer les différents environnements de déploiement.

## 📋 Structure des Branches

### 🔧 **Branches Principales**

#### `production` / `master`
- **Environnement** : Production
- **Déclencheur** : Déploiement automatique en production
- **Protection** : Requiert 2 reviewers, wait timer 5min
- **URL** : https://pitcrew.com

#### `pre-prod` / `preprod`
- **Environnement** : Pré-production
- **Déclencheur** : Déploiement automatique en pré-production
- **Protection** : Requiert 1 reviewer
- **URL** : http://localhost:8889

#### `dev`
- **Environnement** : Développement
- **Déclencheur** : Déploiement automatique en développement (via PR)
- **Protection** : Aucune (pour permettre les PR)
- **URL** : http://localhost:8888

### 🔧 **Branches de Fonctionnalités**

#### `feature/*`
- **Exemple** : `feature/user-authentication`
- **Objectif** : Développement de nouvelles fonctionnalités
- **Workflow** : Tests automatiques, pas de déploiement

#### `bugfix/*`
- **Exemple** : `bugfix/login-error`
- **Objectif** : Correction de bugs
- **Workflow** : Tests automatiques, pas de déploiement

#### `hotfix/*`
- **Exemple** : `hotfix/security-patch`
- **Objectif** : Corrections urgentes pour la production
- **Workflow** : Tests automatiques, déploiement direct en production

## 🔄 Workflow de Développement

### 1. Développement de Fonctionnalités

```bash
# 1. Créer une branche de fonctionnalité
git checkout dev
git pull origin dev
git checkout -b feature/nouvelle-fonctionnalite

# 2. Développer et tester localement
./manage-environments.sh dev

# 3. Commiter et pousser
git add .
git commit -m "feat: nouvelle fonctionnalité"
git push origin feature/nouvelle-fonctionnalite

# 4. Créer une Pull Request vers dev
# Le workflow deploy-dev.yml se déclenche automatiquement
```

### 2. Tests en Pré-production

```bash
# 1. Une fois la PR mergée sur develop
git checkout dev
git pull origin dev

# 2. Créer une branche pour la pré-production
git checkout -b pre-prod/merge-dev
git push origin pre-prod/merge-dev

# 3. Créer une Pull Request vers pre-prod
# Le workflow deploy-preprod.yml se déclenche automatiquement
```

### 3. Déploiement en Production

```bash
# 1. Une fois les tests en pré-production validés
git checkout pre-prod
git pull origin pre-prod

# 2. Merger vers production
git checkout production
git merge pre-prod
git push origin production

# 3. Le workflow deploy.yml se déclenche automatiquement
```

## 🚨 Hotfixes

### Pour les Corrections Urgentes

```bash
# 1. Créer une branche hotfix depuis production
git checkout production
git pull origin production
git checkout -b hotfix/correction-urgente

# 2. Corriger le problème
# ... faire les corrections ...

# 3. Commiter et pousser
git add .
git commit -m "hotfix: correction urgente"
git push origin hotfix/correction-urgente

# 4. Créer une Pull Request vers production
# Le workflow deploy.yml se déclenche automatiquement
```

## 🔒 Protection des Branches

### Configuration Recommandée

#### `production` / `master`
- ✅ Require a pull request before merging
- ✅ Require approvals: 2
- ✅ Dismiss stale PR approvals when new commits are pushed
- ✅ Require status checks to pass before merging
- ✅ Require branches to be up to date before merging
- ✅ Include administrators
- ✅ Restrict pushes that create files that override the gitignore
- ✅ Allow force pushes: Disabled
- ✅ Allow deletions: Disabled

#### `pre-prod` / `preprod`
- ✅ Require a pull request before merging
- ✅ Require approvals: 1
- ✅ Dismiss stale PR approvals when new commits are pushed
- ✅ Require status checks to pass before merging
- ✅ Require branches to be up to date before merging
- ✅ Include administrators
- ✅ Restrict pushes that create files that override the gitignore
- ✅ Allow force pushes: Disabled
- ✅ Allow deletions: Disabled

#### `dev`
- ✅ Require a pull request before merging
- ✅ Require approvals: 1
- ✅ Dismiss stale PR approvals when new commits are pushed
- ✅ Require status checks to pass before merging
- ✅ Require branches to be up to date before merging
- ✅ Include administrators
- ✅ Restrict pushes that create files that override the gitignore
- ✅ Allow force pushes: Disabled
- ✅ Allow deletions: Disabled

## 📊 Monitoring des Branches

### Commandes Utiles

```bash
# Voir toutes les branches
git branch -a

# Voir les branches locales
git branch

# Voir les branches distantes
git branch -r

# Voir le statut des branches
git status

# Voir l'historique des branches
git log --oneline --graph --all

# Nettoyer les branches locales supprimées
git remote prune origin

# Supprimer une branche locale
git branch -d nom-de-la-branche

# Supprimer une branche distante
git push origin --delete nom-de-la-branche
```

## 🛠️ Configuration Git

### Alias Utiles

```bash
# Ajouter ces alias à votre .gitconfig
[alias]
    st = status
    co = checkout
    br = branch
    ci = commit
    unstage = reset HEAD --
    last = log -1 HEAD
    visual = !gitk
    lg = log --oneline --graph --all
    branches = branch -a
    remotes = remote -v
```

### Hooks Git (Optionnel)

Créer `.git/hooks/pre-commit` pour vérifications automatiques :

```bash
#!/bin/bash
# Vérifications avant commit
echo "Running pre-commit checks..."

# Vérifier la syntaxe PHP
find src/ -name "*.php" -exec php -l {} \;

# Vérifier les standards de code
vendor/bin/phpcs --standard=PSR12 src/

echo "Pre-commit checks completed."
```

## 📋 Checklist de Déploiement

### Avant de Merger vers Production

- [ ] Tous les tests passent
- [ ] Code review approuvée par 2 reviewers
- [ ] Tests en pré-production validés
- [ ] Documentation mise à jour
- [ ] Changelog mis à jour
- [ ] Variables d'environnement configurées
- [ ] Base de données migrée
- [ ] Cache vidé
- [ ] Monitoring configuré

### En Cas de Problème

1. **Rollback immédiat** : Revenir à la version précédente
2. **Investigation** : Analyser les logs et métriques
3. **Hotfix** : Corriger le problème
4. **Communication** : Informer l'équipe et les utilisateurs
5. **Post-mortem** : Documenter les leçons apprises

## 📞 Support

Pour toute question sur la stratégie de branches :
1. Consulter ce document
2. Vérifier la documentation GitHub
3. Contacter l'équipe DevOps
4. Consulter les logs de déploiement 