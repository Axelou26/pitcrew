@echo off
echo 🚀 Optimisation de l'application Symfony...

REM Vider tous les caches
echo 🗑️  Vidage des caches...
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

REM Optimiser l'autoloader Composer
echo 📦 Optimisation de l'autoloader Composer...
composer dump-autoload --optimize --no-dev --classmap-authoritative

REM Vider le cache Doctrine
echo 🗄️  Vidage du cache Doctrine...
php bin/console doctrine:cache:clear-metadata --env=prod
php bin/console doctrine:cache:clear-query --env=prod
php bin/console doctrine:cache:clear-result --env=prod

REM Optimiser les routes
echo 🛣️  Optimisation des routes...
php bin/console router:match / --env=prod

echo ✅ Optimisation terminée !
pause 