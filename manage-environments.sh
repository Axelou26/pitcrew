#!/bin/bash

# Script de gestion des environnements PitCrew
# Usage: ./manage-environments.sh [dev|preprod|prod|stop|status|logs]

ENV=${1:-dev}

case $ENV in
    "dev")
        echo "🚀 Lancement de l'environnement de DÉVELOPPEMENT..."
        ./docker-start-dev.sh
        ;;
    "preprod")
        echo "🚀 Lancement de l'environnement de PRÉ-PRODUCTION..."
        ./docker-start-preprod.sh
        ;;
    "prod")
        echo "🚀 Lancement de l'environnement de PRODUCTION..."
        ./docker-start-prod.sh
        ;;
    "stop")
        echo "🛑 Arrêt de tous les environnements..."
        docker-compose down
        docker-compose -f docker-compose.preprod.yml down
        docker-compose -f docker-compose.prod.yml down
        echo "✅ Tous les environnements arrêtés"
        ;;
    "status")
        echo "📊 Statut des environnements :"
        echo ""
        echo "🔧 DÉVELOPPEMENT :"
        docker-compose ps 2>/dev/null || echo "   Non démarré"
        echo ""
        echo "🔧 PRÉ-PRODUCTION :"
        docker-compose -f docker-compose.preprod.yml ps 2>/dev/null || echo "   Non démarré"
        echo ""
        echo "🔧 PRODUCTION :"
        docker-compose -f docker-compose.prod.yml ps 2>/dev/null || echo "   Non démarré"
        ;;
    "logs")
        echo "📋 Logs de l'environnement actuel :"
        docker-compose logs -f
        ;;
    "logs-preprod")
        echo "📋 Logs de l'environnement de pré-production :"
        docker-compose -f docker-compose.preprod.yml logs -f
        ;;
    "logs-prod")
        echo "📋 Logs de l'environnement de production :"
        docker-compose -f docker-compose.prod.yml logs -f
        ;;
    "clean")
        echo "🧹 Nettoyage des conteneurs et volumes..."
        docker-compose down -v
        docker-compose -f docker-compose.preprod.yml down -v
        docker-compose -f docker-compose.prod.yml down -v
        docker system prune -f
        echo "✅ Nettoyage terminé"
        ;;
    "setup-prod")
        echo "🔧 Configuration de l'environnement de production..."
        
        # Création du fichier .env.prod
        if [ ! -f .env.prod ]; then
            echo "📝 Création du fichier .env.prod..."
            cp env.prod.example .env.prod
            echo "⚠️  Modifiez le fichier .env.prod avec vos vraies valeurs !"
        fi
        
        # Création des certificats SSL
        if [ ! -f docker/nginx/ssl/cert.pem ]; then
            echo "🔐 Création des certificats SSL auto-signés..."
            mkdir -p docker/nginx/ssl
            openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
                -keyout docker/nginx/ssl/key.pem \
                -out docker/nginx/ssl/cert.pem \
                -subj "/C=FR/ST=France/L=Paris/O=PitCrew/CN=localhost"
            echo "✅ Certificats SSL créés"
        fi
        
        echo "✅ Configuration terminée"
        ;;
    *)
        echo "❌ Usage: $0 [dev|preprod|prod|stop|status|logs|logs-preprod|logs-prod|clean|setup-prod]"
        echo ""
        echo "📋 Commandes disponibles :"
        echo "  dev          - Démarrer l'environnement de développement"
        echo "  preprod      - Démarrer l'environnement de pré-production"
        echo "  prod         - Démarrer l'environnement de production"
        echo "  stop         - Arrêter tous les environnements"
        echo "  status       - Afficher le statut de tous les environnements"
        echo "  logs         - Afficher les logs de l'environnement actuel"
        echo "  logs-preprod - Afficher les logs de la pré-production"
        echo "  logs-prod    - Afficher les logs de la production"
        echo "  clean        - Nettoyer tous les conteneurs et volumes"
        echo "  setup-prod   - Configurer l'environnement de production"
        exit 1
        ;;
esac 