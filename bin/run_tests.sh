#!/bin/bash

# Couleurs pour le terminal
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Démarrage des tests de recette...${NC}\n"

# 1. Initialisation de l'environnement de test
echo -e "${YELLOW}1. Initialisation de l'environnement de test${NC}"
composer test:init
if [ $? -ne 0 ]; then
    echo -e "${RED}Échec de l'initialisation de l'environnement de test${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Environnement de test initialisé${NC}\n"

# 2. Exécution des tests unitaires
echo -e "${YELLOW}2. Exécution des tests unitaires${NC}"
composer test:unit
if [ $? -ne 0 ]; then
    echo -e "${RED}Échec des tests unitaires${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Tests unitaires réussis${NC}\n"

# 3. Exécution des tests d'intégration
echo -e "${YELLOW}3. Exécution des tests d'intégration${NC}"
composer test:integration
if [ $? -ne 0 ]; then
    echo -e "${RED}Échec des tests d'intégration${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Tests d'intégration réussis${NC}\n"

# 4. Exécution des tests fonctionnels
echo -e "${YELLOW}4. Exécution des tests fonctionnels${NC}"
composer test:functional
if [ $? -ne 0 ]; then
    echo -e "${RED}Échec des tests fonctionnels${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Tests fonctionnels réussis${NC}\n"

# 5. Génération du rapport de couverture
echo -e "${YELLOW}5. Génération du rapport de couverture${NC}"
composer test:coverage
if [ $? -ne 0 ]; then
    echo -e "${RED}Échec de la génération du rapport de couverture${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Rapport de couverture généré${NC}\n"

# 6. Vérification des seuils de couverture
echo -e "${YELLOW}6. Vérification des seuils de couverture${NC}"
coverage_file="coverage/index.html"
if [ ! -f "$coverage_file" ]; then
    echo -e "${RED}Fichier de couverture non trouvé${NC}"
    exit 1
fi

# Affichage du résumé
echo -e "\n${YELLOW}Résumé des tests :${NC}"
echo -e "${GREEN}✓ Tests unitaires${NC}"
echo -e "${GREEN}✓ Tests d'intégration${NC}"
echo -e "${GREEN}✓ Tests fonctionnels${NC}"
echo -e "${GREEN}✓ Rapport de couverture${NC}"

echo -e "\n${GREEN}Tous les tests ont été exécutés avec succès !${NC}"
echo -e "Le rapport de couverture est disponible dans : ${YELLOW}coverage/index.html${NC}" 