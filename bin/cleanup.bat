@echo off
echo 🧹 Nettoyage automatisé du projet PitCrew
echo ==========================================

REM 1. Vérifier les fichiers PHP non utilisés
echo.
echo 1. Analyse des fichiers PHP...

for /r src\ %%f in (*.php) do (
    if not "%%f" == "%%f" (
        set "filename=%%~nxf"
        findstr /s /i "%%~nxf" src\ >nul 2>&1
        if errorlevel 1 (
            echo ⚠ Fichier potentiellement inutilisé: %%f
        )
    )
)

REM 2. Vérifier les dépendances Composer non utilisées
echo.
echo 2. Vérification des dépendances Composer...

composer-unused --no-progress 2>nul
if errorlevel 1 (
    echo ⚠ composer-unused non installé. Installer avec: composer require --dev maglnet/composer-unused
)

REM 3. Vérifier les dépendances NPM non utilisées
echo.
echo 3. Vérification des dépendances NPM...

npx depcheck --json 2>nul
if errorlevel 1 (
    echo ⚠ depcheck non installé. Installer avec: npm install -g depcheck
)

REM 4. Nettoyer les caches
echo.
echo 4. Nettoyage des caches...

php bin/console cache:clear --env=dev
php bin/console cache:clear --env=prod

REM 5. Vérifier les fichiers CSS non référencés
echo.
echo 5. Vérification des fichiers CSS...

for %%f in (public\css\*.css) do (
    findstr /s /i "%%~nxf" templates\ >nul 2>&1
    if errorlevel 1 (
        echo ⚠ Fichier CSS potentiellement inutilisé: %%f
    )
)

REM 6. Vérifier les fichiers JS non référencés
echo.
echo 6. Vérification des fichiers JS...

for %%f in (assets\js\*.js) do (
    findstr /s /i "%%~nxf" templates\ >nul 2>&1
    if errorlevel 1 (
        echo ⚠ Fichier JS potentiellement inutilisé: %%f
    )
)

REM 7. Vérifier les migrations en double
echo.
echo 7. Vérification des migrations...

if exist migrations\ (
    for %%f in (migrations\Version*.php) do (
        findstr /c:"class" "%%f" | findstr /c:"Version" >nul
        if not errorlevel 1 (
            echo ⚠ Migration potentiellement en double: %%f
        )
    )
)

REM 8. Vérifier les fichiers de configuration obsolètes
echo.
echo 8. Vérification des fichiers de configuration...

if exist webpack.config.js (
    if exist vite.config.js (
        echo ⚠ webpack.config.js et vite.config.js coexistent. Supprimer webpack.config.js si Vite est utilisé.
    )
)

REM 9. Nettoyer les fichiers temporaires
echo.
echo 9. Nettoyage des fichiers temporaires...

if exist .phpunit.cache\ rmdir /s /q .phpunit.cache\
if exist phpstan-unused.txt del phpstan-unused.txt
if exist psalm-deadcode.txt del psalm-deadcode.txt

REM 10. Vérifier la taille du projet
echo.
echo 10. Statistiques du projet...

set total_files=0
set php_files=0
set js_files=0
set css_files=0

for /r . %%f in (*) do (
    if not "%%~dpf" == "vendor\" (
        if not "%%~dpf" == "node_modules\" (
            if not "%%~dpf" == ".git\" (
                set /a total_files+=1
            )
        )
    )
)

for /r . %%f in (*.php) do (
    if not "%%~dpf" == "vendor\" (
        set /a php_files+=1
    )
)

for /r . %%f in (*.js) do (
    if not "%%~dpf" == "node_modules\" (
        set /a js_files+=1
    )
)

for /r . %%f in (*.css) do (
    set /a css_files+=1
)

echo 📊 Statistiques:
echo    - Fichiers totaux: %total_files%
echo    - Fichiers PHP: %php_files%
echo    - Fichiers JS: %js_files%
echo    - Fichiers CSS: %css_files%

echo.
echo ✓ Nettoyage terminé !
echo.
echo 💡 Recommandations:
echo    - Exécuter ce script régulièrement (hebdomadaire)
echo    - Ajouter à votre CI/CD pour automatisation
echo    - Vérifier manuellement les fichiers signalés avant suppression

pause 