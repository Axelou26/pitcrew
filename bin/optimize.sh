#!/bin/bash

echo "🚀 Optimisation de l'application Symfony..."

# Vider tous les caches
echo "🗑️  Vidage des caches..."
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Optimiser l'autoloader Composer
echo "📦 Optimisation de l'autoloader Composer..."
composer dump-autoload --optimize --no-dev --classmap-authoritative

# Vider le cache Doctrine
echo "🗄️  Vidage du cache Doctrine..."
php bin/console doctrine:cache:clear-metadata --env=prod
php bin/console doctrine:cache:clear-query --env=prod
php bin/console doctrine:cache:clear-result --env=prod

# Optimiser les routes
echo "🛣️  Optimisation des routes..."
php bin/console router:match / --env=prod

# Vérifier les permissions
echo "🔐 Vérification des permissions..."
chmod -R 755 var/cache/
chmod -R 755 var/log/

echo "✅ Optimisation terminée !" 