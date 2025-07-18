@echo off
echo 🚀 Démarrage de l'environnement de PRODUCTION...

REM Vérification des variables d'environnement
if not exist .env.prod (
    echo ❌ Fichier .env.prod manquant !
    echo 📝 Créez un fichier .env.prod avec les variables suivantes :
    echo    APP_SECRET=votre-secret-production
    echo    DATABASE_URL=mysql://user:password@database:3306/pitcrew_prod
    echo    MYSQL_USER=votre-user
    echo    MYSQL_PASSWORD=votre-password
    echo    MYSQL_ROOT_PASSWORD=votre-root-password
    echo    REDIS_URL=redis://redis:6379
    pause
    exit /b 1
)

REM Arrêt des autres environnements
echo 🛑 Arrêt des autres environnements...
docker-compose down 2>nul
docker-compose -f docker-compose.preprod.yml down 2>nul

REM Vérification des certificats SSL
if not exist docker\nginx\ssl\cert.pem (
    echo ⚠️  Certificats SSL manquants !
    echo 📝 Créez les certificats SSL dans docker/nginx/ssl/
    echo    Pour un certificat auto-signé :
    echo    mkdir docker\nginx\ssl
    echo    openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout docker\nginx\ssl\key.pem -out docker\nginx\ssl\cert.pem
)

REM Démarrage de l'environnement de production
echo 🔧 Démarrage de l'environnement de production...
docker-compose -f docker-compose.prod.yml --env-file .env.prod up -d

REM Attendre que les services soient prêts
echo ⏳ Attente du démarrage des services...
timeout /t 20 /nobreak >nul

REM Vérification de la santé des services
echo 🔍 Vérification de la santé des services...
docker-compose -f docker-compose.prod.yml --env-file .env.prod ps

echo ✅ Environnement de production démarré !
echo 🌐 Application: https://localhost
echo 🔒 SSL activé
echo 📊 Monitoring des ressources activé 