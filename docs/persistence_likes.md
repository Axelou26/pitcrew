# Persistance des Likes - Améliorations

## Problème résolu

Avant cette amélioration, l'état des boutons "j'aime" n'était pas persisté lors des changements de page ou rechargements. Quand un utilisateur likait un post puis changeait de page ou rechargeait la page, le bouton revenait à son état initial.

## Solution implémentée

### 1. Persistance côté client (localStorage)

Les likes sont maintenant stockés dans le `localStorage` du navigateur pour maintenir l'état entre les sessions :

```javascript
// Fonctions de gestion de la persistance
function getLikedPosts() {
    const likedPosts = localStorage.getItem('likedPosts');
    return likedPosts ? JSON.parse(likedPosts) : [];
}

function setLikedPosts(likedPosts) {
    localStorage.setItem('likedPosts', JSON.stringify(likedPosts));
}

function isPostLiked(postId) {
    const likedPosts = getLikedPosts();
    return likedPosts.includes(postId);
}

function addLikedPost(postId) {
    const likedPosts = getLikedPosts();
    if (!likedPosts.includes(postId)) {
        likedPosts.push(postId);
        setLikedPosts(likedPosts);
    }
}

function removeLikedPost(postId) {
    const likedPosts = getLikedPosts();
    const index = likedPosts.indexOf(postId);
    if (index > -1) {
        likedPosts.splice(index, 1);
        setLikedPosts(likedPosts);
    }
}
```

### 2. Synchronisation avec le serveur

Une nouvelle route `/post/sync-likes` a été ajoutée pour synchroniser l'état local avec l'état réel sur le serveur :

```php
#[Route('/sync-likes', name: 'app_post_sync_likes', methods: ['POST'])]
public function syncLikes(Request $request): JsonResponse
{
    // Vérifie les posts réellement likés par l'utilisateur
    // et retourne la liste synchronisée
}
```

### 3. Mise à jour de l'apparence des boutons

Une fonction centralisée gère l'apparence des boutons like :

```javascript
function updateLikeButtonAppearance(button, isLiked) {
    const icon = button.querySelector('i');
    
    if (isLiked) {
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-primary');
        icon.classList.remove('bi-heart');
        icon.classList.add('bi-heart-fill');
    } else {
        button.classList.remove('btn-primary');
        button.classList.add('btn-outline-secondary');
        icon.classList.remove('bi-heart-fill');
        icon.classList.add('bi-heart');
    }
}
```

## Fichiers modifiés

### JavaScript
- `public/js/feed.js` - Ajout de la persistance pour le feed principal
- `public/js/post.js` - Ajout de la persistance pour les pages de posts individuels
- `assets/js/feed.js` - Ajout de la persistance pour l'ancien système de feed
- `assets/js/post.js` - Ajout de la persistance pour l'ancien système de posts

### PHP
- `src/Controller/PostController.php` - Ajout de la route `/sync-likes`

## Fonctionnalités

1. **Persistance locale** : Les likes sont sauvegardés dans le localStorage
2. **Synchronisation serveur** : L'état local est vérifié avec le serveur au chargement
3. **Mise à jour en temps réel** : Les boutons se mettent à jour instantanément
4. **Compatibilité** : Fonctionne sur toutes les pages utilisant les boutons like

## Avantages

- ✅ Les likes persistent lors des changements de page
- ✅ Les likes persistent lors des rechargements
- ✅ Synchronisation automatique avec le serveur
- ✅ Performance optimisée (pas de requêtes inutiles)
- ✅ Expérience utilisateur améliorée

## Utilisation

Aucune action spéciale n'est requise de la part de l'utilisateur. Le système fonctionne automatiquement :

1. L'utilisateur like un post
2. L'état est sauvegardé localement
3. Lors du changement de page/rechargement, l'état est restauré
4. Une synchronisation avec le serveur vérifie la cohérence 