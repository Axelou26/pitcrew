document.addEventListener('DOMContentLoaded', function() {
    console.log('Script post-interactions.js chargé');
    
    // Initialiser les tooltips Bootstrap
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
    
    // Gestion des réactions
    document.querySelectorAll('.reaction-container').forEach(container => {
        const trigger = container.querySelector('.reaction-trigger');
        const menu = container.querySelector('.reaction-menu');
        let timeoutId;
        
        // Gérer le clic sur le bouton principal
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Si une réaction est déjà active, on envoie la même réaction pour la supprimer
            if (this.classList.contains('active-reaction')) {
                const postCard = this.closest('.post-card');
                const postId = postCard.dataset.postId;
                const currentReactionType = this.dataset.currentReaction;
                
                handleReaction(postId, currentReactionType, this, postCard);
            } else {
                // Afficher le menu des réactions
                menu.classList.add('show');
                menu.style.display = 'block';
            }
        });
        
        // Ouvrir le menu au survol du bouton
        container.addEventListener('mouseenter', function() {
            // Ne pas ouvrir le menu si une réaction est déjà active
            if (!trigger.classList.contains('active-reaction')) {
                clearTimeout(timeoutId);
                menu.classList.add('show');
                menu.style.display = 'block';
            }
        });
        
        // Fermer le menu avec un délai quand la souris quitte
        container.addEventListener('mouseleave', function() {
            timeoutId = setTimeout(() => {
                menu.classList.remove('show');
                menu.style.display = 'none';
            }, 800);
        });
        
        // Garder le menu ouvert quand la souris est dessus
        menu.addEventListener('mouseenter', function() {
            clearTimeout(timeoutId);
        });
        
        // Gérer les clics sur les options de réaction
        container.querySelectorAll('.reaction-option').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const postCard = this.closest('.post-card');
                const postId = postCard.dataset.postId;
                const reactionType = this.dataset.reactionType;

                // Cacher le tooltip
                const tooltip = bootstrap.Tooltip.getInstance(this);
                if (tooltip) {
                    tooltip.hide();
                }

                handleReaction(postId, reactionType, trigger, postCard);
            });
        });

        // Fonction pour gérer les réactions
        function handleReaction(postId, reactionType, trigger, postCard) {
            fetch(`/post/${postId}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'reactionType': reactionType
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const reactionSummary = postCard.querySelector('.reaction-summary');
                    
                    // Mettre à jour le compteur de réactions
                    if (data.likesCount > 0) {
                        reactionSummary.innerHTML = `<span class="likes-count">${data.likesCount}</span> réaction${data.likesCount > 1 ? 's' : ''}`;
                        reactionSummary.style.display = '';
                    } else {
                        reactionSummary.style.display = 'none';
                    }

                    // Définir les classes et le contenu en fonction du type de réaction
                    const reactionMap = {
                        'like': { emoji: '👍', name: 'J\'aime', class: 'btn-primary' },
                        'congrats': { emoji: '👏', name: 'Bravo', class: 'btn-success' },
                        'interesting': { emoji: '💡', name: 'Intéressant', class: 'btn-info' },
                        'support': { emoji: '❤️', name: 'Soutien', class: 'btn-danger' },
                        'encouraging': { emoji: '💪', name: 'Encouragement', class: 'btn-warning' }
                    };

                    const defaultReaction = { emoji: '👍', name: 'Réagir', class: 'btn-outline-secondary' };
                    const reaction = data.activeReactionType ? reactionMap[data.activeReactionType] : defaultReaction;

                    // Mettre à jour le bouton
                    trigger.className = `btn btn-sm ${reaction.class} reaction-trigger rounded-pill px-3 d-flex align-items-center`;
                    const reactionEmoji = trigger.querySelector('.reaction-emoji');
                    const reactionName = trigger.querySelector('.reaction-name');

                    if (data.activeReactionType) {
                        trigger.classList.add('active-reaction');
                        trigger.dataset.currentReaction = data.activeReactionType;
                        reactionEmoji.textContent = reaction.emoji;
                        reactionName.textContent = reaction.name;
                    } else {
                        trigger.classList.remove('active-reaction');
                        delete trigger.dataset.currentReaction;
                        reactionEmoji.textContent = defaultReaction.emoji;
                        reactionName.textContent = defaultReaction.name;
                    }

                    // Fermer le menu
                    menu.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la réaction.');
            });
        }
    });

    // Fermer les menus de réaction quand on clique ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.reaction-container')) {
            document.querySelectorAll('.reaction-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });

    // Gestion des commentaires
    document.querySelectorAll('.comment-toggle-button').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const commentsSection = document.querySelector(`#comments-${postId}`);
            
            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
            } else {
                commentsSection.style.display = 'none';
            }
        });
    });

    // Soumission des commentaires
    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const input = this.querySelector('.comment-input');
            const content = input.value.trim();

            if (!content) return;

            fetch(`/post/${postId}/comment/add`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    content: content
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentsList = this.closest('.comments-section').querySelector('.comments-list');
                    const commentsPlaceholder = commentsList.querySelector('.comments-placeholder');
                    
                    // Cacher le placeholder s'il est visible
                    if (commentsPlaceholder) {
                        commentsPlaceholder.classList.add('d-none');
                    }

                    // Ajouter le nouveau commentaire à la liste
                    commentsList.insertAdjacentHTML('beforeend', data.commentHtml);

                    // Mettre à jour le compteur de commentaires
                    const commentsCount = this.closest('.post-card').querySelector('.comments-count');
                    const newCount = parseInt(commentsCount.textContent) + 1;
                    commentsCount.textContent = `${newCount} commentaire${newCount > 1 ? 's' : ''}`;

                    // Vider le champ de saisie
                    input.value = '';

                    // Faire défiler jusqu'au nouveau commentaire
                    const newComment = commentsList.lastElementChild;
                    newComment.scrollIntoView({ behavior: 'smooth' });
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'ajout du commentaire.');
            });
        });
    });

    // Gestion du partage
    document.querySelectorAll('.share-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const comment = this.querySelector('[name="share-comment"]').value;

            fetch(`/post/${postId}/share`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    comment: comment
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                    // Fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.querySelector(`#shareModal${postId}`));
                    modal.hide();
                }
            });
        });
    });

    // Gestion de la modification des posts
    document.querySelectorAll('.edit-post').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const modal = new bootstrap.Modal(document.querySelector(`#editPostModal${postId}`));
            modal.show();
        });
    });

    // Soumission du formulaire de modification
    document.querySelectorAll('.edit-post-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const formData = new FormData(this);

            fetch(`/post/${postId}/edit`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });

    // Gestion de la suppression des posts
    document.querySelectorAll('.delete-post').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette publication ?')) {
                return;
            }

            const postId = this.dataset.postId;
            fetch(`/post/${postId}/delete`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const postCard = this.closest('.post-card');
                    postCard.remove();
                }
            });
        });
    });

    // Gestion de la suppression des commentaires
    document.querySelectorAll('.delete-comment').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) {
                return;
            }

            const commentId = this.dataset.commentId;
            const commentElement = this.closest('.comment');
            const postCard = this.closest('.post-card');
            const commentsCountSpan = postCard.querySelector('.comments-count');
            const commentsPlaceholder = postCard.querySelector('.comments-placeholder');

            fetch(`/comment/${commentId}/delete`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer le commentaire de l'affichage
                    commentElement.remove();

                    // Mettre à jour le compteur de commentaires
                    const commentsCount = data.commentsCount;
                    commentsCountSpan.textContent = `${commentsCount} ${commentsCount > 1 ? 'commentaires' : 'commentaire'}`;

                    // Afficher le placeholder si plus aucun commentaire
                    if (commentsCount === 0) {
                        commentsPlaceholder.classList.remove('d-none');
                    }
                }
            });
        });
    });
}); 