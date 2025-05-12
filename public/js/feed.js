document.addEventListener('DOMContentLoaded', function() {
    const postForm = document.querySelector('#post-form');
    const feedContainer = document.querySelector('#feed-container');
    const MAX_CHARACTERS = 500;
    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;

    // Initialiser la troncature pour tous les posts existants
    document.querySelectorAll('.post-content').forEach(initializePostTruncation);

    if (postForm) {
        postForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');

            // Désactiver le bouton pendant la soumission
            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const response = await fetch('/post/create', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Réinitialiser le formulaire
                    this.reset();

                    // Ajouter le nouveau post au début du fil d'actualité
                    if (feedContainer && data.html) {
                        // Créer un conteneur temporaire pour le nouveau post
                        const tempContainer = document.createElement('div');
                        tempContainer.innerHTML = data.html;
                        const newPost = tempContainer.firstElementChild;

                        // Ajouter une classe pour l'animation
                        newPost.classList.add('new-post');

                        // Insérer le nouveau post au début du feed
                        feedContainer.insertBefore(newPost, feedContainer.firstChild);

                        // Initialiser la troncature pour le nouveau post
                        const postContent = newPost.querySelector('.post-content');
                        if (postContent) {
                            initializePostTruncation(postContent);
                        }

                        // Déclencher l'animation après un court délai
                        setTimeout(() => {
                            newPost.classList.add('show');
                        }, 10);

                        // Initialiser les gestionnaires d'événements pour le nouveau post
                        initializePostInteractions(newPost);
                    }

                    // Afficher une notification de succès
                    showNotification('Publication créée avec succès', 'success');
                } else {
                    throw new Error(data.message || 'Erreur lors de la création de la publication');
                }
            } catch (error) {
                console.error('Erreur lors de la création de la publication:', error);
                showNotification('Une erreur est survenue lors de la création de la publication', 'danger');
            } finally {
                // Réactiver le bouton
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    }

    // Fonction pour initialiser la troncature d'un post
    function initializePostTruncation(contentElement) {
        const postCard = contentElement.closest('.post-card');
        const postId = postCard ? postCard.dataset.postId : null;
        const content = contentElement.textContent.trim();

        // Vérifier si le contenu dépasse le nombre maximum de caractères
        if (content.length > MAX_CHARACTERS) {
            // Tronquer le texte
            const truncatedText = content.substring(0, MAX_CHARACTERS).trim() + '...';
            
            // Sauvegarder le contenu original dans un attribut data
            contentElement.setAttribute('data-full-content', content);
            
            // Mettre à jour le contenu avec la version tronquée
            contentElement.textContent = truncatedText;
            
            // Ajouter la classe pour le style
            contentElement.classList.add('truncated');

            // Créer le lien "Voir plus"
            const readMoreLink = document.createElement('a');
            readMoreLink.href = `/post/${postId}`;
            readMoreLink.className = 'read-more-link';
            readMoreLink.innerHTML = `
                <span class="read-more-text">Voir l'article complet</span>
                <i class="bi bi-arrow-right"></i>
            `;
            
            // Insérer le lien après le contenu
            contentElement.parentNode.insertBefore(readMoreLink, contentElement.nextSibling);
        }
    }

    // Fonction pour initialiser les interactions sur un post
    function initializePostInteractions(postElement) {
        // Réactions
        const reactionTrigger = postElement.querySelector('.reaction-trigger');
        if (reactionTrigger) {
            reactionTrigger.addEventListener('click', function() {
                const container = this.closest('.reaction-container');
                const menu = container.querySelector('.reaction-menu');
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            });
        }

        // Options de réaction
        const reactionOptions = postElement.querySelectorAll('.reaction-option');
        reactionOptions.forEach(option => {
            option.addEventListener('click', async function() {
                const postId = this.closest('.post-card').dataset.postId;
                const reactionType = this.dataset.reactionType;
                
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
                        updateReactionUI(this.closest('.post-card'), data);
                    }
                } catch (error) {
                    console.error('Erreur lors de la réaction:', error);
                    showNotification('Une erreur est survenue', 'danger');
                }
            });
        });

        // Commentaires
        const commentForm = postElement.querySelector('.comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', async function(e) {
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
                            
                            if (data.html) {
                                commentsSection.insertAdjacentHTML('beforeend', data.html);
                            }
                            
                            const commentCount = document.querySelector(`.post-card[data-post-id="${postId}"] .comments-count`);
                            if (commentCount && data.commentsCount !== undefined) {
                                commentCount.textContent = `${data.commentsCount} commentaire${data.commentsCount > 1 ? 's' : ''}`;
                            }
                            
                            input.value = '';
                        }
                    }
                } catch (error) {
                    console.error('Erreur lors de l\'ajout du commentaire:', error);
                    showNotification('Une erreur est survenue', 'danger');
                }
            });
        }
    }

    // Fonction pour charger plus de posts
    async function loadMorePosts() {
        if (isLoading || !hasMore) return;
        
        isLoading = true;
        currentPage++;

        try {
            const response = await fetch(`/post/feed?page=${currentPage}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Erreur réseau');

            const data = await response.json();
            
            if (data.html) {
                const tempContainer = document.createElement('div');
                tempContainer.innerHTML = data.html;
                
                // Ajouter chaque post individuellement avec une animation
                Array.from(tempContainer.children).forEach(post => {
                    post.classList.add('new-post');
                    feedContainer.appendChild(post);
                    
                    // Animation d'apparition
                    setTimeout(() => post.classList.add('show'), 10);
                    
                    // Initialiser les interactions pour le nouveau post
                    initializePostInteractions(post);
                });
            }

            hasMore = data.hasMore;
            
            if (!hasMore) {
                // Afficher un message "Fin des publications"
                const endMessage = document.createElement('div');
                endMessage.className = 'text-center text-muted my-4';
                endMessage.innerHTML = 'Fin des publications';
                feedContainer.appendChild(endMessage);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des posts:', error);
            showNotification('Une erreur est survenue lors du chargement des publications', 'danger');
        } finally {
            isLoading = false;
        }
    }

    // Détecter quand l'utilisateur arrive en bas de la page
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !isLoading && hasMore) {
                loadMorePosts();
            }
        });
    }, { threshold: 0.5 });

    // Observer le dernier post
    function observeLastPost() {
        const posts = document.querySelectorAll('.post-card');
        if (posts.length > 0) {
            observer.observe(posts[posts.length - 1]);
        }
    }

    // Observer le dernier post initial
    observeLastPost();

    // Style pour l'animation des nouveaux posts
    const style = document.createElement('style');
    style.textContent = `
        .new-post {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }
        .new-post.show {
            opacity: 1;
            transform: translateY(0);
        }
    `;
    document.head.appendChild(style);
}); 