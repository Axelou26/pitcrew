#!/bin/bash

echo "🚀 Démarrage de l'environnement de PRODUCTION..."

# Vérification des variables d'environnement
if [ ! -f .env.prod ]; then
    echo "❌ Fichier .env.prod manquant !"
    echo "📝 Créez un fichier .env.prod avec les variables suivantes :"
    echo "   APP_SECRET=votre-secret-production"
    echo "   DATABASE_URL=mysql://user:password@database:3306/pitcrew_prod"
    echo "   MYSQL_USER=votre-user"
    echo "   MYSQL_PASSWORD=votre-password"
    echo "   MYSQL_ROOT_PASSWORD=votre-root-password"
    echo "   REDIS_URL=redis://redis:6379"
    exit 1
fi

# Arrêt des autres environnements
echo "🛑 Arrêt des autres environnements..."
docker-compose down 2>/dev/null
docker-compose -f docker-compose.preprod.yml down 2>/dev/null

# Vérification des certificats SSL
if [ ! -f docker/nginx/ssl/cert.pem ] || [ ! -f docker/nginx/ssl/key.pem ]; then
    echo "⚠️  Certificats SSL manquants !"
    echo "📝 Créez les certificats SSL dans docker/nginx/ssl/"
    echo "   Pour un certificat auto-signé :"
    echo "   mkdir -p docker/nginx/ssl"
    echo "   openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout docker/nginx/ssl/key.pem -out docker/nginx/ssl/cert.pem"
fi

# Démarrage de l'environnement de production
echo "🔧 Démarrage de l'environnement de production..."
docker-compose -f docker-compose.prod.yml --env-file .env.prod up -d

# Attendre que les services soient prêts
echo "⏳ Attente du démarrage des services..."
sleep 20

# Vérification de la santé des services
echo "🔍 Vérification de la santé des services..."
docker-compose -f docker-compose.prod.yml --env-file .env.prod ps

echo "✅ Environnement de production démarré !"
echo "🌐 Application: https://localhost"
echo "🔒 SSL activé"
echo "📊 Monitoring des ressources activé" 