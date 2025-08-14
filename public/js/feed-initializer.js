/**
 * Initialisation du feed et gestion des boutons like
 * Script de secours quand le module principal n'est pas chargé
 */

// Fonctions de persistance des likes
function getLikedPosts() {
    const likedPosts = localStorage.getItem('likedPosts');
    return likedPosts ? JSON.parse(likedPosts) : [];
}

function setLikedPosts(likedPosts) {
    localStorage.setItem('likedPosts', JSON.stringify(likedPosts));
}

function isPostLiked(postId) {
    const likedPosts = getLikedPosts();
    return likedPosts.includes(postId);
}

function addLikedPost(postId) {
    const likedPosts = getLikedPosts();
    if (!likedPosts.includes(postId)) {
        likedPosts.push(postId);
        setLikedPosts(likedPosts);
    }
}

function removeLikedPost(postId) {
    const likedPosts = getLikedPosts();
    const index = likedPosts.indexOf(postId);
    if (index > -1) {
        likedPosts.splice(index, 1);
        setLikedPosts(likedPosts);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        console.log('🔍 Vérification de l\'initialisation du feed et des boutons like');
        
        // Vérifier si le module principal de likes est chargé
        if (typeof window.initLikes === 'function') {
            console.log('✅ Module principal de likes détecté, pas besoin de script de test');
            return;
        }
        
        console.log('⚠️ Module principal de likes non détecté, utilisation du script de test');
        initializeLikeButtons();
        initializeCommentButtons();
    }, 2000); // Attendre 2 secondes pour s'assurer que tous les scripts sont chargés
});

/**
 * Initialise les boutons like avec des gestionnaires d'événements
 */
function initializeLikeButtons() {
    const likeButtons = document.querySelectorAll('.like-button');
    console.log(`💖 ${likeButtons.length} boutons like trouvés sur la page`);
    
    likeButtons.forEach((button, index) => {
        const postId = button.dataset.postId;
        console.log(`Bouton like ${index + 1}: postId=${postId}`);
        
        // Restaurer l'état du bouton selon la persistance locale
        if (postId) {
            const isLiked = isPostLiked(postId);
            updateLikeButton(button, isLiked);
            console.log(`Bouton ${index + 1}: postId=${postId}, isLiked=${isLiked}`);
        }
        
        // Vérifier si le bouton a des gestionnaires d'événements
        const clickListeners = button.onclick;
        console.log(`Bouton ${index + 1} - onclick:`, clickListeners);
        
        // Ajouter un gestionnaire de test si aucun n'existe
        if (!clickListeners) {
            console.log(`🔄 Ajout d'un gestionnaire de test pour le bouton ${index + 1}`);
            button.addEventListener('click', handleLikeClick);
        }
    });
}

/**
 * Gère le clic sur un bouton like
 */
async function handleLikeClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const postId = this.dataset.postId;
    console.log(`🖱️ Clic détecté sur bouton like pour le post ${postId}`);
    
    try {
        console.log(`📤 Envoi de la requête like pour le post ${postId}`);
        
        const response = await fetch(`/post/${postId}/like`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        });
        
        console.log(`📥 Réponse reçue: ${response.status}`);
        
        if (response.ok) {
            const data = await response.json();
            console.log('📊 Données reçues:', data);
            
            if (data.success) {
                // Mettre à jour la persistance locale
                if (data.isLiked) {
                    addLikedPost(postId);
                    console.log(`❤️ Post ${postId} ajouté aux likes`);
                } else {
                    removeLikedPost(postId);
                    console.log(`💔 Post ${postId} retiré des likes`);
                }
                
                updateLikeButton(this, data.isLiked);
                updateLikesCount(this, data.likesCount);
                console.log(`✅ ${data.message}`);
            } else {
                console.warn(`⚠️ ${data.message}`);
            }
        } else {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
    } catch (error) {
        console.error('❌ Erreur lors du like:', error);
        alert(`❌ Erreur: ${error.message}`);
    }
}

/**
 * Met à jour l'apparence du bouton like
 */
function updateLikeButton(button, isLiked) {
    const icon = button.querySelector('i');
    if (icon) {
        if (isLiked) {
            icon.classList.remove('bi-heart');
            icon.classList.add('bi-heart-fill');
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-primary');
        } else {
            icon.classList.remove('bi-heart-fill');
            icon.classList.add('bi-heart');
            button.classList.remove('btn-primary');
            button.classList.add('btn-outline-secondary');
        }
    }
}

/**
 * Met à jour le compteur de likes
 */
function updateLikesCount(button, likesCount) {
    const postCard = button.closest('.post-card');
    if (postCard) {
        const likesSummary = postCard.querySelector('.likes-summary');
        if (likesSummary && likesCount !== undefined) {
            if (likesCount > 0) {
                likesSummary.innerHTML = `<span class="likes-count me-1">${likesCount}</span> j'aime${likesCount > 1 ? 's' : ''}`;
            } else {
                likesSummary.innerHTML = '';
            }
        }
    }
}

/**
 * Initialise les boutons de commentaires
 */
function initializeCommentButtons() {
    const commentButtons = document.querySelectorAll('.comment-toggle-button');
    console.log(`💬 ${commentButtons.length} boutons de commentaires trouvés`);
    
    commentButtons.forEach(button => {
        button.addEventListener('click', handleCommentToggle);
    });
}

/**
 * Gère le basculement de l'affichage des commentaires
 */
function handleCommentToggle(e) {
    e.preventDefault();
    
    const postId = this.dataset.postId;
    if (!postId) {
        console.error('ID de post manquant sur le bouton de commentaire');
        return;
    }
    
    const commentsId = `comments-${postId}`;
    const commentsSection = document.getElementById(commentsId);
    
    if (!commentsSection) {
        console.error(`Section de commentaires #${commentsId} non trouvée`);
        return;
    }
    
    // Basculer l'affichage
    if (commentsSection.style.display === 'none' || commentsSection.style.display === '') {
        commentsSection.style.display = 'block';
        focusCommentInput(commentsSection);
    } else {
        commentsSection.style.display = 'none';
    }
}

/**
 * Met le focus sur le champ de commentaire et initialise l'autocomplétion
 */
function focusCommentInput(commentsSection) {
    const commentInput = commentsSection.querySelector('.comment-input');
    if (commentInput) {
        // S'assurer que la classe mention-input est présente
        if (!commentInput.classList.contains('mention-input')) {
            commentInput.classList.add('mention-input');
        }
        
        // Initialiser l'autocomplétion des mentions
        if (typeof window.initializePostAutocomplete === 'function') {
            window.initializePostAutocomplete(commentInput);
        }
        
        commentInput.focus();
    }
}

// Définir une fonction d'initialisation globale du feed
window.initializeFeed = function() {
    initializeCommentButtons();
};

// Si le feed n'est pas initialisé par le module principal, utiliser notre fonction de secours
if (document.querySelectorAll('.comment-toggle-button').length > 0 && 
    typeof window.initFeed === 'undefined') {
    window.initializeFeed();
}
