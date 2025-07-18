#!/bin/sh
set -e

echo "🚀 Optimisation du démarrage PitCrew..."

# Optimisation Symfony
echo "📦 Optimisation des autoloaders..."
if [ -f composer.json ]; then
    composer dump-autoload --optimize --classmap-authoritative --no-dev
fi

# Cache Symfony préchargé
echo "🔥 Préchauffage du cache..."
php bin/console cache:clear --env=dev --no-debug
php bin/console cache:warmup --env=dev

# Optimisation des assets
echo "📦 Optimisation des assets..."
if [ -f package.json ]; then
    npm run build --if-present
fi

# Optimisation des permissions
echo "📁 Optimisation des permissions..."
chown -R www-data:www-data var
chmod -R 775 var

# Précompilation Twig
echo "🎨 Précompilation des templates..."
php bin/console cache:clear --env=dev
php bin/console debug:router > /dev/null 2>&1 || true

echo "✅ Optimisation terminée !" 