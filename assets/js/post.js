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

    // Gestion des likes
    document.querySelectorAll('.like-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            if (!postId) {
                console.error('Erreur: postId indéfini ou vide sur le bouton like.', this);
                alert('Erreur technique: impossible de liker ce post.');
                return;
            }
            
            fetch(`/post/${postId}/like`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
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
                    const likesSummary = this.closest('.post-card')?.querySelector('.likes-summary');
                    if (likesSummary && data.likesCount !== undefined) {
                        if (data.likesCount > 0) {
                            likesSummary.innerHTML = `<span class="likes-count me-1">${data.likesCount}</span> j'aime${data.likesCount > 1 ? 's' : ''}`;
                        } else {
                            likesSummary.innerHTML = '';
                        }
                    }
                } else {
                    alert(data.message || 'Une erreur est survenue');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        });
    });

    // Gestionnaire des commentaires
    document.querySelectorAll('.comment-toggle-button').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const commentsSection = document.querySelector(`#comments-${postId}`);
            commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            if (!postId) {
                console.error('Erreur: postId indéfini ou vide pour le commentaire.', this);
                alert('Erreur technique: impossible d\'ajouter un commentaire.');
                return;
            }
            const input = this.querySelector('.comment-input');
            const content = input.value.trim();

            if (!content) return;

            try {
                const response = await fetch(`/post/${postId}/comment/add`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ content })
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        const commentsSection = document.querySelector(`#comments-${postId} .comments-list`);
                        const placeholder = commentsSection.querySelector('.comments-placeholder');
                        
                        if (placeholder) {
                            placeholder.classList.add('d-none');
                        }
                        
                        // Ajouter le nouveau commentaire
                        if (data.html) {
                            commentsSection.insertAdjacentHTML('beforeend', data.html);
                        }
                        
                        // Mettre à jour le compteur de commentaires
                        const commentCount = document.querySelector(`.post-card[data-post-id="${postId}"] .comments-count`);
                        if (commentCount && data.commentsCount !== undefined) {
                            commentCount.textContent = `${data.commentsCount} commentaire${data.commentsCount > 1 ? 's' : ''}`;
                        }
                        
                        // Vider le champ de saisie
                        input.value = '';
                    } else {
                        throw new Error(data.error || 'Une erreur est survenue');
                    }
                } else {
                    throw new Error('Erreur lors de l\'ajout du commentaire');
                }
            } catch (error) {
                console.error('Erreur lors de l\'ajout du commentaire:', error);
                showNotification('Une erreur est survenue', 'danger');
            }
        });
    });

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
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            if (!postId) {
                console.error('Erreur: postId indéfini ou vide pour la suppression.', this);
                alert('Erreur technique: impossible de supprimer ce post.');
                return;
            }
            const token = this.dataset.token;

            if (confirm('Êtes-vous sûr de vouloir supprimer cette publication ?')) {
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
                        const postCard = document.querySelector(`[data-post-id="${postId}"]`);
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

    // Fonction pour afficher les notifications
    function showNotification(message, type = 'info') {
        // Créer une notification Bootstrap
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
}); 