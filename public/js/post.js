document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour le bouton "Intéressant" en haut
    document.querySelectorAll('.interest-reaction-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const postId = this.closest('.post-card').dataset.postId;
            await handleReaction(postId, 'interesting', this.closest('.post-card'));
        });
    });

    // Gestionnaire des réactions du menu
    document.querySelectorAll('.reaction-trigger').forEach(trigger => {
        trigger.addEventListener('click', function() {
            const container = this.closest('.reaction-container');
            const menu = container.querySelector('.reaction-menu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('.reaction-option').forEach(option => {
        option.addEventListener('click', async function() {
            const postId = this.closest('.post-card').dataset.postId;
            const reactionType = this.dataset.reactionType;
            await handleReaction(postId, reactionType, this.closest('.post-card'));
        });
    });

    // Fonction commune pour gérer les réactions
    async function handleReaction(postId, reactionType, postCard) {
        try {
            const response = await fetch(`/post/${postId}/reaction`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ type: reactionType })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Mettre à jour les deux emplacements de réaction
                    updateReactionUI(postCard, data);
                    updateInterestButton(postCard, data);
                } else {
                    throw new Error(data.message || 'Une erreur est survenue');
                }
            } else {
                throw new Error('Erreur lors de la réaction');
            }
        } catch (error) {
            console.error('Erreur lors de la réaction:', error);
            showNotification('Une erreur est survenue', 'danger');
        }
    }

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
    document.querySelectorAll('.share-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const comment = this.querySelector('textarea[name="share-comment"]').value;

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
                    const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                    if (modal) {
                        modal.hide();
                    }

                    // Réinitialiser le formulaire
                    this.reset();

                    // Afficher une notification de succès
                    showNotification('Publication partagée avec succès', 'success');
                } else {
                    showNotification(data.message || 'Une erreur est survenue lors du partage', 'danger');
                }
            } catch (error) {
                console.error('Erreur lors du partage:', error);
                showNotification('Une erreur est survenue lors du partage', 'danger');
            }
        });
    });

    // Gestionnaire de suppression
    document.querySelectorAll('.delete-post').forEach(link => {
        link.addEventListener('click', async function(e) {
            e.preventDefault();
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette publication ?')) return;

            const postId = this.dataset.postId;
            const token = this.dataset.token;

            try {
                const response = await fetch(`/post/${postId}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `_token=${encodeURIComponent(token)}`
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.closest('.post-card').remove();
                        showNotification('Publication supprimée avec succès', 'success');
                    } else {
                        throw new Error(data.message || 'Erreur lors de la suppression');
                    }
                } else {
                    throw new Error('Erreur lors de la suppression');
                }
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
                showNotification('Une erreur est survenue', 'danger');
            }
        });
    });

    // Gestionnaire de modification
    document.querySelectorAll('.edit-post').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const modal = new bootstrap.Modal(document.querySelector(`#editPostModal${postId}`));
            modal.show();
        });
    });

    // Gestionnaire du formulaire de modification
    document.querySelectorAll('.edit-post-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const formData = new FormData(this);

            try {
                const response = await fetch(`/post/${postId}/edit`, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        // Fermer la modale
                        const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                        if (modal) {
                            modal.hide();
                        }

                        // Mettre à jour le contenu du post
                        if (data.html) {
                            const oldPost = document.querySelector(`.post-card[data-post-id="${postId}"]`);
                            oldPost.outerHTML = data.html;
                        }

                        showNotification('Publication modifiée avec succès', 'success');
                    } else {
                        throw new Error(data.message || 'Une erreur est survenue');
                    }
                } else {
                    throw new Error('Erreur lors de la modification');
                }
            } catch (error) {
                console.error('Erreur lors de la modification:', error);
                showNotification('Une erreur est survenue', 'danger');
            }
        });
    });

    function updateInterestButton(postCard, data) {
        const interestButton = postCard.querySelector('.interest-reaction-btn');
        if (interestButton) {
            interestButton.classList.toggle('active', data.currentUserReaction === 'interesting');
        }
    }

    function updateReactionUI(postCard, data) {
        const reactionTrigger = postCard.querySelector('.reaction-trigger');
        const reactionMenu = postCard.querySelector('.reaction-menu');
        const reactionSummary = postCard.querySelector('.reaction-summary');
        
        if (reactionTrigger && data.currentUserReaction) {
            const reactionDetails = {
                'like': {'emoji': '👍', 'name': 'J\'aime', 'class': 'btn-primary'},
                'congrats': {'emoji': '👏', 'name': 'Bravo', 'class': 'btn-success'},
                'support': {'emoji': '❤️', 'name': 'Soutien', 'class': 'btn-danger'},
                'interesting': {'emoji': '💡', 'name': 'Intéressant', 'class': 'btn-info'},
                'encouraging': {'emoji': '💪', 'name': 'Encouragement', 'class': 'btn-warning'}
            };
            
            const defaultReaction = {'emoji': '👍', 'name': 'Réagir', 'class': 'btn-outline-secondary'};
            const activeReaction = data.currentUserReaction ? reactionDetails[data.currentUserReaction] : defaultReaction;
            
            // Mettre à jour le bouton de réaction
            reactionTrigger.className = `btn btn-sm ${activeReaction.class} reaction-trigger rounded-pill px-3 d-flex align-items-center`;
            reactionTrigger.innerHTML = `
                <span class="reaction-emoji me-1">${activeReaction.emoji}</span>
                <span class="reaction-name">${activeReaction.name}</span>
            `;
            
            // Fermer le menu de réactions
            if (reactionMenu) {
                reactionMenu.style.display = 'none';
            }
        }
        
        // Mettre à jour le résumé des réactions
        if (reactionSummary && data.totalReactions !== undefined) {
            if (data.totalReactions > 0) {
                reactionSummary.innerHTML = `<span class="likes-count me-1">${data.totalReactions}</span> réaction${data.totalReactions > 1 ? 's' : ''}`;
            } else {
                reactionSummary.innerHTML = '';
            }
        }
    }

    function showNotification(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        const container = document.getElementById('toast-container');
        if (container) {
            container.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Supprimer le toast après qu'il soit caché
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });
        }
    }
}); 