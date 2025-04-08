# Tests Unitaires

Ce dossier contient tous les tests unitaires de l'application. Les tests sont organisés selon la même structure que le code source.

## Structure

```
tests/
├── Unit/                    # Tests unitaires
│   ├── Service/            # Tests des services
│   ├── Entity/             # Tests des entités
│   ├── Form/              # Tests des formulaires
│   └── Security/          # Tests de sécurité
├── Integration/            # Tests d'intégration
│   ├── Controller/        # Tests des contrôleurs
│   ├── Repository/        # Tests des repositories
│   └── Service/          # Tests d'intégration des services
└── Functional/            # Tests fonctionnels
    └── Controller/        # Tests fonctionnels des contrôleurs
```

## Conventions de nommage

- Les classes de test doivent se terminer par "Test"
- Les méthodes de test doivent commencer par "test"
- Les noms des méthodes doivent décrire le scénario testé

Exemple :
```php
public function testCreateUserWithValidData()
public function testCreateUserWithInvalidEmail()
``` 