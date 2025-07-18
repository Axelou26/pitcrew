#!/bin/bash

# Script de démarrage Docker pour PitCrew
# Usage: ./docker-start.sh [start|stop|restart|logs|shell|build|clean]

set -e

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
print_message() {
    echo -e "${GREEN}[PitCrew]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[PitCrew]${NC} $1"
}

print_error() {
    echo -e "${RED}[PitCrew]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[PitCrew]${NC} $1"
}

# Fonction d'aide
show_help() {
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commandes disponibles:"
    echo "  start     - Démarrer tous les services"
    echo "  stop      - Arrêter tous les services"
    echo "  restart   - Redémarrer tous les services"
    echo "  logs      - Afficher les logs"
    echo "  shell     - Ouvrir un shell dans le conteneur app"
    echo "  build     - Reconstruire les images Docker"
    echo "  clean     - Nettoyer les conteneurs et volumes"
    echo "  status    - Afficher le statut des services"
    echo "  install   - Installer les dépendances"
    echo "  migrate   - Exécuter les migrations"
    echo "  fixtures  - Charger les fixtures"
    echo "  test      - Exécuter les tests"
    echo "  help      - Afficher cette aide"
}

# Fonction pour démarrer les services
start_services() {
    print_message "Démarrage des services Docker..."
    docker-compose up -d
    print_message "Services démarrés avec succès !"
    print_info "Application accessible sur: http://localhost:8888"
    print_info "PhpMyAdmin accessible sur: http://localhost:8080"
    print_info "MailHog accessible sur: http://localhost:8025"
    print_info "Redis accessible sur: localhost:6379"
}

# Fonction pour arrêter les services
stop_services() {
    print_message "Arrêt des services Docker..."
    docker-compose down
    print_message "Services arrêtés avec succès !"
}

# Fonction pour redémarrer les services
restart_services() {
    print_message "Redémarrage des services Docker..."
    docker-compose down
    docker-compose up -d
    print_message "Services redémarrés avec succès !"
}

# Fonction pour afficher les logs
show_logs() {
    print_message "Affichage des logs..."
    docker-compose logs -f
}

# Fonction pour ouvrir un shell
open_shell() {
    print_message "Ouverture d'un shell dans le conteneur app..."
    docker-compose exec app bash
}

# Fonction pour reconstruire les images
build_images() {
    print_message "Reconstruction des images Docker..."
    docker-compose build --no-cache
    print_message "Images reconstruites avec succès !"
}

# Fonction pour nettoyer
clean_all() {
    print_warning "Nettoyage des conteneurs et volumes..."
    docker-compose down -v
    docker system prune -f
    print_message "Nettoyage terminé !"
}

# Fonction pour afficher le statut
show_status() {
    print_message "Statut des services:"
    docker-compose ps
}

# Fonction pour installer les dépendances
install_dependencies() {
    print_message "Installation des dépendances..."
    docker-compose exec app composer install
    docker-compose exec app npm install
    print_message "Dépendances installées avec succès !"
}

# Fonction pour exécuter les migrations
run_migrations() {
    print_message "Exécution des migrations..."
    docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
    print_message "Migrations exécutées avec succès !"
}

# Fonction pour charger les fixtures
load_fixtures() {
    print_message "Chargement des fixtures..."
    docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction
    print_message "Fixtures chargées avec succès !"
}

# Fonction pour exécuter les tests
run_tests() {
    print_message "Exécution des tests..."
    docker-compose exec app php bin/phpunit
    print_message "Tests terminés !"
}

# Vérification de Docker
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker n'est pas installé ou n'est pas dans le PATH"
        exit 1
    fi

    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose n'est pas installé ou n'est pas dans le PATH"
        exit 1
    fi
}

# Vérification du fichier docker-compose.yml
check_compose_file() {
    if [ ! -f "docker-compose.yml" ]; then
        print_error "Le fichier docker-compose.yml n'existe pas dans le répertoire actuel"
        exit 1
    fi
}

# Fonction principale
main() {
    check_docker
    check_compose_file

    case "${1:-help}" in
        start)
            start_services
            ;;
        stop)
            stop_services
            ;;
        restart)
            restart_services
            ;;
        logs)
            show_logs
            ;;
        shell)
            open_shell
            ;;
        build)
            build_images
            ;;
        clean)
            clean_all
            ;;
        status)
            show_status
            ;;
        install)
            install_dependencies
            ;;
        migrate)
            run_migrations
            ;;
        fixtures)
            load_fixtures
            ;;
        test)
            run_tests
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            print_error "Commande inconnue: $1"
            show_help
            exit 1
            ;;
    esac
}

# Exécution de la fonction principale
main "$@" 