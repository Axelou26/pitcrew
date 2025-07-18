# Optimisation des Notifications

## Problème identifié

Les endpoints `/notifications/unread` et `/api/notifications/count` présentaient des erreurs 500 et des temps de réponse très élevés (3-14 secondes).

## Causes identifiées

1. **Erreur 500 sur `/notifications/unread`** : Le contrôleur ne gérait pas les requêtes AJAX et tentait de rendre un template Twig complet au lieu de retourner du HTML partiel.

2. **Performances dégradées** : 
   - Absence d'index sur la table `notification`
   - Pas de cache sur les requêtes fréquentes
   - Fréquence trop élevée des appels AJAX (30 secondes)

## Solutions implémentées

### 1. Correction de l'endpoint `/notifications/unread`

**Fichier modifié** : `src/Controller/NotificationController.php`

- Ajout de la gestion des requêtes AJAX
- Retour d'HTML partiel pour les requêtes AJAX
- Retour du template complet pour les requêtes normales

### 2. Optimisation des requêtes de base de données

**Fichier modifié** : `src/Repository/NotificationRepository.php`

- Ajout de cache sur les requêtes fréquentes (15-30 secondes)
- Limitation du nombre de résultats (20 par défaut)
- Invalidation intelligente du cache

### 3. Ajout d'index de base de données

**Fichier modifié** : `src/Entity/Notification.php`

- Index composite sur `(user_id, is_read)`
- Index sur `created_at`
- Index sur `type`

**Migration** : `migrations/Version20250115000001.php`

### 4. Optimisation du JavaScript

**Fichier modifié** : `public/js/notifications.js`

- Réduction de la fréquence des appels (60 secondes au lieu de 30)
- Amélioration de la gestion des erreurs
- Arrêt automatique en cas d'erreurs répétées

### 5. Template partiel pour AJAX

**Nouveau fichier** : `templates/notification/_notifications_list.html.twig`

- Template optimisé pour les requêtes AJAX
- Structure HTML simplifiée
- Gestion des cas vides

## Résultats attendus

1. **Élimination des erreurs 500** : L'endpoint `/notifications/unread` devrait maintenant fonctionner correctement
2. **Amélioration des performances** : Réduction significative des temps de réponse grâce aux index et au cache
3. **Réduction de la charge serveur** : Moins d'appels AJAX et cache intelligent

## Tests

Un script de test a été créé : `bin/test_notifications.php`

Pour tester les performances :
```bash
php bin/test_notifications.php
```

## Déploiement

1. Appliquer la migration :
```bash
php bin/console doctrine:migrations:migrate
```

2. Vider le cache :
```bash
php bin/console cache:clear
```

3. Tester les endpoints :
```bash
php bin/test_notifications.php
```

## Monitoring

Surveiller les logs pour vérifier :
- Absence d'erreurs 500
- Réduction des temps de réponse
- Utilisation correcte du cache 