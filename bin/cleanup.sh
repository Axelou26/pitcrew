#!/bin/bash

echo "🧹 Nettoyage automatisé du projet PitCrew"
echo "=========================================="

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
log_info() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

# 1. Vérifier les fichiers PHP non utilisés
echo ""
echo "1. Analyse des fichiers PHP..."

# Chercher les fichiers PHP qui ne sont pas référencés
find src/ -name "*.php" -type f | while read file; do
    filename=$(basename "$file")
    # Ignorer les fichiers de test
    if [[ ! "$file" =~ test ]]; then
        # Chercher les références à ce fichier
        references=$(grep -r "$filename" src/ --exclude-dir=tests 2>/dev/null | wc -l)
        if [ "$references" -eq 0 ]; then
            log_warning "Fichier potentiellement inutilisé: $file"
        fi
    fi
done

# 2. Vérifier les dépendances Composer non utilisées
echo ""
echo "2. Vérification des dépendances Composer..."

if command -v composer-unused &> /dev/null; then
    composer-unused --no-progress
else
    log_warning "composer-unused non installé. Installer avec: composer require --dev maglnet/composer-unused"
fi

# 3. Vérifier les dépendances NPM non utilisées
echo ""
echo "3. Vérification des dépendances NPM..."

if command -v depcheck &> /dev/null; then
    npx depcheck --json | jq -r '.dependencies[]' 2>/dev/null | while read dep; do
        if [ ! -z "$dep" ]; then
            log_warning "Dépendance JS potentiellement inutilisée: $dep"
        fi
    done
else
    log_warning "depcheck non installé. Installer avec: npm install -g depcheck"
fi

# 4. Nettoyer les caches
echo ""
echo "4. Nettoyage des caches..."

php bin/console cache:clear --env=dev
php bin/console cache:clear --env=prod

# 5. Vérifier les fichiers CSS non référencés
echo ""
echo "5. Vérification des fichiers CSS..."

find public/css/ -name "*.css" -type f | while read file; do
    filename=$(basename "$file")
    # Chercher les références dans les templates
    references=$(grep -r "$filename" templates/ 2>/dev/null | wc -l)
    if [ "$references" -eq 0 ]; then
        log_warning "Fichier CSS potentiellement inutilisé: $file"
    fi
done

# 6. Vérifier les fichiers JS non référencés
echo ""
echo "6. Vérification des fichiers JS..."

find assets/js/ -name "*.js" -type f | while read file; do
    filename=$(basename "$file")
    # Chercher les références dans les templates
    references=$(grep -r "$filename" templates/ 2>/dev/null | wc -l)
    if [ "$references" -eq 0 ]; then
        log_warning "Fichier JS potentiellement inutilisé: $file"
    fi
done

# 7. Vérifier les migrations en double
echo ""
echo "7. Vérification des migrations..."

migrations_dir="migrations/"
if [ -d "$migrations_dir" ]; then
    find "$migrations_dir" -name "Version*.php" | while read file; do
        # Extraire le nom de la classe
        class_name=$(grep "class" "$file" | head -1 | sed 's/.*class \([^ ]*\).*/\1/')
        # Chercher les doublons
        duplicates=$(grep -r "class $class_name" "$migrations_dir" | wc -l)
        if [ "$duplicates" -gt 1 ]; then
            log_warning "Migration en double détectée: $class_name"
        fi
    done
fi

# 8. Vérifier les fichiers de configuration obsolètes
echo ""
echo "8. Vérification des fichiers de configuration..."

# Vérifier si webpack.config.js existe mais que Vite est utilisé
if [ -f "webpack.config.js" ] && [ -f "vite.config.js" ]; then
    log_warning "webpack.config.js et vite.config.js coexistent. Supprimer webpack.config.js si Vite est utilisé."
fi

# 9. Nettoyer les fichiers temporaires
echo ""
echo "9. Nettoyage des fichiers temporaires..."

# Supprimer les fichiers de cache PHPStan
rm -rf .phpunit.cache/
rm -f phpstan-unused.txt
rm -f psalm-deadcode.txt

# 10. Vérifier la taille du projet
echo ""
echo "10. Statistiques du projet..."

total_files=$(find . -type f -not -path "./vendor/*" -not -path "./node_modules/*" -not -path "./.git/*" | wc -l)
php_files=$(find . -name "*.php" -not -path "./vendor/*" | wc -l)
js_files=$(find . -name "*.js" -not -path "./node_modules/*" | wc -l)
css_files=$(find . -name "*.css" | wc -l)

echo "📊 Statistiques:"
echo "   - Fichiers totaux: $total_files"
echo "   - Fichiers PHP: $php_files"
echo "   - Fichiers JS: $js_files"
echo "   - Fichiers CSS: $css_files"

echo ""
log_info "Nettoyage terminé !"
echo ""
echo "💡 Recommandations:"
echo "   - Exécuter ce script régulièrement (hebdomadaire)"
echo "   - Ajouter à votre CI/CD pour automatisation"
echo "   - Vérifier manuellement les fichiers signalés avant suppression" 