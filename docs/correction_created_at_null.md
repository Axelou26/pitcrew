# Correction du problème createdAt null

## Problème

Une exception était levée lors du rendu des templates Twig utilisant le filtre `ago` de KnpTimeBundle :

```
Knp\Bundle\TimeBundle\DateTimeFormatter::formatDiff(): Argument #1 ($from) must be of type DateTimeInterface|string|int, null given
```

Cette erreur se produisait dans le template `templates/post/_post_card_minimal.html.twig` à la ligne 24.

## Cause

Certaines entités dans la base de données avaient une valeur `null` pour le champ `createdAt`, ce qui causait l'échec du filtre `ago` qui ne peut pas gérer les valeurs `null`.

## Solution

### 1. Correction des templates

J'ai ajouté des vérifications de sécurité dans tous les templates qui utilisent le filtre `ago` :

- `templates/post/_post_card_minimal.html.twig`
- `templates/job_offer/_offers_list.html.twig`
- `templates/notification/_notification.html.twig`
- `templates/notification/_notifications_list.html.twig`
- `templates/job_application/index.html.twig`
- `templates/job_application/show.html.twig`
- `templates/profile/index.html.twig`
- `templates/applicant/job_offers.html.twig`

**Exemple de correction :**
```twig
{% if post.createdAt %}
    {{ post.createdAt|ago }}
{% else %}
    <em>Date inconnue</em>
{% endif %}
```

### 2. Commande de correction des données

J'ai créé une commande Symfony pour corriger les données existantes :

```bash
php bin/console app:fix-null-created-at
```

Cette commande :
- Trouve toutes les entités avec `createdAt` null
- Les met à jour avec la date actuelle
- Affiche le nombre d'entités corrigées

### 3. Entités concernées

Les entités suivantes sont vérifiées et corrigées :
- `Post`
- `JobOffer`
- `Application`
- `JobApplication`
- `Notification`

## Prévention

Pour éviter ce problème à l'avenir :

1. **Toujours initialiser `createdAt` dans le constructeur** :
```php
public function __construct()
{
    $this->createdAt = new DateTimeImmutable();
}
```

2. **Ajouter des vérifications dans les templates** avant d'utiliser le filtre `ago`

3. **Utiliser des contraintes de validation** pour s'assurer que `createdAt` n'est jamais null

## Tests

Un script de test a été créé (`test_fix_created_at.php`) pour vérifier que les templates gèrent correctement les valeurs null.

## Impact

- ✅ Plus d'erreurs lors du rendu des templates
- ✅ Affichage gracieux des dates inconnues
- ✅ Correction automatique des données existantes
- ✅ Prévention du problème à l'avenir 