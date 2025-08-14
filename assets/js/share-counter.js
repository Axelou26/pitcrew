// Module pour gérer les compteurs de partage
document.addEventListener('DOMContentLoaded', function() {
    // Rechercher tous les éléments de compteur de partages
    const counters = document.querySelectorAll('.shares-count');
    
    // Initialisation des compteurs
    counters.forEach(counter => {
        const postId = counter.closest('.post-card')?.dataset.postId;
        if (postId) {
            // Compteur initialisé
        }
    });
    
    // Fonction pour mettre à jour le compteur
    window.updateShareCount = function(postId, count) {
        const counter = document.querySelector(`.post-card[data-post-id="${postId}"] .shares-count`);
        if (counter) {
            counter.textContent = `${count} partage${count > 1 ? 's' : ''}`;
        }
    };
});

export default {};
