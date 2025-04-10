// Import styles
import './styles/app.scss';

// Import Bootstrap SCSS
import 'bootstrap/scss/bootstrap.scss';

// Import Bootstrap Icons
import 'bootstrap-icons/font/bootstrap-icons.css';

// Import Bootstrap JS
import * as bootstrap from 'bootstrap';

// Make Bootstrap available globally
window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', () => {
    // Tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Définir la map des réactions (similaire à celle dans Twig)
    const reactionMap = {
        'like': { emoji: '👍', name: 'J\'aime', class: 'btn-primary' },
        'congrats': { emoji: '👏', name: 'Bravo', class: 'btn-success' },
        'support': { emoji: '❤️', name: 'Soutien', class: 'btn-danger' },
        'interesting': { emoji: '💡', name: 'Intéressant', class: 'btn-info' },
        'encouraging': { emoji: '💪', name: 'Encouragement', class: 'btn-warning' }
    };
    const defaultReaction = { emoji: '👍', name: 'Réagir', class: 'btn-outline-secondary' };

    // Gestionnaire pour les clics sur les options de réaction
    document.body.addEventListener('click', function(event) {
        if (event.target.closest('.reaction-option')) {
            const reactionButton = event.target.closest('.reaction-option');
            const reactionType = reactionButton.dataset.reactionType;
            const postCard = reactionButton.closest('.post-card');
            const postId = postCard.dataset.postId;
            const reactionTrigger = postCard.querySelector('.reaction-trigger');
            const reactionEmojiSpan = reactionTrigger.querySelector('.reaction-emoji');
            const reactionNameSpan = reactionTrigger.querySelector('.reaction-name');
            const likesCountSpan = postCard.querySelector('.reaction-summary .likes-count'); // Cible le span dans le résumé
            const dropdownInstance = bootstrap.Dropdown.getInstance(reactionTrigger);

            // Désactiver les boutons pendant la requête
            postCard.querySelectorAll('.reaction-option').forEach(btn => btn.disabled = true);

            fetch(`/post/${postId}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' // Important pour Symfony
                },
                body: new URLSearchParams({ 'reactionType': reactionType })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const activeReactionType = data.activeReactionType;
                    const newCount = data.likesCount;

                    // Mettre à jour le compteur total
                    if (likesCountSpan) {
                        if (newCount > 0) {
                            likesCountSpan.textContent = newCount;
                            likesCountSpan.nextSibling.textContent = ` réaction${newCount > 1 ? 's' : ''}`;
                            likesCountSpan.parentElement.style.display = ''; // S'assurer qu'il est visible
                        } else {
                             likesCountSpan.parentElement.style.display = 'none'; // Cacher si 0
                        }
                    }
                    
                    // Mettre à jour le bouton déclencheur
                    const activeReaction = activeReactionType ? reactionMap[activeReactionType] : defaultReaction;
                    
                    reactionTrigger.classList.remove(...Object.values(reactionMap).map(r => r.class), 'btn-outline-secondary');
                    reactionTrigger.classList.add(activeReaction.class);
                    reactionEmojiSpan.textContent = activeReaction.emoji;
                    reactionNameSpan.textContent = activeReaction.name;
                    reactionTrigger.dataset.currentReaction = activeReactionType || '';
                    
                    // Mettre à jour l'option 'Retirer' dans le dropdown (si elle existe)
                    const removeOption = postCard.querySelector('.reaction-option[data-reaction-type="' + reactionType + '"] ~ li .reaction-option.text-danger');
                    const dropdownMenu = postCard.querySelector('.reaction-options');
                    let existingRemoveOption = dropdownMenu.querySelector('.reaction-option.text-danger');
                    let divider = dropdownMenu.querySelector('.dropdown-divider');

                    // Supprimer l'ancienne option retirer s'il y en a une
                    if (existingRemoveOption) existingRemoveOption.closest('li').remove();
                    if (divider) divider.closest('li').remove();

                    // Ajouter la nouvelle option retirer si une réaction est active
                    if (activeReactionType) {
                        const newDivider = document.createElement('li');
                        newDivider.innerHTML = '<hr class="dropdown-divider">';
                        dropdownMenu.appendChild(newDivider);

                        const newRemoveLi = document.createElement('li');
                        const newRemoveButton = document.createElement('button');
                        newRemoveButton.className = 'dropdown-item reaction-option text-danger d-flex align-items-center';
                        newRemoveButton.type = 'button';
                        newRemoveButton.dataset.reactionType = activeReactionType;
                        newRemoveButton.innerHTML = `<i class="bi bi-x-circle me-2"></i> Retirer ${reactionMap[activeReactionType].name}`;
                        newRemoveLi.appendChild(newRemoveButton);
                        dropdownMenu.appendChild(newRemoveLi);
                    }

                } else {
                    console.error('Erreur lors de la mise à jour de la réaction:', data.message);
                    // Afficher un message d'erreur à l'utilisateur si nécessaire
                }
            })
            .catch(error => {
                console.error('Erreur réseau ou serveur:', error);
                // Afficher un message d'erreur à l'utilisateur
            })
            .finally(() => {
                 // Réactiver les boutons
                 postCard.querySelectorAll('.reaction-option').forEach(btn => btn.disabled = false);
                // Fermer le dropdown après l'action
                 if (dropdownInstance) {
                     //dropdownInstance.hide(); // Peut être masqué automatiquement, sinon décommenter
                 }
            });
        }
    });

    // Gestionnaire pour la soumission du formulaire de partage
    document.body.addEventListener('submit', function(event) {
        if (event.target.classList.contains('share-form')) {
            event.preventDefault(); // Empêcher la soumission standard

            const form = event.target;
            const postId = form.dataset.postId;
            const modalElement = form.closest('.modal');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalPostCard = document.querySelector(`.post-card[data-post-id="${postId}"]`);
            const sharesCountSpan = originalPostCard ? originalPostCard.querySelector('.shares-count') : null;

            const formData = new FormData(form);

            // Désactiver le bouton pendant la requête
            if (submitButton) submitButton.disabled = true;

            fetch(`/post/${postId}/share`, {
                method: 'POST',
                headers: {
                    // Pas besoin de Content-Type ici, FormData le gère
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                 if (!response.ok) {
                     // Essayer de lire le message d'erreur JSON
                     return response.json().then(errData => {
                         throw new Error(errData.message || `Erreur serveur (${response.status})`);
                     }).catch(() => {
                         // Si pas de JSON, lancer une erreur générique
                         throw new Error(`Erreur réseau ou serveur (${response.status})`);
                     });
                 }
                 return response.json();
             })
            .then(data => {
                if (data.success) {
                    // Mettre à jour le compteur de partages sur le post original
                    if (sharesCountSpan) {
                         const newCount = data.sharesCount;
                         sharesCountSpan.textContent = `${newCount} partage${newCount > 1 ? 's' : ''}`;
                    }

                    // Fermer la modale
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    // Optionnel: Afficher une notification de succès
                    // alert('Post repartagé avec succès!'); 
                    // Idéalement, utiliser un système de notification plus discret (toast)
                    
                    // Optionnel: Ajouter le nouveau post au début du fil
                    // fetch(`/post/${data.newPostId}/render`) // Endpoint à créer qui renvoie le HTML du post
                    //    .then(res => res.text())
                    //    .then(html => { /* Insérer le HTML au début de la liste des posts */});

                } else {
                    console.error('Erreur lors du partage:', data.message);
                    alert(data.message || 'Une erreur est survenue lors du partage.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert(error.message || 'Une erreur est survenue.');
            })
            .finally(() => {
                // Réactiver le bouton
                if (submitButton) submitButton.disabled = false;
            });
        }
    });

    // Gestionnaire pour afficher/masquer la section commentaires
    document.body.addEventListener('click', function(event) {
        const commentToggleButton = event.target.closest('.comment-toggle-button');
        if (commentToggleButton) {
            const postId = commentToggleButton.dataset.postId;
            const commentsSection = document.getElementById(`comments-${postId}`);
            if (commentsSection) {
                const isVisible = commentsSection.style.display !== 'none';
                commentsSection.style.display = isVisible ? 'none' : 'block';
                // Optionnel: faire défiler vers la section si elle devient visible
                if (!isVisible) {
                    // commentsSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    // Faire focus sur l'input de commentaire
                    const commentInput = commentsSection.querySelector('.comment-input');
                    if (commentInput) {
                        commentInput.focus();
                    }
                }
            }
        }
    });

}); 