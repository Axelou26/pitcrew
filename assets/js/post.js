document.addEventListener('DOMContentLoaded', function() {
    // Gestion des likes
    document.querySelectorAll('.like-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');
            const likeCount = this.querySelector('.like-count');
            const icon = this.querySelector('i');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    likeCount.textContent = data.likes;
                    if (data.isLiked) {
                        icon.classList.remove('bi-heart');
                        icon.classList.add('bi-heart-fill');
                    } else {
                        icon.classList.remove('bi-heart-fill');
                        icon.classList.add('bi-heart');
                    }
                }
            })
            .catch(error => console.error('Erreur:', error));
        });
    });

    // Gestion des commentaires
    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const url = this.getAttribute('action');
            const commentInput = this.querySelector('textarea');
            const commentsContainer = this.closest('.post-card').querySelector('.comments-container');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'content': commentInput.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Ajouter le nouveau commentaire
                    commentsContainer.insertAdjacentHTML('beforeend', `
                        <div class="comment">
                            <div class="d-flex align-items-start mb-2">
                                <img src="${data.comment.authorAvatar}" class="rounded-circle me-2" width="32" height="32">
                                <div>
                                    <a href="${data.comment.authorUrl}" class="fw-bold text-decoration-none">${data.comment.authorName}</a>
                                    <p class="mb-1">${data.comment.content}</p>
                                    <small class="text-muted">${data.comment.createdAt}</small>
                                </div>
                            </div>
                        </div>
                    `);
                    
                    // Réinitialiser le formulaire
                    commentInput.value = '';
                }
            })
            .catch(error => console.error('Erreur:', error));
        });
    });

    // Gestion du partage
    document.querySelectorAll('.share-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = window.location.origin + this.getAttribute('data-url');
            
            if (navigator.share) {
                navigator.share({
                    title: 'Partager ce post',
                    url: url
                })
                .catch(error => console.error('Erreur:', error));
            } else {
                // Fallback: copier le lien dans le presse-papier
                navigator.clipboard.writeText(url)
                    .then(() => alert('Lien copié dans le presse-papier !'))
                    .catch(error => console.error('Erreur:', error));
            }
        });
    });
}); 