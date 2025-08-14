// Script spécifique pour la page de détail d'un post
document.addEventListener('DOMContentLoaded', function() {
    // S'assurer que le contenu complet est affiché sur la page de détail
    const postContentElements = document.querySelectorAll('.post-content');
    
    postContentElements.forEach(contentElement => {
        // Supprimer toute classe de troncature
        contentElement.classList.remove('truncated');
        
        // S'assurer que le contenu complet est affiché
        const fullContent = contentElement.getAttribute('data-full-content');
        if (fullContent) {
            contentElement.innerHTML = fullContent;
        }
        
        // Supprimer les liens "Voir plus" s'ils existent
        const readMoreLink = contentElement.parentNode.querySelector('.read-more-link');
        if (readMoreLink) {
            readMoreLink.remove();
        }
        
        // Supprimer les conteneurs "read-more" s'ils existent
        const readMoreContainer = contentElement.parentNode.querySelector('.read-more-container');
        if (readMoreContainer) {
            readMoreContainer.remove();
        }
        
        // Appliquer des styles pour s'assurer que le contenu est visible
        contentElement.style.maxHeight = 'none';
        contentElement.style.overflow = 'visible';
        contentElement.style.position = 'static';
    });
    
    // Supprimer les pseudo-éléments de troncature
    const style = document.createElement('style');
    style.textContent = `
        .post-content::after {
            display: none !important;
        }
        .post-content {
            max-height: none !important;
            overflow: visible !important;
        }
    `;
    document.head.appendChild(style);
}); 