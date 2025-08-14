document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour initialiser les boutons de suppression
    function initializeDeleteButtons() {
        // Cibler tous les liens de suppression
        document.querySelectorAll('.delete-post').forEach(function(deleteLink) {
            // Remplacer chaque lien par un bouton ayant les mêmes attributs
            const newDeleteButton = document.createElement('button');
            newDeleteButton.className = deleteLink.className;
            newDeleteButton.setAttribute('type', 'button');
            
            // Copier les attributs data-*
            Array.from(deleteLink.attributes)
                .filter(attr => attr.name.startsWith('data-'))
                .forEach(attr => newDeleteButton.setAttribute(attr.name, attr.value));
            
            // Copier le contenu HTML (l'icône et le texte)
            newDeleteButton.innerHTML = deleteLink.innerHTML;
            
            // Ajouter le gestionnaire d'événements
            newDeleteButton.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const postId = this.dataset.postId;
                if (!postId) {
                    console.error('Erreur: postId indéfini ou vide sur le bouton de suppression', this);
                    window.showNotification('Erreur technique: impossible de supprimer ce post.', 'danger');
                    return;
                }
                
                if (confirm('Êtes-vous sûr de vouloir supprimer cette publication ?')) {
                    try {
                        const response = await fetch(`/post/${postId}/delete`, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        });

                        if (response.ok) {
                            const data = await response.json();
                            if (data.success) {
                                // Supprimer le post du DOM avec animation
                                const postCard = this.closest('.post-card');
                                if (postCard) {
                                    postCard.classList.add('fade-out');
                                    setTimeout(() => {
                                        postCard.remove();
                                    }, 300);
                                }
                                window.showNotification('Publication supprimée avec succès', 'success');
                            } else {
                                window.showNotification(data.message || 'Erreur lors de la suppression', 'danger');
                            }
                        } else {
                            throw new Error('Erreur lors de la suppression');
                        }
                    } catch (error) {
                        console.error('Erreur lors de la suppression du post:', error);
                        window.showNotification('Une erreur est survenue lors de la suppression', 'danger');
                    }
                }
            });
            
            // Remplacer le lien par le nouveau bouton
            deleteLink.parentNode.replaceChild(newDeleteButton, deleteLink);
        });
    }

    // Initialiser tous les boutons au chargement de la page
    initializeDeleteButtons();

    // Style pour l'animation de suppression
    const style = document.createElement('style');
    style.textContent = `
        .fade-out {
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }
    `;
    document.head.appendChild(style);
});
