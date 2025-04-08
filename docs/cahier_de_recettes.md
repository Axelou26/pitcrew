# Cahier de Recettes - PitCrew

## 1. Gestion des Utilisateurs

### 1.1 Inscription Utilisateur
**Scénario**: Inscription d'un nouveau postulant
- **Prérequis**: Aucun
- **Actions**:
  1. Accéder à la page d'inscription
  2. Sélectionner le type "Postulant"
  3. Remplir le formulaire avec données valides
  4. Soumettre le formulaire
- **Résultat Attendu**:
  - Redirection vers la page de confirmation
  - Email de vérification envoyé
  - Utilisateur créé en base de données
- **Test Associé**: `UserRegistrationTest::testCompleteRegistrationProcess()`

### 1.2 Validation des Données d'Inscription
**Scénario**: Tentative d'inscription avec données invalides
- **Actions**:
  1. Soumettre le formulaire avec données invalides
- **Résultats Attendus**:
  - Affichage des messages d'erreur appropriés
  - Aucun utilisateur créé
- **Test Associé**: `UserRegistrationTest::testRegistrationWithInvalidData()`

## 2. Gestion des Candidatures

### 2.1 Soumission de Candidature
**Scénario**: Postulation à une offre d'emploi
- **Prérequis**: Utilisateur connecté (postulant)
- **Actions**:
  1. Accéder à une offre d'emploi
  2. Remplir le formulaire de candidature
  3. Joindre CV et lettre de motivation
  4. Soumettre la candidature
- **Résultats Attendus**:
  - Candidature enregistrée
  - Documents uploadés sur S3
  - Notification envoyée au recruteur
- **Test Associé**: `JobApplicationTest::testBasicInformation()`

### 2.2 Gestion des Documents
**Scénario**: Upload et gestion des documents
- **Actions**:
  1. Upload de multiples documents
  2. Suppression d'un document
- **Résultats Attendus**:
  - Documents correctement stockés
  - Document supprimé accessible
- **Test Associé**: `JobApplicationTest::testDocuments()`

## 3. Système d'Abonnement

### 3.1 Vérification des Fonctionnalités par Niveau
**Scénario**: Accès aux fonctionnalités selon le niveau d'abonnement
- **Actions**:
  1. Tester l'accès aux fonctionnalités Basic
  2. Tester l'accès aux fonctionnalités Premium
  3. Tester l'accès aux fonctionnalités Business
- **Résultats Attendus**:
  - Accès correct selon le niveau
  - Restrictions appropriées
- **Test Associé**: `SubscriptionFeaturesTest::testFeaturesByLevel()`

## 4. Performance et Sécurité

### 4.1 Tests de Performance
**Scénario**: Vérification des temps de réponse
- **Actions**:
  1. Chargement de la page d'accueil
  2. Requêtes base de données
  3. Performance du cache
- **Résultats Attendus**:
  - Temps de chargement < 1s
  - Requêtes DB < 0.5s
  - Cache fonctionnel
- **Test Associé**: `HomepagePerformanceTest::testHomepageLoadTime()`

### 4.2 Tests de Sécurité
**Scénario**: Vérification des mesures de sécurité
- **Actions**:
  1. Tentatives d'injection SQL
  2. Tests XSS
  3. Vérification CSRF
- **Résultats Attendus**:
  - Toutes les attaques bloquées
  - Headers sécurité présents
- **Test Associé**: `SecurityTest::testSecurityHeaders()`

## 5. Procédure d'Exécution des Tests

### 5.1 Initialisation
```bash
composer test:init
```
- Crée la base de données de test
- Applique le schéma
- Charge les fixtures

### 5.2 Exécution des Tests
```bash
composer test:all
```
- Lance tous les tests unitaires
- Lance les tests d'intégration
- Lance les tests fonctionnels
- Génère le rapport de couverture

### 5.3 Validation des Résultats
- Vérifier le rapport de couverture
- Analyser les logs d'erreur
- Valider les métriques de performance 