#!/bin/bash

# Couleurs pour le terminal
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Vérification des arguments
if [ "$#" -lt 2 ]; then
    echo -e "${RED}Usage: $0 <bug-number> <description>${NC}"
    echo -e "Example: $0 123 \"Fix login validation\""
    exit 1
fi

BUG_NUMBER=$1
DESCRIPTION=$2
BRANCH_NAME="fix/bug-${BUG_NUMBER}-${DESCRIPTION// /-}"

echo -e "${YELLOW}Démarrage du processus de correction du bug #${BUG_NUMBER}${NC}\n"

# 1. Création de la branche
echo -e "${YELLOW}1. Création de la branche de correction${NC}"
git checkout -b "$BRANCH_NAME"
if [ $? -ne 0 ]; then
    echo -e "${RED}Échec de la création de la branche${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Branche créée : $BRANCH_NAME${NC}\n"

# 2. Création du fichier de documentation
echo -e "${YELLOW}2. Création du fichier de documentation${NC}"
DOC_FILE="docs/bugs/bug-${BUG_NUMBER}.md"
mkdir -p docs/bugs

cat > "$DOC_FILE" << EOF
# Bug #${BUG_NUMBER}

## Description
[À compléter]

## Impact
- **Niveau de criticité** : [Critique/Majeur/Mineur]
- **Composants affectés** : [Liste des composants]
- **Utilisateurs affectés** : [Types d'utilisateurs]

## Reproduction
1. [Étape 1]
2. [Étape 2]
3. [Étape 3]

## Analyse
[Description technique du problème]

## Solution
[Description de la solution implémentée]

## Tests
- [ ] Tests unitaires ajoutés/modifiés
- [ ] Tests d'intégration ajoutés/modifiés
- [ ] Tests fonctionnels ajoutés/modifiés
- [ ] Tests de non-régression exécutés

## Validation
- [ ] Revue de code effectuée
- [ ] Tests automatisés passés
- [ ] Validation fonctionnelle effectuée
- [ ] Documentation mise à jour

## Déploiement
- [ ] Déployé en préproduction le : [DATE]
- [ ] Validé en préproduction le : [DATE]
- [ ] Déployé en production le : [DATE]

## Notes
[Notes additionnelles]
EOF

echo -e "${GREEN}✓ Fichier de documentation créé : $DOC_FILE${NC}\n"

# 3. Exécution des tests initiaux
echo -e "${YELLOW}3. Exécution des tests initiaux${NC}"
composer test
if [ $? -ne 0 ]; then
    echo -e "${RED}⚠ Certains tests échouent avant la correction${NC}"
    echo -e "Assurez-vous de documenter ces échecs dans le fichier de documentation."
else
    echo -e "${GREEN}✓ Tous les tests initiaux passent${NC}"
fi
echo

# 4. Instructions pour le développeur
echo -e "${YELLOW}Étapes suivantes :${NC}"
echo -e "1. Compléter la documentation dans : ${GREEN}$DOC_FILE${NC}"
echo -e "2. Implémenter la correction"
echo -e "3. Ajouter/modifier les tests nécessaires"
echo -e "4. Exécuter ${GREEN}composer test${NC} pour valider la correction"
echo -e "5. Commiter les changements"
echo -e "6. Pousser la branche avec ${GREEN}git push origin $BRANCH_NAME${NC}"
echo -e "7. Créer une Pull Request"

# 5. Création d'un commit initial
git add "$DOC_FILE"
git commit -m "docs: initialisation de la documentation pour le bug #${BUG_NUMBER}"

echo -e "\n${GREEN}Configuration du fix terminée !${NC}"
echo -e "Vous pouvez maintenant commencer à travailler sur la correction." 