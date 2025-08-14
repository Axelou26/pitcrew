// Module feed.js
export function initFeed() {
    // Rendre la fonction accessible globalement pour le script de secours
    window.initFeed = initFeed;
    
    console.log('Feed.js chargé et exécuté');
    console.log('Vérification des boutons de commentaire:', document.querySelectorAll('.comment-toggle-button').length);
    const postForm = document.querySelector('#post-form');
    const feedContainer = document.querySelector('#feed-container') || document.querySelector('.posts-container');
    const MAX_CHARACTERS = 500;
    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    
    // Ne pas continuer avec la fonctionnalité de chargement infini si feedContainer n'existe pas
    if (!feedContainer) {
        // La page actuelle n'a probablement pas de feed, donc on désactive silencieusement
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
    
    // Initialiser les interactions pour tous les posts existants
    document.querySelectorAll('.post-card').forEach(postElement => {
        initializePostInteractions(postElement);
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
        }
    }

    // Fonction pour initialiser les interactions sur un post
    function initializePostInteractions(postElement) {
        // Note: La gestion de suppression est maintenant dans post-delete.js
        
        // Likes - La gestion des likes est maintenant dans like-fix.js
        const likeButton = postElement.querySelector('.like-button');
        if (likeButton) {
            // Note: L'initialisation et la gestion des événements ont été déplacées 
            // vers le module like-fix.js pour éviter les conflits
            console.log('Bouton like détecté - sera géré par like-fix.js');
        }

        // Gestion du bouton Commenter
        const commentToggleButton = postElement.querySelector('.comment-toggle-button');
        if (!commentToggleButton) {
            console.error('Bouton de commentaires non trouvé pour le post:', postElement.dataset.postId);
            return;
        }
        
        // Log pour débogage
        console.log('Initialisation du bouton de commentaires pour le post:', postElement.dataset.postId);
        
        commentToggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Récupérer l'ID du post
            const postId = this.dataset.postId || postElement.dataset.postId;
            if (!postId) {
                console.error('Erreur: postId indéfini ou vide sur le bouton de commentaire.', this);
                return;
            }
            
            console.log('Clic sur le bouton de commentaires pour le post', postId);
            
            // Trouver la section de commentaires
            const commentsId = `comments-${postId}`;
            const commentsSection = document.getElementById(commentsId);
            
            if (!commentsSection) {
                console.error(`Section de commentaires #${commentsId} non trouvée`);
                return;
            }
            
            console.log('Section de commentaires trouvée:', commentsSection);
            
            // Vérifier l'état actuel et inverser
            const isVisible = commentsSection.style.display !== 'none';
            
            if (isVisible) {
                // Masquer la section
                console.log('Masquage de la section de commentaires');
                commentsSection.style.display = 'none';
            } else {
                // Afficher la section
                console.log('Affichage de la section de commentaires');
                commentsSection.style.display = 'block';
                
                // Charger les commentaires depuis le serveur
                loadComments(postId, commentsSection);
                
                // Mettre le focus sur le champ de commentaire
                setTimeout(() => {
                    const commentInput = commentsSection.querySelector('.comment-input');
                    if (commentInput) {
                        commentInput.focus();
                    }
                }, 100);
            }
        });

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

    // Fonction pour charger les commentaires d'un post
    async function loadComments(postId, commentsSection) {
        console.log('Fonction loadComments appelée avec postId:', postId);
        console.log('Section de commentaires:', commentsSection);
        
        try {
            // Récupérer l'élément qui contient la liste des commentaires
            let commentsListContainer = commentsSection.querySelector('.comments-list');
            console.log('Container de commentaires initial:', commentsListContainer);
            
            if (!commentsListContainer) {
                // Créer le conteneur s'il n'existe pas
                console.log('Container de commentaires non trouvé, création...');
                commentsListContainer = document.createElement('div');
                commentsListContainer.className = 'comments-list mb-3';
                commentsListContainer.style.maxHeight = '300px';
                commentsListContainer.style.overflowY = 'auto';
                commentsSection.appendChild(commentsListContainer);
            }
            
            // Ajouter un indicateur de chargement
            commentsListContainer.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm" role="status"></div> Chargement des commentaires...</div>';
            
            console.log(`Envoi de la requête AJAX vers /post/${postId}/comments`);
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 secondes de timeout
            
            const response = await fetch(`/post/${postId}/comments`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            console.log('Réponse reçue:', response.status);
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            // Vérifier le type de contenu
            const contentType = response.headers.get('content-type');
            console.log('Content-Type:', contentType);
            
            // Traiter la réponse selon son type
            let html;
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                console.log('JSON reçu');
                html = data.html || '<div class="text-center p-3">Aucun commentaire pour le moment.</div>';
            } else {
                html = await response.text();
                console.log('HTML reçu (longueur):', html.length);
                
                // Si la réponse est vide ou très courte, considérer qu'il n'y a pas de commentaires
                if (!html || html.trim().length < 10) {
                    html = '<div class="text-center p-3">Aucun commentaire pour le moment.</div>';
                }
            }
            
            // Mettre à jour le contenu
            commentsListContainer.innerHTML = html;
            
            // Initialiser les gestionnaires d'événements pour les nouveaux commentaires
            initializeCommentEvents(commentsListContainer);
            
        } catch (error) {
            console.error('Erreur lors du chargement des commentaires:', error);
            
            // Message d'erreur spécifique en cas de timeout
            let errorMessage = 'Erreur lors du chargement des commentaires. Veuillez réessayer.';
            if (error.name === 'AbortError') {
                errorMessage = 'Le chargement des commentaires a pris trop de temps. Veuillez réessayer.';
            }
            
            const commentsListContainer = commentsSection.querySelector('.comments-list');
            if (commentsListContainer) {
                commentsListContainer.innerHTML = 
                    `<div class="alert alert-danger">${errorMessage}</div>`;
            }
        }
    }
    
    // Fonction pour initialiser les gestionnaires d'événements sur les commentaires
    function initializeCommentEvents(commentsContainer) {
        // Gestion des boutons de suppression de commentaire
        const deleteButtons = commentsContainer.querySelectorAll('.delete-comment');
        deleteButtons.forEach(button => {
            button.addEventListener('click', async function() {
                const commentId = this.dataset.commentId;
                const token = this.dataset.token;
                if (!commentId) return;
                
                if (confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) {
                    try {
                        const response = await fetch(`/comment/${commentId}/delete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ token })
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            if (data.success) {
                                // Supprimer l'élément du commentaire
                                const commentElement = this.closest('.comment');
                                if (commentElement) {
                                    commentElement.remove();
                                }
                                
                                // Mettre à jour le compteur de commentaires
                                const postCard = commentsContainer.closest('.post-card');
                                if (postCard) {
                                    const commentCount = postCard.querySelector('.comments-count');
                                    if (commentCount && data.commentsCount !== undefined) {
                                        commentCount.textContent = `${data.commentsCount} commentaire${data.commentsCount > 1 ? 's' : ''}`;
                                    }
                                }
                                
                                // Vérifier s'il reste des commentaires
                                if (data.commentsCount === 0) {
                                    commentsContainer.innerHTML = '<div class="text-center text-muted p-3 comments-placeholder">Soyez le premier à commenter.</div>';
                                }
                            }
                        }
                    } catch (error) {
                        console.error('Erreur lors de la suppression du commentaire:', error);
                        showNotification('Une erreur est survenue lors de la suppression du commentaire', 'danger');
                    }
                }
            });
        });
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

    // Style pour l'animation des nouveaux posts et suppression
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
        .fade-out {
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }
        .delete-post {
            cursor: pointer !important;
        }
    `;
    document.head.appendChild(style);
}

// L'initialisation se fait maintenant depuis main.js
// document.addEventListener('DOMContentLoaded', initFeed);