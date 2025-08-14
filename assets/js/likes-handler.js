/**
 * Module dédié à la gestion des likes
 */
export function initLikes() {
    // Rendre la fonction accessible globalement pour le script de secours
    window.initLikes = initLikes;
    
    console.log('Module de gestion des likes chargé');

    // Fonction pour gérer la persistance des likes
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

    // Fonction pour mettre à jour l'apparence du bouton like
    function updateLikeButtonAppearance(button, isLiked) {
        const icon = button.querySelector('i');
        const text = button.querySelector('.like-text');
        
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

    // Fonction pour afficher les notifications
    function showNotification(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Initialiser l'état des likes pour tous les boutons existants
    document.querySelectorAll('.like-button').forEach(button => {
        // Remplacer le bouton existant pour supprimer tous les écouteurs d'événements précédents
        const newButton = button.cloneNode(true);
        if (button.parentNode) {
            button.parentNode.replaceChild(newButton, button);
        }
        
        const postId = newButton.dataset.postId;
        if (postId) {
            const isLiked = isPostLiked(postId);
            updateLikeButtonAppearance(newButton, isLiked);
        }

        // Ajouter le nouvel écouteur d'événements
        newButton.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const postId = this.dataset.postId;
            if (!postId) {
                console.error('Erreur: postId indéfini ou vide sur le bouton like.', this);
                showNotification('Erreur technique: impossible de liker ce post.', 'danger');
                return;
            }
            
            console.log('Clic sur le bouton like pour le post', postId);
            
            try {
                const response = await fetch(`/post/${postId}/like`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        // Mettre à jour la persistance locale
                        if (data.isLiked) {
                            addLikedPost(postId);
                        } else {
                            removeLikedPost(postId);
                        }
                        
                        // Mettre à jour l'apparence du bouton
                        updateLikeButtonAppearance(this, data.isLiked);
                        
                        // Mise à jour du compteur de likes
                        const likesSummary = this.closest('.post-card').querySelector('.likes-summary');
                        if (likesSummary && data.likesCount !== undefined) {
                            if (data.likesCount > 0) {
                                likesSummary.innerHTML = `<span class="likes-count me-1">${data.likesCount}</span> j'aime${data.likesCount > 1 ? 's' : ''}`;
                            } else {
                                likesSummary.innerHTML = '';
                            }
                        }
                        
                        console.log(`Post ${data.isLiked ? 'liké' : 'unliké'} avec succès!`);
                    } else if (data.message) {
                        showNotification(data.message, 'warning');
                    }
                } else {
                    const errorData = await response.json().catch(() => ({ message: 'Erreur de serveur' }));
                    throw new Error(errorData.message || 'Erreur lors de l\'interaction avec le serveur');
                }
            } catch (error) {
                console.error('Erreur lors du like:', error);
                showNotification('Impossible de liker ce post. Veuillez réessayer.', 'danger');
            }
        });
    });

    // Synchroniser les likes avec le serveur
    async function syncLikesWithServer() {
        const likedPosts = getLikedPosts();
        if (likedPosts.length === 0) return;

        try {
            const response = await fetch('/post/sync-likes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ likedPosts })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Mettre à jour la liste des likes avec les données du serveur
                    setLikedPosts(data.serverLikedPosts || []);
                    
                    // Mettre à jour l'apparence de tous les boutons
                    document.querySelectorAll('.like-button').forEach(button => {
                        const postId = button.dataset.postId;
                        if (postId) {
                            const isLiked = isPostLiked(postId);
                            updateLikeButtonAppearance(button, isLiked);
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Erreur lors de la synchronisation des likes:', error);
        }
    }

    // Synchroniser les likes au chargement de la page
    syncLikesWithServer();
    
    // Rendre les fonctions disponibles globalement
    window.showNotification = showNotification;
}

// Ne pas auto-initialiser ici, cela sera fait depuis main.js
