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
                showNotification('Une erreur est survenue lors de la suppression', 'danger');
            }
        });
    });

    // Gestionnaire de suppression des commentaires
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.delete-comment')) {
            const button = e.target.closest('.delete-comment');
            const commentId = button.dataset.commentId;
            const token = button.dataset.token;
            const comment = document.querySelector(`[data-comment-id="${commentId}"]`);
            const postId = comment.closest('.post-card').dataset.postId;

            if (!confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) return;

            try {
                const response = await fetch(`/comment/${commentId}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `_token=${encodeURIComponent(token)}`
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        // Supprimer le commentaire avec une animation
                        comment.style.transition = 'all 0.3s ease';
                        comment.style.opacity = '0';
                        comment.style.transform = 'translateX(-10px)';
                        
                        setTimeout(() => {
                            comment.remove();
                            
                            // Mettre à jour le compteur de commentaires
                            const commentCount = document.querySelector(`.post-card[data-post-id="${postId}"] .comments-count`);
                            if (commentCount && data.commentsCount !== undefined) {
                                commentCount.textContent = `${data.commentsCount} commentaire${data.commentsCount > 1 ? 's' : ''}`;
                            }

                            // Afficher le placeholder si plus de commentaires
                            if (data.commentsCount === 0) {
                                const commentsSection = document.querySelector(`#comments-${postId} .comments-list`);
                                const placeholder = commentsSection.querySelector('.comments-placeholder');
                                if (placeholder) {
                                    placeholder.classList.remove('d-none');
                                }
                            }
                        }, 300);

                        showNotification('Commentaire supprimé avec succès', 'success');
                    } else {
                        throw new Error(data.message || 'Erreur lors de la suppression');
                    }
                } else {
                    throw new Error('Erreur lors de la suppression');
                }
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
                showNotification('Une erreur est survenue lors de la suppression', 'danger');
            }
        }
    });

    // Gestionnaire de modification des posts
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-post')) {
            e.preventDefault();
            const postId = e.target.closest('.edit-post').dataset.postId;
            const modal = document.querySelector(`#editPostModal${postId}`);
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }
    });

    document.querySelectorAll('.edit-post-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Désactiver le bouton pendant la soumission
            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const response = await fetch(`/post/${postId}/edit`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();
                
                if (response.ok && data.success) {
                    // Mettre à jour le contenu du post
                    const postCard = document.querySelector(`.post-card[data-post-id="${postId}"]`);
                    if (postCard) {
                        const titleElement = postCard.querySelector('.card-title');
                        const contentElement = postCard.querySelector('.post-content');
                        
                        // Mettre à jour le titre
                        const title = formData.get('title');
                        if (titleElement) {
                            if (title) {
                                titleElement.textContent = title;
                                titleElement.classList.remove('d-none');
                            } else {
                                titleElement.classList.add('d-none');
                            }
                        } else if (title) {
                            const titleDiv = document.createElement('h5');
                            titleDiv.className = 'card-title mb-2 fw-bold';
                            titleDiv.textContent = title;
                            contentElement.parentNode.insertBefore(titleDiv, contentElement);
                        }
                        
                        // Mettre à jour le contenu
                        if (contentElement) {
                            contentElement.innerHTML = formData.get('content').replace(/\n/g, '<br>');
                        }
                        
                        // Fermer la modal
                        const modal = bootstrap.Modal.getInstance(document.querySelector(`#editPostModal${postId}`));
                        if (modal) {
                            modal.hide();
                        }
                        
                        showNotification('Publication modifiée avec succès', 'success');
                    } else {
                        // Si on ne trouve pas la carte du post, on recharge la page
                        window.location.reload();
                    }
                } else {
                    showNotification(data.message || 'Erreur lors de la modification', 'danger');
                    console.error('Erreur de modification:', data);
                }
            } catch (error) {
                console.error('Erreur lors de la modification:', error);
                showNotification('Une erreur est survenue lors de la modification', 'danger');
            } finally {
                // Réactiver le bouton
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    });

    // Gestionnaire des suggestions de mentions et hashtags
    document.querySelectorAll('.mention-input').forEach(input => {
        const suggestionContainer = input.parentNode.querySelector('.mention-suggestions');
        let selectedIndex = -1;

        input.addEventListener('input', async function(e) {
            const cursorPos = this.selectionStart;
            const content = this.value;
            const beforeCursor = content.slice(0, cursorPos);
            
            // Recherche du dernier @ ou # avant le curseur
            const lastMentionMatch = beforeCursor.match(/(@\w*)$/);
            const lastHashtagMatch = beforeCursor.match(/(#\w*)$/);
            
            if (lastMentionMatch) {
                const searchTerm = lastMentionMatch[1].slice(1); // Enlever le @
                if (searchTerm.length > 0) {
                    try {
                        const response = await fetch(`/api/mention-suggestions?q=${encodeURIComponent(searchTerm)}`);
                        const data = await response.json();
                        if (data.success) {
                            showSuggestions(suggestionContainer, data.results.map(user => ({
                                text: `${user.firstName} ${user.lastName}`,
                                displayText: `${user.firstName} ${user.lastName}${user.profilePicture ? ' 🖼️' : ''}`,
                                prefix: '@'
                            })), selectedIndex);
                        }
                    } catch (error) {
                        console.error('Erreur lors de la recherche des mentions:', error);
                    }
                } else {
                    hideSuggestions(suggestionContainer);
                }
            } else if (lastHashtagMatch) {
                const searchTerm = lastHashtagMatch[1].slice(1); // Enlever le #
                if (searchTerm.length > 0) {
                    try {
                        const response = await fetch(`/api/hashtag-suggestions?q=${encodeURIComponent(searchTerm)}`);
                        const data = await response.json();
                        if (data.success) {
                            showSuggestions(suggestionContainer, data.results.map(hashtag => ({
                                text: hashtag.name,
                                displayText: `#${hashtag.name} (${hashtag.usageCount} utilisations)`,
                                prefix: '#'
                            })), selectedIndex);
                        }
                    } catch (error) {
                        console.error('Erreur lors de la recherche des hashtags:', error);
                    }
                } else {
                    hideSuggestions(suggestionContainer);
                }
            } else {
                hideSuggestions(suggestionContainer);
            }
        });

        // Gérer la sélection avec les flèches et l'insertion avec Entrée
        input.addEventListener('keydown', function(e) {
            const suggestions = suggestionContainer.querySelectorAll('.mention-item');
            if (suggestions.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % suggestions.length;
                updateSelection(suggestionContainer, selectedIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + suggestions.length) % suggestions.length;
                updateSelection(suggestionContainer, selectedIndex);
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                const selectedSuggestion = suggestionContainer.querySelector('.mention-item.selected');
                if (selectedSuggestion) {
                    const suggestionData = JSON.parse(selectedSuggestion.dataset.suggestion);
                    insertSuggestion(input, suggestionData);
                }
            }
        });
    });

    function showSuggestions(container, suggestions, selectedIndex) {
        container.innerHTML = '';
        if (suggestions.length === 0) {
            hideSuggestions(container);
            return;
        }

        suggestions.forEach((suggestion, index) => {
            const div = document.createElement('div');
            div.className = 'mention-item';
            div.innerHTML = suggestion.displayText;
            div.dataset.suggestion = JSON.stringify(suggestion);
            if (index === selectedIndex) {
                div.classList.add('selected');
            }
            div.addEventListener('click', () => {
                insertSuggestion(container.parentNode.querySelector('.mention-input'), suggestion);
            });
            container.appendChild(div);
        });

        container.classList.add('show');
    }

    function hideSuggestions(container) {
        container.innerHTML = '';
        container.classList.remove('show');
        selectedIndex = -1;
    }

    function updateSelection(container, index) {
        container.querySelectorAll('.mention-item').forEach((item, i) => {
            if (i === index) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }

    function insertSuggestion(input, suggestion) {
        const cursorPos = input.selectionStart;
        const content = input.value;
        const beforeCursor = content.slice(0, cursorPos);
        const afterCursor = content.slice(cursorPos);

        // Trouver le début du mot actuel
        const lastMentionMatch = beforeCursor.match(/(@\w*)$/);
        const lastHashtagMatch = beforeCursor.match(/(#\w*)$/);
        const match = lastMentionMatch || lastHashtagMatch;

        if (match) {
            const startPos = cursorPos - match[1].length;
            const newText = suggestion.prefix + suggestion.text;
            input.value = content.slice(0, startPos) + newText + ' ' + afterCursor;
            input.selectionStart = input.selectionEnd = startPos + newText.length + 1;
        }

        hideSuggestions(input.parentNode.querySelector('.mention-suggestions'));
        input.focus();
    }

    // Fonction pour mettre à jour le bouton "Intéressant"
    function updateInterestButton(postCard, data) {
        const interestButton = postCard.querySelector('.interest-reaction-btn');
        if (interestButton) {
            const isInterested = data.reaction.type === 'interesting';
            interestButton.classList.toggle('active', isInterested);
            interestButton.classList.toggle('btn-info', isInterested);
            interestButton.classList.toggle('btn-outline-secondary', !isInterested);
        }
    }

    // Fonction pour mettre à jour l'interface des réactions
    function updateReactionUI(postCard, data) {
        const trigger = postCard.querySelector('.reaction-trigger');
        const emoji = trigger.querySelector('.reaction-emoji');
        const name = trigger.querySelector('.reaction-name');
        const menu = postCard.querySelector('.reaction-menu');
        const summary = postCard.querySelector('.reaction-summary');

        // Supprimer toutes les classes btn-* existantes
        trigger.className = trigger.className.replace(/btn-(primary|success|danger|info|warning|outline-secondary)/g, '');
        
        // Ajouter les nouvelles classes
        trigger.className = `btn btn-sm ${data.reaction.class} reaction-trigger rounded-pill px-3 d-flex align-items-center`;
        
        // Ajouter ou supprimer la classe active-reaction
        if (data.reaction.type) {
            trigger.classList.add('active-reaction');
            trigger.dataset.currentReaction = data.reaction.type;
        } else {
            trigger.classList.remove('active-reaction');
            delete trigger.dataset.currentReaction;
        }

        emoji.textContent = data.reaction.emoji;
        name.textContent = data.reaction.name;
        menu.style.display = 'none';
        
        if (data.totalReactions > 0) {
            summary.innerHTML = `<span class="likes-count me-1">${data.totalReactions}</span> réaction${data.totalReactions > 1 ? 's' : ''}`;
        } else {
            summary.innerHTML = '';
        }
    }
});

function addCommentToUI(postId, data) {
    const commentsSection = document.querySelector(`#comments-${postId} .comments-list`);
    const placeholder = commentsSection.querySelector('.comments-placeholder');
    const commentCount = document.querySelector(`.post-card[data-post-id="${postId}"] .comments-count`);
    
    if (placeholder) {
        placeholder.classList.add('d-none');
    }

    const commentElement = document.createElement('div');
    commentElement.className = 'comment mb-3';
    commentElement.innerHTML = data.commentHtml;
    commentsSection.appendChild(commentElement);

    const count = parseInt(commentCount.textContent) + 1;
    commentCount.textContent = `${count} commentaire${count > 1 ? 's' : ''}`;
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
} 