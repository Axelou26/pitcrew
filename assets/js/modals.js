document.addEventListener('DOMContentLoaded', function() {
    // Initialisation manuelle des modales Bootstrap
    var shareModals = {};
    var modalElements = document.querySelectorAll('.modal');
    
    modalElements.forEach(function(modal) {
        try {
            shareModals[modal.id] = new bootstrap.Modal(modal);
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de la modale:', modal.id, error);
        }
    });

    // Ouvrir manuellement les modales lors du clic sur les boutons de partage
    document.querySelectorAll('.share-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const targetId = this.dataset.bsTarget || this.getAttribute('data-bs-target');
            
            if (targetId) {
                const modalId = targetId.replace('#', '');
                if (shareModals[modalId]) {
                    try {
                        shareModals[modalId].show();
                    } catch (error) {
                        console.error('Erreur lors de l\'ouverture de la modale:', error);
                        
                        // Alternative: utiliser l'API native de Bootstrap
                        const modalElement = document.querySelector(targetId);
                        if (modalElement) {
                            const bsModal = new bootstrap.Modal(modalElement);
                            bsModal.show();
                        }
                    }
                } else {
                    console.error('Modale non trouvée dans la collection:', modalId);
                    // Essai d'initialisation et d'ouverture directe
                    const modalElement = document.querySelector(targetId);
                    if (modalElement) {
                        const bsModal = new bootstrap.Modal(modalElement);
                        bsModal.show();
                    } else {
                        console.error('Élément modal non trouvé dans le DOM:', targetId);
                    }
                }
            } else {
                console.error('Pas de cible définie pour le bouton de partage');
            }
        });
    });

    // Amélioration de la gestion des boutons submit de partage
    document.querySelectorAll('.share-submit').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            
            if (!postId) {
                console.error('Erreur: postId manquant');
                return;
            }
            
            const modal = this.closest('.modal');
            if (!modal) {
                console.error('Erreur: modale parente non trouvée');
                return;
            }
            
            const form = modal.querySelector('.share-form');
            if (!form) {
                console.error('Erreur: formulaire de partage non trouvé');
                return;
            }
            
            const textarea = form.querySelector('textarea[name="comment"]');
            if (!textarea) {
                console.error('Erreur: champ de commentaire non trouvé');
                return;
            }
            
            const comment = textarea.value;
            
            try {
                const response = await fetch(`/post/${postId}/share`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ comment })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    // Mettre à jour le compteur de partages si disponible
                    const sharesCountElement = document.querySelector(`[data-post-id="${postId}"] .shares-count`);
                    if (sharesCountElement) {
                        const newCount = data.sharesCount;
                        sharesCountElement.textContent = `${newCount} partage${newCount > 1 ? 's' : ''}`;
                    }
                    
                    // Fermer la modale
                    const modalInstance = shareModals[modal.id] || bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        console.error('Instance de modale non trouvée pour la fermeture');
                        modal.style.display = 'none';
                        document.querySelector('.modal-backdrop')?.remove();
                    }
                    
                    // Réinitialiser le formulaire
                    form.reset();
                    
                    // Afficher une notification de succès
                    if (window.showNotification) {
                        window.showNotification('Post partagé avec succès', 'success');
                    } else {
                        alert('Post partagé avec succès');
                    }
                    
                    // Actualiser la page après un court délai pour voir le post partagé
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    console.error('Erreur lors du partage:', data);
                    if (window.showNotification) {
                        window.showNotification(data.message || 'Une erreur est survenue lors du partage', 'danger');
                    } else {
                        alert(data.message || 'Une erreur est survenue lors du partage');
                    }
                }
            } catch (error) {
                console.error('Erreur lors du partage:', error);
                if (window.showNotification) {
                    window.showNotification('Une erreur est survenue lors du partage', 'danger');
                } else {
                    alert('Une erreur est survenue lors du partage');
                }
            }
        });
    });

    // Gestion des événements modaux
    modalElements.forEach(modal => {
        modal.addEventListener('shown.bs.modal', function(e) {
            const textarea = this.querySelector('textarea');
            if (textarea) textarea.focus();
        });
    });
});

export default {};
