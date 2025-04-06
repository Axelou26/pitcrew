# Hashtags Tendance

Ce document explique comment fonctionne le système de mise à jour automatique des hashtags tendance.

## Fonctionnement

Le système utilise une combinaison de commandes Symfony et de cache pour maintenir une liste actualisée des hashtags les plus populaires.

### Algorithme

La détection des hashtags tendance prend en compte les facteurs suivants :
- **Utilisation récente** : Les hashtags utilisés dans les dernières 24 heures ont plus de poids
- **Engagement** : L'engagement sur les posts (likes, commentaires, partages) contenant ces hashtags augmente leur score
- **Pondération** : Des poids différents sont appliqués aux différents types d'engagement :
  - Utilisation récente : poids x2
  - Likes : poids x0.5
  - Commentaires : poids x1
  - Partages : poids x1.5

### Mise en cache

La liste des hashtags tendance est mise en cache pendant 1 heure pour réduire la charge sur la base de données.

## Configuration

### Commande Symfony

La commande suivante met à jour les hashtags tendance :

```
php bin/console app:update-trending-hashtags
```

### Configuration cron

Pour automatiser la mise à jour, ajoutez la ligne suivante à votre configuration cron :

```
# Mise à jour des hashtags tendance toutes les heures
0 * * * * cd /chemin/absolu/vers/pitcrew && php bin/console app:update-trending-hashtags >> var/log/trending-hashtags.log 2>&1
```

Pour ajouter cette tâche à crontab :

1. Éditez votre crontab :
```
crontab -e
```

2. Ajoutez la ligne ci-dessus (en remplaçant `/chemin/absolu/vers/pitcrew` par le chemin absolu de votre projet)

3. Sauvegardez et fermez l'éditeur

### Vérification

Pour vérifier que la commande fonctionne correctement, exécutez-la manuellement :

```
php bin/console app:update-trending-hashtags
```

Vous devriez voir une sortie avec la liste des hashtags tendance et leurs scores.

## Affichage

Les hashtags tendance sont automatiquement affichés sur la page d'accueil et sur les pages de détail des hashtags. Aucune modification des templates n'est nécessaire. 