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
    const textarea = form.querySelector('textarea');
    const content = textarea.value.trim();
    const parentCommentId = form.dataset.parentId || null;

    if (!content) {
        alert('Le commentaire ne peut pas être vide.');
        return;
    }

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

        if (data.success) {
            // Recharger la liste des commentaires
            const commentsContainer = document.querySelector('.comments-container');
            const response = await fetch(`/post/${postId}/comments`);
            const html = await response.text();
            commentsContainer.innerHTML = html;

            // Réinitialiser le formulaire
            textarea.value = '';
            if (parentCommentId) {
                form.dataset.parentId = '';
                form.querySelector('button[type="submit"]').textContent = 'Publier';
            }
        } else {
            alert('Une erreur est survenue lors de l\'ajout du commentaire.');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de l\'ajout du commentaire.');
    }
}

function handleReplyClick(event) {
    const button = event.target;
    const commentId = button.dataset.commentId;
    const form = document.getElementById('comment-form');
    
    form.dataset.parentId = commentId;
    form.querySelector('button[type="submit"]').textContent = 'Répondre';
    form.querySelector('textarea').focus();
    
    // Faire défiler jusqu'au formulaire
    form.scrollIntoView({ behavior: 'smooth' });
} 