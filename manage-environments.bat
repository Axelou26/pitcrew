@echo off
REM Script de gestion des environnements PitCrew
REM Usage: manage-environments.bat [dev^|preprod^|prod^|stop^|status^|logs]

set ENV=%1
if "%ENV%"=="" set ENV=dev

if "%ENV%"=="dev" (
    echo 🚀 Lancement de l'environnement de DÉVELOPPEMENT...
    call docker-start-dev.bat
) else if "%ENV%"=="preprod" (
    echo 🚀 Lancement de l'environnement de PRÉ-PRODUCTION...
    call docker-start-preprod.bat
) else if "%ENV%"=="prod" (
    echo 🚀 Lancement de l'environnement de PRODUCTION...
    call docker-start-prod.bat
) else if "%ENV%"=="stop" (
    echo 🛑 Arrêt de tous les environnements...
    docker-compose down
    docker-compose -f docker-compose.preprod.yml down
    docker-compose -f docker-compose.prod.yml down
    echo ✅ Tous les environnements arrêtés
) else if "%ENV%"=="status" (
    echo 📊 Statut des environnements :
    echo.
    echo 🔧 DÉVELOPPEMENT :
    docker-compose ps 2>nul || echo    Non démarré
    echo.
    echo 🔧 PRÉ-PRODUCTION :
    docker-compose -f docker-compose.preprod.yml ps 2>nul || echo    Non démarré
    echo.
    echo 🔧 PRODUCTION :
    docker-compose -f docker-compose.prod.yml ps 2>nul || echo    Non démarré
) else if "%ENV%"=="logs" (
    echo 📋 Logs de l'environnement actuel :
    docker-compose logs -f
) else if "%ENV%"=="logs-preprod" (
    echo 📋 Logs de l'environnement de pré-production :
    docker-compose -f docker-compose.preprod.yml logs -f
) else if "%ENV%"=="logs-prod" (
    echo 📋 Logs de l'environnement de production :
    docker-compose -f docker-compose.prod.yml logs -f
) else if "%ENV%"=="clean" (
    echo 🧹 Nettoyage des conteneurs et volumes...
    docker-compose down -v
    docker-compose -f docker-compose.preprod.yml down -v
    docker-compose -f docker-compose.prod.yml down -v
    docker system prune -f
    echo ✅ Nettoyage terminé
) else if "%ENV%"=="setup-prod" (
    echo 🔧 Configuration de l'environnement de production...
    
    REM Création du fichier .env.prod
    if not exist .env.prod (
        echo 📝 Création du fichier .env.prod...
        copy env.prod.example .env.prod
        echo ⚠️  Modifiez le fichier .env.prod avec vos vraies valeurs !
    )
    
    REM Création des certificats SSL
    if not exist docker\nginx\ssl\cert.pem (
        echo 🔐 Création des certificats SSL auto-signés...
        mkdir docker\nginx\ssl 2>nul
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout docker\nginx\ssl\key.pem -out docker\nginx\ssl\cert.pem -subj "/C=FR/ST=France/L=Paris/O=PitCrew/CN=localhost"
        echo ✅ Certificats SSL créés
    )
    
    echo ✅ Configuration terminée
) else (
    echo ❌ Usage: %0 [dev^|preprod^|prod^|stop^|status^|logs^|logs-preprod^|logs-prod^|clean^|setup-prod]
    echo.
    echo 📋 Commandes disponibles :
    echo   dev          - Démarrer l'environnement de développement
    echo   preprod      - Démarrer l'environnement de pré-production
    echo   prod         - Démarrer l'environnement de production
    echo   stop         - Arrêter tous les environnements
    echo   status       - Afficher le statut de tous les environnements
    echo   logs         - Afficher les logs de l'environnement actuel
    echo   logs-preprod - Afficher les logs de la pré-production
    echo   logs-prod    - Afficher les logs de la production
    echo   clean        - Nettoyer tous les conteneurs et volumes
    echo   setup-prod   - Configurer l'environnement de production
    exit /b 1
) 