#!/bin/bash

echo "🚀 Optimisation des performances Symfony..."

# Optimisation de l'autoloader Composer
echo "📦 Optimisation de l'autoloader Composer..."
composer dump-autoload --no-dev --classmap-authoritative --optimize

# Vider les caches
echo "🗑️  Vidage des caches..."
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Optimisation du cache des routes
echo "🛣️  Optimisation du cache des routes..."
php bin/console router:match / --env=prod

# Optimisation des métadonnées Doctrine
echo "🗄️  Optimisation des métadonnées Doctrine..."
php bin/console doctrine:cache:clear-metadata --env=prod
php bin/console doctrine:cache:clear-query --env=prod
php bin/console doctrine:cache:clear-result --env=prod

# Vérification des permissions
echo "🔐 Vérification des permissions..."
chmod -R 755 var/cache/
chmod -R 755 var/log/

echo "✅ Optimisation terminée !"
echo "📊 Redémarrez votre serveur web pour appliquer les changements." 