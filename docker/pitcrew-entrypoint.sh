#!/bin/sh
set -e

echo "🚀 Démarrage de l'application PitCrew..."

# Nettoyage du cache Symfony si nécessaire
if [ "$APP_ENV" = "dev" ] && [ -d "var/cache" ]; then
    echo "🧹 Nettoyage du cache..."
    rm -rf var/cache/*
fi

# Création des répertoires avec bonnes permissions
echo "📁 Configuration des répertoires..."
mkdir -p var/cache var/log var/sessions
chown -R www-data:www-data var
chmod -R 777 var

# Vérification de la base de données avec timeout
echo "🗄️  Vérification de la base de données..."
timeout=60
counter=0
while [ $counter -lt $timeout ]; do
    if php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; then
        echo "✅ Base de données connectée !"
        break
    fi
    echo "⏳ En attente de la base de données... ($counter/$timeout)"
    sleep 2
    counter=$((counter + 2))
done

if [ $counter -eq $timeout ]; then
    echo "❌ Timeout: Impossible de se connecter à la base de données"
    echo "🔄 Démarrage de PHP-FPM malgré tout..."
fi

# Exécution des migrations si nécessaire
if [ "$APP_ENV" = "dev" ]; then
    echo "🔄 Exécution des migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction || echo "⚠️  Erreur lors des migrations"
fi

# Optimisation complète du démarrage
echo "🔥 Optimisation et warmup du cache..."
if [ -f /usr/local/bin/optimize-startup.sh ]; then
    /usr/local/bin/optimize-startup.sh || echo "⚠️  Erreur lors de l'optimisation"
else
    php bin/console cache:warmup || echo "⚠️  Erreur lors du warmup du cache"
fi

echo "✅ Application prête !"

# Démarrage de PHP-FPM
echo "🚀 Démarrage de PHP-FPM..."
exec php-fpm 