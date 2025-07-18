document.addEventListener('DOMContentLoaded', function() {
    const postForm = document.querySelector('#post-form');
    const feedContainer = document.querySelector('#feed-container');
    const MAX_CHARACTERS = 500;
    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    
    // Ne pas continuer avec la fonctionnalité de chargement infini si feedContainer n'existe pas
    if (!feedContainer) {
        console.log('Feed container non trouvé, fonctionnalité de chargement infini désactivée.');
        hasMore = false;
    }

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

    // Initialiser la troncature pour tous les posts existants
    document.querySelectorAll('.post-content').forEach(initializePostTruncation);

    // Initialiser l'état des likes pour tous les posts existants
    document.querySelectorAll('.like-button').forEach(button => {
        const postId = button.dataset.postId;
        if (postId) {
            const isLiked = isPostLiked(postId);
            updateLikeButtonAppearance(button, isLiked);
        }
    });

    // Fonction pour synchroniser l'état des likes avec le serveur
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

    // Fonction pour tronquer le contenu des posts
    function initializePostTruncation(contentElement) {
        const content = contentElement.getAttribute('data-full-content') || contentElement.textContent;
        const maxLength = 300;
        
        if (content.length > maxLength) {
            const truncatedText = content.substring(0, maxLength) + '...';
            const postId = contentElement.closest('.post-card').dataset.postId;
            
            // Sauvegarder le contenu complet
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
        // Likes
        const likeButton = postElement.querySelector('.like-button');
        if (likeButton) {
            // Initialiser l'état du bouton like avec la persistance
            const postId = likeButton.dataset.postId;
            if (postId) {
                const isLiked = isPostLiked(postId);
                updateLikeButtonAppearance(likeButton, isLiked);
            }

            likeButton.addEventListener('click', async function(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                if (!postId) {
                    console.error('Erreur: postId indéfini ou vide sur le bouton like.', this);
                    alert('Erreur technique: impossible de liker ce post.');
                    return;
                }
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
                    }
                }
            });
        }

        // Commentaires
        const commentForm = postElement.querySelector('.comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', async function(e) {
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
        if (isLoading || !hasMore || !feedContainer) return;
        
        isLoading = true;
        currentPage++;

        try {
            const response = await fetch(`/post/feed?page=${currentPage}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                console.error('Erreur HTTP:', response.status, response.statusText);
                throw new Error(`Erreur réseau: ${response.status}`);
            }

            // Vérifier le type de contenu
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Réponse non-JSON reçue:', contentType);
                throw new Error('Réponse non-JSON reçue');
            }

            const data = await response.json();
            
            if (data.html && feedContainer) {
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
                    
                    // Initialiser la troncature pour le nouveau post
                    const postContent = post.querySelector('.post-content');
                    if (postContent) {
                        initializePostTruncation(postContent);
                    }
                    
                    // Initialiser l'état des likes
                    const likeButton = post.querySelector('.like-button');
                    if (likeButton) {
                        const postId = likeButton.dataset.postId;
                        if (postId) {
                            const isLiked = isPostLiked(postId);
                            updateLikeButtonAppearance(likeButton, isLiked);
                        }
                    }
                });
            }

            hasMore = data.hasMore;
        } catch (error) {
            console.error('Erreur lors du chargement des posts:', error);
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
        if (posts && posts.length > 0) {
            observer.observe(posts[posts.length - 1]);
        }
    }

    // Observer le dernier post initial
    if (feedContainer) {
        observeLastPost();
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

    // Rendre la fonction disponible globalement
    window.showNotification = showNotification;

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