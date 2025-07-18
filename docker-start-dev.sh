#!/bin/bash

echo "🚀 Démarrage de l'environnement de DÉVELOPPEMENT..."

# Arrêt des autres environnements
echo "🛑 Arrêt des autres environnements..."
docker-compose -f docker-compose.preprod.yml down 2>/dev/null
docker-compose -f docker-compose.prod.yml down 2>/dev/null

# Démarrage de l'environnement de développement
echo "🔧 Démarrage de l'environnement de développement..."
docker-compose up -d

# Attendre que les services soient prêts
echo "⏳ Attente du démarrage des services..."
sleep 10

# Vérification de la santé des services
echo "🔍 Vérification de la santé des services..."
docker-compose ps

echo "✅ Environnement de développement démarré !"
echo "🌐 Application: http://localhost:8888"
echo "📧 MailHog: http://localhost:8025"
echo "🗄️  phpMyAdmin: http://localhost:8080"
echo "🔴 Redis: localhost:6379"
echo "🗃️  MySQL: localhost:33306" 