document.addEventListener('DOMContentLoaded', function() {
    const feedContainer = document.querySelector('.feed-container');
    if (!feedContainer) return;

    let page = 1;
    let loading = false;
    let hasMore = true;

    // Fonction pour charger plus de posts
    function loadMorePosts() {
        if (loading || !hasMore) return;
        
        loading = true;
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'text-center py-3';
        loadingIndicator.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
        feedContainer.appendChild(loadingIndicator);

        fetch(`/feed?page=${page}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingIndicator.remove();
            
            if (data.posts && data.posts.length > 0) {
                data.posts.forEach(post => {
                    feedContainer.insertAdjacentHTML('beforeend', `
                        <div class="post-card card mb-4">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="${post.authorAvatar}" class="rounded-circle me-2" width="40" height="40">
                                    <div>
                                        <a href="${post.authorUrl}" class="text-decoration-none fw-bold">${post.authorName}</a>
                                        <div class="text-muted small">${post.createdAt}</div>
                                    </div>
                                </div>
                                
                                <h5 class="card-title">${post.title}</h5>
                                <p class="card-text">${post.content}</p>
                                
                                ${post.image ? `
                                    <img src="${post.image}" class="img-fluid rounded mb-3" alt="${post.title}">
                                ` : ''}
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary like-button" data-url="${post.likeUrl}">
                                            <i class="bi ${post.isLiked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                                            <span class="like-count">${post.likes}</span>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-chat"></i>
                                            ${post.comments}
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary share-button" data-url="${post.shareUrl}">
                                            <i class="bi bi-share"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });
                
                page++;
            } else {
                hasMore = false;
                feedContainer.insertAdjacentHTML('beforeend', `
                    <div class="text-center py-3 text-muted">
                        Vous avez atteint la fin du flux
                    </div>
                `);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des posts:', error);
            loadingIndicator.innerHTML = `
                <div class="alert alert-danger">
                    Une erreur est survenue lors du chargement des posts.
                    <button class="btn btn-link" onclick="loadMorePosts()">Réessayer</button>
                </div>
            `;
        })
        .finally(() => {
            loading = false;
        });
    }

    // Détecter quand l'utilisateur atteint le bas de la page
    function handleScroll() {
        const scrollPosition = window.innerHeight + window.scrollY;
        const documentHeight = document.documentElement.offsetHeight;
        
        if (scrollPosition >= documentHeight - 1000) { // Charger plus tôt pour une expérience fluide
            loadMorePosts();
        }
    }

    // Écouter l'événement de défilement
    window.addEventListener('scroll', handleScroll);

    // Charger les premiers posts
    loadMorePosts();
}); 