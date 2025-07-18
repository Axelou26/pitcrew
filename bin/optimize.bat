@echo off
echo 🚀 Optimisation des performances Symfony...

REM Optimisation de l'autoloader Composer
echo 📦 Optimisation de l'autoloader Composer...
composer dump-autoload --no-dev --classmap-authoritative --optimize

REM Vider les caches
echo 🗑️  Vidage des caches...
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

REM Optimisation du cache des routes
echo 🛣️  Optimisation du cache des routes...
php bin/console router:match / --env=prod

REM Optimisation des métadonnées Doctrine
echo 🗄️  Optimisation des métadonnées Doctrine...
php bin/console doctrine:cache:clear-metadata --env=prod
php bin/console doctrine:cache:clear-query --env=prod
php bin/console doctrine:cache:clear-result --env=prod

echo ✅ Optimisation terminée !
echo 📊 Redémarrez votre serveur web pour appliquer les changements.
pause 