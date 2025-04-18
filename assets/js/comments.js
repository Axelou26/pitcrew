document.addEventListener('DOMContentLoaded', () => {
    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', handleCommentSubmit);
    }

    document.querySelectorAll('.reply-button').forEach(button => {
        button.addEventListener('click', handleReplyClick);
    });
});

async function handleCommentSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const postId = form.dataset.postId;
    const input = form.querySelector('.comment-input');
    const content = input.value.trim();
    const parentCommentId = null;
    const postCard = form.closest('.post-card');
    const commentsList = postCard.querySelector('.comments-list');
    const commentsPlaceholder = postCard.querySelector('.comments-placeholder');
    const commentsCountSpan = postCard.querySelector('.comments-count');
    const submitButton = form.querySelector('.comment-submit-button');

    if (!content) {
        input.classList.add('is-invalid');
        return;
    } else {
        input.classList.remove('is-invalid');
    }

    if (submitButton) submitButton.disabled = true;

    try {
        const response = await fetch(`/post/${postId}/comment/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                content: content,
                parentId: parentCommentId
            })
        });

        if (!response.ok) {
            if (response.status === 404) {
                alert('Cette publication n\'existe plus. Elle a peut-être été supprimée.');
                // Supprimer la carte du post si elle n'existe plus
                if (postCard) {
                    postCard.style.transition = 'opacity 0.3s ease';
                    postCard.style.opacity = '0';
                    setTimeout(() => {
                        postCard.remove();
                        // Vérifier si le conteneur est vide
                        const postsContainer = document.querySelector('.posts-container');
                        if (postsContainer) {
                            const remainingPosts = postsContainer.querySelectorAll('.post-card');
                            if (remainingPosts.length === 0) {
                                postsContainer.innerHTML = `
                                    <div class="card border-0 shadow-sm rounded-3 p-5 text-center">
                                        <i class="bi bi-newspaper display-1 text-muted mb-3"></i>
                                        <h3 class="h4 text-muted">Aucune publication disponible</h3>
                                        <p class="text-muted mb-4">Soyez le premier à partager du contenu avec la communauté</p>
                                        <div class="text-center">
                                            <a href="/post/new" class="btn btn-primary rounded-pill px-4">
                                                <i class="bi bi-plus-lg me-2"></i>Créer une publication
                                            </a>
                                        </div>
                                    </div>
                                `;
                            }
                        }
                    }, 300);
                }
                return;
            }
            throw new Error('Une erreur est survenue lors de l\'ajout du commentaire');
        }

        const data = await response.json();

        if (data.success && data.comment && data.comment.html !== undefined && data.commentsCount !== undefined) {
            if (commentsList) {
                commentsList.insertAdjacentHTML('beforeend', data.comment.html);
                if (commentsPlaceholder) {
                    commentsPlaceholder.classList.add('d-none');
                }
            }
            
            if (commentsCountSpan) {
                const newCount = data.commentsCount;
                commentsCountSpan.textContent = `${newCount} commentaire${newCount > 1 ? 's' : ''}`;
            }

            input.value = '';
            input.classList.remove('is-invalid');

        } else {
            console.error('Comment submit error:', data.error || 'Invalid response');
            alert('Erreur: ' + (data.error || 'Impossible d\'ajouter le commentaire.'));
        }
    } catch (error) {
        console.error('Fetch Error:', error);
        alert(error.message);
    } finally {
        if (submitButton) submitButton.disabled = false;
    }
}

function handleReplyClick(event) {
    const button = event.target;
    const commentId = button.dataset.commentId;
    const form = document.getElementById('comment-form');
    
    form.dataset.parentId = commentId;
    form.querySelector('button[type="submit"]').textContent = 'Répondre';
    form.querySelector('textarea').focus();
    
    form.scrollIntoView({ behavior: 'smooth' });
}

document.addEventListener('submit', function(event) {
    if (event.target.classList.contains('comment-form')) {
        handleCommentSubmit(event);
    }
}); 