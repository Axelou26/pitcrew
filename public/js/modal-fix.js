/**
 * Script pour corriger les problèmes d'accessibilité des modales
 * Résout spécifiquement les problèmes avec aria-hidden
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de correction des modales chargé');

    // Fonction pour supprimer proprement une modale avec gestion de l'accessibilité
    function cleanupModal(modalElement) {
        console.log('Nettoyage complet de la modale:', modalElement.id);

        // 1. Retirer le focus des éléments de la modale avant fermeture
        const focusedElement = modalElement.querySelector(':focus');
        if (focusedElement) {
            console.log('Retrait du focus de:', focusedElement);
            focusedElement.blur();
        }

        // 2. Retirer l'attribut aria-hidden qui cause le problème
        modalElement.removeAttribute('aria-hidden');
        
        // 3. Retirer les classes et attributs de la modale
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        
        // 4. Supprimer tous les backdrops
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            if (backdrop && backdrop.parentNode) {
                backdrop.parentNode.removeChild(backdrop);
            }
        });
        
        // 5. Restaurer l'état du body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // 6. Rendre les éléments de nouveau accessibles au clavier
        document.querySelectorAll('[data-bs-scroll-lock]').forEach(el => {
            el.removeAttribute('data-bs-scroll-lock');
        });
        
        console.log('Modale nettoyée avec succès');
    }
    
    // Gérer tous les boutons qui ferment les modales (Annuler, X, etc.)
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(button => {
        // Remplacer le gestionnaire d'événement existant
        const newButton = button.cloneNode(true);
        if (button.parentNode) {
            button.parentNode.replaceChild(newButton, button);
        }
        
        // Ajouter notre propre gestionnaire
        newButton.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const modal = this.closest('.modal');
            if (modal) {
                console.log('Bouton de fermeture cliqué pour la modale:', modal.id);
                cleanupModal(modal);
            }
        });
    });
    
    // Intercepter les événements de Bootstrap sur les modales
    document.querySelectorAll('.modal').forEach(modal => {
        // Avant que la modale se cache
        modal.addEventListener('hide.bs.modal', function(event) {
            console.log('Événement hide.bs.modal intercepté pour:', this.id);
            // Nous allons gérer manuellement la fermeture pour éviter les problèmes d'accessibilité
            event.preventDefault();
            event.stopPropagation();
            cleanupModal(this);
            return false;
        }, true);
    });
    
    // Gérer la touche Échap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' || event.keyCode === 27) {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                event.preventDefault();
                console.log('Touche Échap détectée, fermeture de la modale:', openModal.id);
                cleanupModal(openModal);
            }
        }
    });
    
    // Gérer le clic en dehors de la modale
    document.addEventListener('click', function(event) {
        // Si on clique sur la modale elle-même (le fond) mais pas son contenu
        if (event.target.classList.contains('modal') && event.target.classList.contains('show')) {
            event.preventDefault();
            console.log('Clic en dehors du contenu de la modale détecté');
            cleanupModal(event.target);
        }
    });
});
