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
        alert('Une erreur réseau est survenue.');
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