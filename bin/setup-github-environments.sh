#!/bin/bash

# 🚀 Script de Configuration des Environnements GitHub Actions
# Ce script aide à configurer les environnements de déploiement sur GitHub

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
print_message() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Fonction pour vérifier si une commande existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Vérifier les prérequis
check_prerequisites() {
    print_message "Vérification des prérequis..."
    
    if ! command_exists git; then
        print_error "Git n'est pas installé"
        exit 1
    fi
    
    if ! command_exists docker; then
        print_error "Docker n'est pas installé"
        exit 1
    fi
    
    if ! command_exists docker-compose; then
        print_error "Docker Compose n'est pas installé"
        exit 1
    fi
    
    print_success "Tous les prérequis sont satisfaits"
}

# Afficher les informations de configuration
show_configuration_info() {
    echo
    print_message "Configuration des Environnements GitHub Actions"
    echo "=================================================="
    echo
    echo "📋 Environnements à configurer :"
    echo "  🔧 Développement (development)"
    echo "  🔧 Pré-production (pre-production)"
    echo "  🔧 Production (production)"
    echo
    echo "🔧 Secrets requis :"
    echo "  DOCKER_HUB_USERNAME"
    echo "  DOCKER_HUB_ACCESS_TOKEN"
    echo "  APP_SECRET_DEV"
    echo "  DATABASE_URL_DEV"
    echo "  APP_SECRET_PREPROD"
    echo "  DATABASE_URL_PREPROD"
    echo "  APP_SECRET_PROD"
    echo "  DATABASE_URL_PROD"
    echo "  SLACK_WEBHOOK"
    echo
}

# Générer les secrets d'exemple
generate_secrets_example() {
    print_message "Génération des secrets d'exemple..."
    
    cat > .github/secrets.example << EOL
# 🔐 Secrets GitHub Actions - Exemple
# Copiez ce fichier vers .github/secrets.env et modifiez les valeurs

# Docker Hub
DOCKER_HUB_USERNAME=votre-username-dockerhub
DOCKER_HUB_ACCESS_TOKEN=votre-token-dockerhub

# Environnement Développement
APP_SECRET_DEV=\$(openssl rand -hex 32)
DATABASE_URL_DEV=mysql://pitcrew_dev:password@database:3306/pitcrew_dev

# Environnement Pré-production
APP_SECRET_PREPROD=\$(openssl rand -hex 32)
DATABASE_URL_PREPROD=mysql://pitcrew_preprod:password@database:3306/pitcrew_preprod

# Environnement Production
APP_SECRET_PROD=\$(openssl rand -hex 32)
DATABASE_URL_PROD=mysql://pitcrew_prod:password@database:3306/pitcrew_prod

# Notifications Slack
SLACK_WEBHOOK=https://hooks.slack.com/services/votre-webhook
EOL
    
    print_success "Fichier .github/secrets.example créé"
}

# Vérifier la structure des branches
check_branch_structure() {
    print_message "Vérification de la structure des branches..."
    
    local branches=("main" "master" "develop" "dev" "staging" "preprod")
    local missing_branches=()
    
    for branch in "${branches[@]}"; do
        if ! git show-ref --verify --quiet refs/remotes/origin/$branch; then
            missing_branches+=("$branch")
        fi
    done
    
    if [ ${#missing_branches[@]} -gt 0 ]; then
        print_warning "Branches manquantes : ${missing_branches[*]}"
        echo
        echo "Pour créer les branches manquantes :"
        for branch in "${missing_branches[@]}"; do
            echo "  git checkout -b $branch"
            echo "  git push origin $branch"
        done
    else
        print_success "Toutes les branches requises existent"
    fi
}

# Vérifier les workflows GitHub Actions
check_workflows() {
    print_message "Vérification des workflows GitHub Actions..."
    
    local workflows=("deploy-dev.yml" "deploy-preprod.yml" "deploy.yml")
    local missing_workflows=()
    
    for workflow in "${workflows[@]}"; do
        if [ ! -f ".github/workflows/$workflow" ]; then
            missing_workflows+=("$workflow")
        fi
    done
    
    if [ ${#missing_workflows[@]} -gt 0 ]; then
        print_error "Workflows manquants : ${missing_workflows[*]}"
        exit 1
    else
        print_success "Tous les workflows sont présents"
    fi
}

# Afficher les instructions de configuration
show_setup_instructions() {
    echo
    print_message "Instructions de Configuration"
    echo "================================"
    echo
    echo "1. 📝 Configurer les Secrets GitHub :"
    echo "   - Allez dans Settings > Secrets and variables > Actions"
    echo "   - Ajoutez tous les secrets listés ci-dessus"
    echo
    echo "2. 🛠️ Configurer les Environnements :"
    echo "   - Allez dans Settings > Environments"
    echo "   - Créez les environnements : development, pre-production, production"
    echo
    echo "3. 🔒 Protection des Environnements :"
    echo "   - Development : Aucune protection (pour les PR)"
    echo "   - Pre-production : 1 reviewer requis"
    echo "   - Production : 2 reviewers requis, wait timer 5min"
    echo
    echo "4. 🚀 Test des Workflows :"
    echo "   - Créez une PR vers develop pour tester le workflow dev"
    echo "   - Créez une PR vers staging pour tester le workflow preprod"
    echo "   - Mergez sur main pour tester le workflow prod"
    echo
}

# Afficher les commandes utiles
show_useful_commands() {
    echo
    print_message "Commandes Utiles"
    echo "=================="
    echo
    echo "🔧 Gestion des environnements locaux :"
    echo "  ./manage-environments.sh dev      # Démarrage dev"
    echo "  ./manage-environments.sh preprod  # Démarrage preprod"
    echo "  ./manage-environments.sh prod     # Démarrage prod"
    echo
    echo "📊 Monitoring :"
    echo "  ./manage-environments.sh status   # Statut des environnements"
    echo "  ./manage-environments.sh logs     # Logs en temps réel"
    echo
    echo "🧹 Nettoyage :"
    echo "  ./manage-environments.sh clean    # Nettoyer tout"
    echo "  ./manage-environments.sh stop     # Arrêter tout"
    echo
    echo "🔍 Debug :"
    echo "  docker compose logs app           # Logs de l'application"
    echo "  docker compose logs database      # Logs de la base de données"
    echo "  docker compose exec app php bin/console debug:container"
    echo
}

# Fonction principale
main() {
    echo "🚀 Configuration des Environnements GitHub Actions"
    echo "================================================"
    echo
    
    check_prerequisites
    show_configuration_info
    generate_secrets_example
    check_branch_structure
    check_workflows
    show_setup_instructions
    show_useful_commands
    
    echo
    print_success "Configuration terminée !"
    echo
    echo "📚 Documentation :"
    echo "  - .github/environments.md"
    echo "  - ENVIRONNEMENTS.md"
    echo "  - DOCKER_README.md"
    echo
    print_warning "N'oubliez pas de configurer les secrets GitHub !"
}

# Exécuter le script principal
main "$@" 