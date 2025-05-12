document.addEventListener('DOMContentLoaded', function() {
    const postContent = document.querySelector('.post-content');
    if (!postContent) return;

    let mentionTrigger = false;
    let hashtagTrigger = false;
    let currentWord = '';
    let suggestionBox = null;

    function createSuggestionBox() {
        if (!suggestionBox) {
            suggestionBox = document.createElement('div');
            suggestionBox.className = 'suggestion-box card shadow-sm';
            suggestionBox.style.position = 'absolute';
            suggestionBox.style.display = 'none';
            document.body.appendChild(suggestionBox);
        }
    }

    function showSuggestions(suggestions, type) {
        if (!suggestionBox) return;

        const rect = window.getSelection().getRangeAt(0).getBoundingClientRect();
        suggestionBox.style.left = `${rect.left + window.scrollX}px`;
        suggestionBox.style.top = `${rect.bottom + window.scrollY}px`;
        
        if (suggestions.length > 0) {
            suggestionBox.innerHTML = suggestions.map(suggestion => `
                <div class="suggestion-item p-2" data-value="${suggestion.value}" data-type="${type}">
                    ${type === 'mention' ? `
                        <div class="d-flex align-items-center">
                            <img src="${suggestion.avatar}" class="rounded-circle me-2" width="24" height="24">
                            <div>
                                <div class="fw-bold">${suggestion.name}</div>
                                <small class="text-muted">@${suggestion.username}</small>
                            </div>
                        </div>
                    ` : `
                        <div class="d-flex align-items-center">
                            <i class="bi bi-hash me-2"></i>
                            <span>${suggestion.value}</span>
                        </div>
                    `}
                </div>
            `).join('');
            suggestionBox.style.display = 'block';

            // Gestionnaire de clic pour les suggestions
            suggestionBox.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const type = this.getAttribute('data-type');
                    insertSuggestion(value, type);
                });
            });
        } else {
            suggestionBox.style.display = 'none';
        }
    }

    function insertSuggestion(value, type) {
        const selection = window.getSelection();
        const range = selection.getRangeAt(0);
        const prefix = type === 'mention' ? '@' : '#';
        
        // Supprimer le texte de déclenchement
        range.setStart(range.startContainer, range.startOffset - currentWord.length - 1);
        range.deleteContents();
        
        // Insérer la suggestion
        const suggestionNode = document.createTextNode(`${prefix}${value} `);
        range.insertNode(suggestionNode);
        
        // Placer le curseur après la suggestion
        range.setStartAfter(suggestionNode);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
        
        // Réinitialiser
        resetTriggers();
    }

    function resetTriggers() {
        mentionTrigger = false;
        hashtagTrigger = false;
        currentWord = '';
        if (suggestionBox) {
            suggestionBox.style.display = 'none';
        }
    }

    function searchSuggestions(query, type) {
        // Simuler une recherche d'API
        return fetch(`/api/${type}-suggestions?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .catch(error => {
                console.error('Erreur lors de la recherche de suggestions:', error);
                return [];
            });
    }

    postContent.addEventListener('input', function() {
        const selection = window.getSelection();
        if (!selection.rangeCount) return;

        const range = selection.getRangeAt(0);
        const text = range.startContainer.textContent;
        const cursorPosition = range.startOffset;

        // Détecter les déclencheurs @ et #
        if (text[cursorPosition - 1] === '@') {
            mentionTrigger = true;
            hashtagTrigger = false;
            currentWord = '';
            createSuggestionBox();
        } else if (text[cursorPosition - 1] === '#') {
            hashtagTrigger = true;
            mentionTrigger = false;
            currentWord = '';
            createSuggestionBox();
        }

        // Si un déclencheur est actif, mettre à jour la recherche
        if (mentionTrigger || hashtagTrigger) {
            const lastChar = text[cursorPosition - 1];
            if (lastChar === ' ' || lastChar === null) {
                resetTriggers();
            } else {
                const words = text.slice(0, cursorPosition).split(/\s+/);
                currentWord = words[words.length - 1].slice(1);
                
                if (currentWord.length > 0) {
                    const type = mentionTrigger ? 'mention' : 'hashtag';
                    searchSuggestions(currentWord, type)
                        .then(suggestions => showSuggestions(suggestions, type));
                }
            }
        }
    });

    // Fermer la boîte de suggestions lors d'un clic en dehors
    document.addEventListener('click', function(e) {
        if (suggestionBox && !suggestionBox.contains(e.target) && !postContent.contains(e.target)) {
            resetTriggers();
        }
    });
}); 