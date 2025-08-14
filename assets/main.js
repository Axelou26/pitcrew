/*
 * Fichier d'entrée principal pour tous les modules JavaScript
 * Ce fichier importe tous les modules nécessaires pour éviter les erreurs de résolution
 */

// Import des modules JavaScript
import { initFeed } from './js/feed.js';
import { initLikes } from './js/likes-handler.js';
import './js/post-autocomplete.js';
import './js/csrf-helper.js'; // Import du module d'aide pour CSRF

console.log('Tous les modules JavaScript ont été chargés avec succès');

// Initialisation des modules quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation des modules JavaScript...');
    
    // Initialiser le gestionnaire de likes en premier
    try {
        console.log('💖 Initialisation du gestionnaire de likes...');
        initLikes();
        console.log('✅ Gestionnaire de likes initialisé avec succès');
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation du gestionnaire de likes:', error);
    }
    
    // Initialiser le feed explicitement
    try {
        console.log('📰 Tentative d\'initialisation du feed...');
        initFeed();
        console.log('✅ Feed initialisé avec succès');
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation du feed:', error);
    }
    
    // Vérifier que les boutons like sont bien initialisés
    setTimeout(function() {
        const likeButtons = document.querySelectorAll('.like-button');
        console.log(`🔍 Vérification: ${likeButtons.length} boutons like trouvés`);
        
        likeButtons.forEach((button, index) => {
            const postId = button.dataset.postId;
            console.log(`Bouton ${index + 1}: postId=${postId}, classes=${button.className}`);
        });
    }, 2000);
});
