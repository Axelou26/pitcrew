/**
 * Script pour corriger la fonctionnalité de partage des posts
 */
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour ouvrir manuellement une modale
    function ouvrirModale(modalId) {
        // Récupérer l'élément modal
        const modalElement = document.getElementById(modalId);
        if (!modalElement) {
            console.error('Modale non trouvée dans le DOM:', modalId);
            return;
        }
        
        try {
            // Essayer d'ouvrir la modale avec Bootstrap
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } catch (error) {
            console.error('Erreur lors de l\'ouverture de la modale:', error);
            
            // Méthode alternative
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            
            // Créer un backdrop manuellement
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
            
            // Ajouter la classe pour désactiver le défilement
            document.body.classList.add('modal-open');
            
            // Gestionnaire pour fermer manuellement
            const closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"]');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    fermerModaleManuelle(modalElement);
                });
            });
        }
    }
    
    // Fonction pour fermer une modale manuellement
    function fermerModaleManuelle(modalElement) {
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        
        // Supprimer tous les backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            if (backdrop.parentNode) {
                backdrop.parentNode.removeChild(backdrop);
            }
        });
        
        // Rétablir le défilement
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    // Attacher l'événement de clic aux boutons de partage
    document.querySelectorAll('.share-button').forEach(button => {
        // Supprimer les gestionnaires d'événements précédents (peut-être ajoutés par d'autres scripts)
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        // Ajouter le nouveau gestionnaire d'événement
        newButton.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const postId = this.getAttribute('data-post-id');
            const modalId = 'shareModal' + postId;
            
            // Ouvrir la modale manuellement
            ouvrirModale(modalId);
        });
    });
    
    // Améliorer la gestion des boutons d'annulation de modal
    document.querySelectorAll('button[data-bs-dismiss="modal"]').forEach(button => {
        // Supprimer les gestionnaires d'événements précédents
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        // Ajouter le nouveau gestionnaire d'événement
        newButton.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const modal = this.closest('.modal');
            if (modal) {
                // Essayer de fermer via l'API Bootstrap
                try {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                        return;
                    }
                } catch (error) {
                    console.error('Erreur lors de la fermeture via Bootstrap:', error);
                }
                
                // Méthode alternative si l'API Bootstrap échoue
                fermerModaleManuelle(modal);
            }
        });
    });
    
    // Améliorer la gestion des soumissions de partage
    document.querySelectorAll('.share-submit').forEach(button => {
        // Supprimer les gestionnaires d'événements précédents
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        // Ajouter le nouveau gestionnaire d'événement
        newButton.addEventListener('click', async function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const postId = this.getAttribute('data-post-id');
            
            const modal = this.closest('.modal');
            const form = modal.querySelector('.share-form');
            const textarea = form.querySelector('textarea[name="comment"]');
            
            if (!textarea) {
                console.error('Champ de commentaire non trouvé');
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
                    // Fermer la modale manuellement
                    fermerModaleManuelle(modal);
                    
                    // Afficher une notification
                    alert('Post partagé avec succès!');
                    
                    // Recharger la page pour voir le post partagé
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    alert(data.message || 'Une erreur est survenue lors du partage');
                }
            } catch (error) {
                console.error('Erreur lors du partage:', error);
                alert('Une erreur est survenue lors du partage');
            }
        });
    });
    
    // Ajouter des gestionnaires d'événements pour la fermeture des modales lors du clic sur l'arrière-plan
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal') && event.target.classList.contains('show')) {
            fermerModaleManuelle(event.target);
        }
    });
    
    // Gérer la touche Echap pour fermer les modales
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' || event.keyCode === 27) {
            const modalOuverte = document.querySelector('.modal.show');
            if (modalOuverte) {
                fermerModaleManuelle(modalOuverte);
            }
        }
    });
});