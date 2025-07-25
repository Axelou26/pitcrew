@echo off
echo 🚀 Démarrage de l'environnement de PRÉ-PRODUCTION...

REM Arrêt des autres environnements
echo 🛑 Arrêt des autres environnements...
docker-compose down 2>nul
docker-compose -f docker-compose.prod.yml down 2>nul

REM Démarrage de l'environnement de pré-production
echo 🔧 Démarrage de l'environnement de pré-production...
docker-compose -f docker-compose.preprod.yml up -d

REM Attendre que les services soient prêts
echo ⏳ Attente du démarrage des services...
timeout /t 15 /nobreak >nul

REM Vérification de la santé des services
echo 🔍 Vérification de la santé des services...
docker-compose -f docker-compose.preprod.yml ps

echo ✅ Environnement de pré-production démarré !
echo 🌐 Application: http://localhost:8889
echo 📧 MailHog: http://localhost:8026
echo 🗄️  phpMyAdmin: http://localhost:8081
echo 🔴 Redis: localhost:6380
echo 🗃️  MySQL: localhost:33307 