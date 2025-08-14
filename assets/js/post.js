document.addEventListener('DOMContentLoaded', function() {
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

    // Initialiser l'état des likes pour tous les boutons existants
    document.querySelectorAll('.like-button').forEach(button => {
        const postId = button.dataset.postId;
        if (postId) {
            const isLiked = isPostLiked(postId);
            updateLikeButtonAppearance(button, isLiked);
        }
    });

    // Gestion des likes déplacée dans like-fix.js
    console.log('La gestion des likes est maintenant dans le module like-fix.js');

    // Gestionnaire des commentaires - SUPPRIMÉ pour éviter les conflits avec feed.js
    // Les commentaires sont maintenant gérés exclusivement par feed.js
    
    // Gestionnaire de partage
    document.querySelectorAll('.share-submit').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            if (!postId) {
                console.error('Erreur: postId indéfini ou vide pour le partage.', this);
                alert('Erreur technique: impossible de partager ce post.');
                return;
            }
            const modal = this.closest('.modal');
            const form = modal.querySelector('.share-form');
            const comment = form.querySelector('textarea[name="comment"]').value;

            try {
                const response = await fetch(`/post/${postId}/share`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ comment })
                });

                const data = await response.json();

                if (response.ok) {
                    // Mettre à jour le compteur de partages
                    const sharesCountElement = document.querySelector(`[data-post-id="${postId}"] .shares-count`);
                    if (sharesCountElement) {
                        const newCount = data.sharesCount;
                        sharesCountElement.textContent = `${newCount} partage${newCount > 1 ? 's' : ''}`;
                    }

                    // Fermer la modale
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    // Réinitialiser le formulaire
                    form.reset();
                } else {
                    showNotification(data.message || 'Une erreur est survenue lors du partage', 'danger');
                }
            } catch (error) {
                console.error('Erreur lors du partage:', error);
                showNotification('Une erreur est survenue lors du partage', 'danger');
            }
        });
    });

    // Gestionnaire de suppression de posts
    document.querySelectorAll('.delete-post').forEach(button => {
        // Supprimer l'attribut href pour éviter la redirection
        if (button.hasAttribute('href')) {
            button.setAttribute('href', 'javascript:void(0)');
        }
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Clic sur bouton de suppression détecté');
            
            const postId = this.dataset.postId;
            if (!postId) {
                console.error('Erreur: postId indéfini ou vide pour la suppression.', this);
                alert('Erreur technique: impossible de supprimer ce post.');
                return;
            }
            const token = this.dataset.token;

            if (confirm('Êtes-vous sûr de vouloir supprimer cette publication ?')) {
                console.log('Suppression en cours du post', postId);
                fetch(`/post/${postId}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ _token: token })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const postCard = document.querySelector(`.post-card[data-post-id="${postId}"]`);
                        if (postCard) {
                            postCard.remove();
                        }
                        showNotification('Publication supprimée avec succès', 'success');
                    } else {
                        alert(data.message || 'Une erreur est survenue');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue');
                });
            }
        });
    });

    // Fonction showNotification supprimée - elle est déjà définie dans feed.js
});

export default {}; 